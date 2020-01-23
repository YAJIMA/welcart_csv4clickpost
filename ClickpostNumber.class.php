<?php
/**
CSV Export for Click Post
Version: 1.0.0
Author: HatchBit & Co.
**/

class CLICKPOST_NUMBER
{
	public static $opts;

	public function __construct(){

		self::initialize_data();

		if( is_admin() ){

			add_action( 'usces_action_admin_system_extentions', array( $this, 'setting_form') );
			add_action( 'admin_footer-welcart-shop_page_usces_system', array( $this, 'system_js') );
			add_action( 'init', array( $this, 'save_data') );

			if( self::$opts['activate_flag'] ){

				add_action( 'usces_action_order_list_page', array( $this, 'cp_action') );
				add_action( 'usces_action_order_list_searchbox_bottom', array( $this, 'action_button') );
				add_filter( 'usces_filter_order_list_page_js', array( $this, 'order_list_page_js') );
				add_action( 'usces_action_order_list_footer', array( $this, 'order_list_footer') );

				add_action( 'usces_after_cart_instant', array( $this, 'after_cart_instant') );

			}
		}
	}

	/**********************************************
	* Initialize
	* Modified: 2020/01/23
	***********************************************/
	public function initialize_data(){
		global $usces;
		$options = get_option('usces_ex');

		$options['system']['clickpostcsv']['activate_flag'] = !isset($options['system']['clickpostcsv']['activate_flag']) ? 0 : (int)$options['system']['clickpostcsv']['activate_flag'];

		update_option( 'usces_ex', $options );
		self::$opts = $options['system']['clickpostcsv'];
	}

	/**********************************************
	* save option data
	* Modified: 2020/01/23
	***********************************************/
	public function save_data(){
		global $usces;
		if(isset($_POST['usces_cp_option_update'])) {
			check_admin_referer( 'admin_system', 'wc_nonce' );

			$usces->stripslashes_deep_post($_POST);
			self::$opts['activate_flag'] = isset($_POST['cp_activate_flag']) ? (int)$_POST['cp_activate_flag'] : 0;

			$options = get_option('usces_ex');
			$options['system']['clickpostcsv'] = self::$opts;
			update_option('usces_ex', $options);
		}
	}

	/**********************************************
	* setting_form
	* Modified:27 Oct.2015
	***********************************************/
	public function setting_form(){
		$status =  self::$opts['activate_flag'] ? '<span class="running">' . __('Running', 'usces') . '</span>' : '<span class="stopped">' . __('Stopped', 'usces') . '</span>';
?>
	<form action="" method="post" name="option_form" id="clickpostcsv_form">
	<div class="postbox">
		<h3 class="hndle" id="clickpostcsv"><span>クリックポスト連携</span><?php echo $status; ?></h3>
		<div class="inside">
		<table class="form_table">
			<tr height="35">
				<th class="system_th"><a style="cursor:pointer;" onclick="toggleVisibility('ex_cp_activate_flag');"><?php _e('Activation', 'usces'); ?></a></th>
				<td width="10"><input name="cp_activate_flag" id="cp_activate_flag0" type="radio" value="0"<?php if(self::$opts['activate_flag'] === 0) echo 'checked="checked"'; ?> /></td><td width="100"><label for="cp_activate_flag0"><?php _e('disable', 'usces'); ?></label></td>
				<td width="10"><input name="cp_activate_flag" id="cp_activate_flag1" type="radio" value="1"<?php if(self::$opts['activate_flag'] === 1) echo 'checked="checked"'; ?> /></td><td><label for="cp_activate_flag1"><?php _e('enable', 'usces'); ?></label></td>
				<td><div id="ex_cp_activate_flag" class="explanation">クリックポスト用のCSV出力機能を有効化します。</div></td>
			</tr>
		</table>
		<hr />
		<input name="usces_cp_option_update" type="submit" class="button button-primary" value="<?php _e('change decision','usces'); ?>" />
		</div>
	</div><!--postbox-->
	<?php wp_nonce_field( 'admin_system', 'wc_nonce' ); ?>
	</form>
<?php
	}

	/*************************************
	 * cp_action
	 * Modified: 2020/01/23
	 ************************************/
	public function cp_action( $order_action ) {

		switch($order_action){
			case 'cp_register':
				if( isset($_GET['cp_regfile']) && !WCUtils::is_blank($_GET['cp_regfile']) ) {

					$res = $this->register_tracking_number();
					$_GET['usces_status'] = isset($res['status']) ? $res['status'] : '';
					$_GET['usces_message'] = isset($res['message']) ? $res['message'] : '';
					add_filter( 'usces_order_list_action_status', array( $this, 'order_list_action_status') );
					add_filter( 'usces_order_list_action_message', array( $this, 'order_list_action_message') );
				}
				break;

			case 'dl_cpcsv':
				$this->outcsv_shipping();
				break;

		}
	}

	public function order_list_action_status($status) {
		if( isset( $_GET['usces_status'] ) && !empty( $_GET['usces_status'] ) ){
			$status = $_GET['usces_status'];
		}
		return $status;
	}

	public function order_list_action_message($message) {
		if( isset( $_GET['usces_message'] ) && !empty( $_GET['usces_message'] ) ){
			$message = $_GET['usces_message'];
		}
		return $message;
	}
	
	public function action_button() {
		echo '
				<input type="button" id="dl_cpcsv" class="searchbutton" value="クリックポストCSVデータ出力" /></td>
		';
	}

    public function order_list_page_js() {
        $_wp_http_referer = urlencode(wp_unslash( $_SERVER['REQUEST_URI'] ));
        $wc_nonce = wp_create_nonce( 'admin_system' );
        $html = '
		$("#cp_upload_dialog").dialog({
			bgiframe: true,
			autoOpen: false,
			title: "データ取込",
			height: 360,
			width: 600,
			modal: true,
			buttons: {
				'.__('close', 'usces').': function() {
					$(this).dialog("close");
				}
			},
			close: function() {}
		});
		$("#up_cpcsv").click(function() {
			$("#cp_upload_dialog").dialog( "open" );
		});
		$("#dl_cpcsv").click(function() {
			if( $("input[name*=\'listcheck\']:checked").length == 0 ) {
				alert("'.__('Choose the data.', 'usces').'");
				$("#oederlistaction").val("");
				return false;
			}
			var listcheck = "";
			$("input[name*=\'listcheck\']").each(function(i) {
				if( $(this).attr("checked") ) {
					listcheck += "&listcheck["+i+"]="+$(this).val();
				}
			});
			location.href = "'.USCES_ADMIN_URL.'?page=usces_orderlist&order_action=dl_cpcsv"+listcheck+"&noheader=true&_wp_http_referer=' . $_wp_http_referer . '&wc_nonce=' . $wc_nonce . '";
		});
		';
        echo $html;
    }

    public function system_js() {
?>
	<script type="text/javascript">
		jQuery(function($){
			$("input[name='cp_sponsor_flag']").change(function() {
				if( $(this).val() == "0" ) {
					$(".cp_sponsor").hide("slow");
				}else{
					$(".cp_sponsor").show("slow");
				}
			});
			if( $("input[name='cp_sponsor_flag']:checked").val() == "0" ) {
				$(".cp_sponsor").hide();
			}else{
				$(".cp_sponsor").show();
			}
		});
	</script>
<?php
	}

	private function isdate( $date ) {
		if( empty($date) ) {
			return false;
		}
		try {
			new DateTime( $date );
			list( $year, $month, $day ) = explode( '-', $date );
			$res = checkdate( (int)$month, (int)$day, (int)$year );
			return $res;
		} catch( Exception $e ) {
			return false;
		}
	}

    public function order_list_footer() {
        $html = '
		<div id="cp_upload_dialog">
		</div>
		';
        echo $html;
    }

    public function upload_tracking_number() {
        global $wpdb, $usces;

        check_admin_referer( 'admin_system', 'wc_nonce' );
        // Upload
        $path = WP_CONTENT_DIR.'/uploads/';
        if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'cpupload' ) {
            $workfile = $_FILES["usces_upcsv"]["tmp_name"];
            if( !is_uploaded_file( $workfile ) ) {
                $res['status'] = 'error';
                $res['message'] = __('The file was not uploaded.', 'usces');
                $url = USCES_ADMIN_URL.'?page=usces_orderlist&usces_status='.$res['status'].'&usces_message='.urlencode( $res['message'] );
                wp_redirect( $url );
                exit;
            }

            list( $fname, $fext ) = explode( '.', $_FILES["usces_upcsv"]["name"], 2 );
            if( $fext != 'csv' ) {
                $res['status'] = 'error';
                $res['message'] =  __('The file is not supported.', 'usces')."(".$_FILES["usces_upcsv"]["name"].")";
                $url = USCES_ADMIN_URL.'?page=usces_orderlist&usces_status='.$res['status'].'&usces_message='.urlencode( $res['message'] );
                wp_redirect( $url );
                exit;
            }

            $new_filename = $fname.'_'.time().'.'.$fext;
            $upload_file = $path.mb_convert_encoding($new_filename, "SJIS", "UTF-8");
            if( !move_uploaded_file( $workfile, $upload_file ) ) {
                $res['status'] = 'error';
                $res['message'] =  __('The file was not stored.', 'usces')."(".$_FILES["usces_upcsv"]["name"].")";
                $url = USCES_ADMIN_URL.'?page=usces_orderlist&usces_status='.$res['status'].'&usces_message='.urlencode( $res['message'] );
                wp_redirect( $url );
                exit;
            }
            return $new_filename;
        }
    }

    public function register_tracking_number() {
        global $wpdb, $usces;

        //check_admin_referer( 'admin_system', 'wc_nonce' );
        // cp_register
        $path = WP_CONTENT_DIR.'/uploads/';
        $file_name = '';
        $reg_file = '';
        if( isset($_REQUEST['cp_regfile']) && !WCUtils::is_blank($_REQUEST['cp_regfile']) && isset($_REQUEST['order_action']) && $_REQUEST['order_action'] == 'cp_register' ) {
            $file_name = $_REQUEST['cp_regfile'];
            $reg_file = $path.mb_convert_encoding($file_name, "SJIS", "UTF-8");
            if( !file_exists( $reg_file ) ) {
                $res['status'] = 'error';
                $res['message'] =  __('CSV file does not exist.', 'usces').esc_html( $file_name );
                return( $res );
            }
        }

        $wpdb->query( 'SET SQL_BIG_SELECTS=1' );
        set_time_limit( 3600 );

        define( 'CP_tracking_number', 3 );//荷物配送番号
        define( 'CP_tracking_DATE', 4 );//出荷日付
        define( 'CP_ORDER_ID', 0 );//Welcart受注番号

        $orglines = array();
        $sp = ',';
        $total_num = 0;
        $comp_num = 0;
        $err_num = 0;
        $res = array();

        if( !( $fpo = fopen( $reg_file, "r" ) ) ) {
            $res['status'] = 'error';
            $res['message'] = __('A file does not open.', 'usces').esc_html( $file_name );
            return $res;
        }

        $fname_parts = explode( '.', $reg_file );
        if( 'csv' !== end( $fname_parts ) ) {
            $res['status'] = 'error';
            $res['message'] = __('This file is not in the CSV file.', 'usces').esc_html( $file_name );
            return $res;

        } else {
            $buf = '';
            while( !feof($fpo) ) {
                $temp = fgets( $fpo, 10240 );
                if( 0 == strlen($temp) ) continue;
                $orglines[] = str_replace( '"', '', $temp );
            }
        }
        fclose( $fpo );

        $total_num = count( $orglines );

        $pre_id = 0;
        foreach( $orglines as $line ) {
            $data = explode( $sp, $line );
            $cp_order_id = mb_convert_encoding($data[CP_ORDER_ID], 'UTF-8', 'SJIS');
            $boids = explode( '__', $cp_order_id, 2 );
            if( isset($boids[1]) )
                $group_id = $boids[1];
            else
                $group_id = false;

            $order_id = $this->get_order_id_from_dec( $boids[0] );
            $tracking_number = trim(mb_convert_encoding($data[CP_tracking_number], 'UTF-8', 'SJIS'));
            $order_data = $usces->get_order_data( $order_id );
            if( $order_data ) {
                if( false !== $group_id ){
                    $group_value = unserialize($usces->get_order_meta_value( ('group_'.$group_id), $order_id ));
                    $group_value['delivery_company'] = 'クリックポスト';
                    $group_value['tracking_number'] = $tracking_number;
                    $usces->set_order_meta_value( ('group_'.$group_id), serialize($group_value), $order_id );
                }else{
                    if( $pre_id != $order_id ){
                        $usces->del_order_meta( 'tracking_number', $order_id );
                    }
                    $current_tracking = $usces->get_order_meta_value( 'tracking_number', $order_id );
                    if( !empty($current_tracking) ){
                        if( false === strpos($current_tracking, $tracking_number) ){
                            $tracking_number = $current_tracking . ',' . $tracking_number;
                        }
                    }
                    $usces->set_order_meta_value( 'tracking_number', $tracking_number, $order_id );
                    $usces->set_order_meta_value( 'delivery_company', 'クリックポスト', $order_id );
                }

                do_action( 'wcyncp_action_tracking_number_set', $order_data, $group_id, $tracking_number );

                $comp_num++;

            } else {
                $err_num++;
            }
            $pre_id = $order_id;
        }

        $res['status'] = 'success';
        $res['message'] = sprintf(__('%2$s of %1$s lines registration completion, error on %3$s lines.', 'usces'), $total_num, $comp_num, $err_num);
        unlink( $reg_file );

        return $res;
    }

    public function after_cart_instant() {
        if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'usces_cp_tracking' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'cpupload' ) {
            check_admin_referer( 'admin_system', 'wc_nonce' );
            $filename = self::upload_tracking_number();
            $url = USCES_ADMIN_URL.'?page=usces_orderlist&usces_status=none&usces_message=&order_action=cp_register&cp_regfile='.$filename;
            wp_redirect( $url );
            exit;
        }
    }

    public function get_order_id_from_dec( $dec_order_id ) {
		global $wpdb;
		$order_meta_table_name = $wpdb->prefix."usces_order_meta";
		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM $order_meta_table_name WHERE meta_key = %s AND meta_value = %s LIMIT 1", 'dec_order_id', $dec_order_id ) );
		return $order_id;
	}

	public function make_individual_cart( $cart_org ) {

		$individual_cart = array();
		$normal_cart = array();

		foreach( $cart_org as $org ){
			$is_individual = false;
			$post_id = (int)$org['post_id'];
			$quantity = (int)$org['quantity'];
			$is_individual = get_post_meta( $post_id, '_itemIndividualSCharge', true );
			if( $is_individual ){
				$org['quantity'] = 1;
				for( $i=0; $i<$quantity; $i++ ){
					$individual_cart[] = array( $org );
				}
			}else{
				$normal_cart[] = $org;
			}
		}
		if( !empty($normal_cart) ){
			$individual_cart[] = $normal_cart;
		}
		return $individual_cart;
	}

	public function outcsv_shipping() {
		global $usces;
		check_admin_referer( 'admin_system', 'wc_nonce' );

		$filename = "kuronekoyamato".current_time('YmdHis').".csv";
		$ids = $_GET['listcheck'];

		$billing_code = self::$opts['billing_code'];
		$kind_code = self::$opts['kind_code'];
		$fare_id = self::$opts['fare_id'];
		$time_zone = self::$opts['time_zone'];
		$time_zone = str_replace(array("\r\n", "\r"), "\n", trim($time_zone));
		$time_zone_arr = explode("\n", $time_zone);
		foreach( (array)$time_zone_arr as $zone_str ){
			list($key, $zone) = explode(":", trim($zone_str), 2);
			$delivery_time[$key] = $zone;
		}

		$line = '';
		$ldata = array(
            'お届け先郵便番号' => '',
            'お届け先氏名' => '',
            'お届け先敬称' => '',
            'お届け先住所1行目' => '',
            'お届け先住所2行目' => '',
            'お届け先住所3行目' => '',
            'お届け先住所4行目' => '',
            '内容品' => ''
		);
		$line_header = apply_filters( 'wcyncp_filter_outcsv_header', $ldata );
		foreach( $line_header as $lkey => $lvalue ){
			$line .= '"'.$lkey.'",';
		}
		rtrim( $line, ',' );
		$line .= "\r\n";


		foreach( (array)$ids as $order_id ) {

			$data = $usces->get_order_data( $order_id, 'direct' );
			$delivery = unserialize($data['order_delivery']);


			$deco_order_id = usces_get_deco_order_id( $order_id );

			if( !empty($data['order_delivery_time']) ) {
				if( array_key_exists($data['order_delivery_time'], $delivery_time) ) {
					$arrivaltime = $delivery_time[$data['order_delivery_time']];
				} else {
					$arrivaltime = "";
				}
			} else {
				$arrivaltime = "";
			}

			$order_date = date('Ymd', strtotime($data['order_date']));
			$total_full_price = $data['order_item_total_price'] - $data['order_usedpoint'] + $data['order_discount'] + $data['order_shipping_charge'] + $data['order_cod_fee'] + $data['order_tax'];
			if( $total_full_price < 0 ) $total_full_price = 0;
			$payments = usces_get_payments_by_name($data['order_payment_name']);
			if( 'COD' == $payments['settlement'] ){
				$cod_price = $total_full_price;
				$okurisyu = 2;
			}else{
				$cod_price = '';
				$okurisyu = 0;
			}
			$okurisyu = apply_filters( 'wcyncp_filter_outcsv_okurisyu', $okurisyu, $data );

			$syukkayoteibi = current_time('Y/m/d');

			if( !strtotime($data['order_delivery_date']) ){
				if( self::$opts['delivery_date'] ){
					$otodokekiboubi = "最短日";
				}else{
					$otodokekiboubi = "";
				}
			}else{
				$otodokekiboubi = str_replace('-', '/', $data['order_delivery_date']);
			}

			if( self::$opts['sponsor_flag'] ){

				$sponsor_code = self::$opts['sponsor_code'];
				$sponsor_tel = self::$opts['sponsor_tel'];
				$sponsor_telb = self::$opts['sponsor_telb'];
				$sponsor_zip = str_replace( '-', '', self::$opts['sponsor_zip']);
				$sponsor_add1 = mb_substr(mb_convert_kana(self::$opts['sponsor_add1'], 'A'),0,32 );
				$sponsor_add2 = mb_substr(mb_convert_kana(self::$opts['sponsor_add2'], 'A'),0,16 );
				$sponsor_name = mb_substr(mb_convert_kana(self::$opts['sponsor_name'], 'A'),0,16 );
				$sponsor_kana = mb_substr(mb_convert_kana(self::$opts['sponsor_kana'], 'A'),0,16 );

			}else{

				$sponsor_code = '';
				$sponsor_tel = $data['order_tel'];
				$sponsor_telb = '';
				$sponsor_zip = str_replace( '-', '', $data['order_zip']);
				$sponsor_add1 = mb_substr(mb_convert_kana($data['order_pref'].$data['order_address1'].$data['order_address2'], 'A'),0,32 );
				$sponsor_add2 = mb_substr(mb_convert_kana($data['order_address3'], 'A'),0,16 );
				$sponsor_name = mb_substr(mb_convert_kana($data['order_name1']."　".$data['order_name2'], 'A'),0,16 );
				$sponsor_kana = '';

			}

			if( self::$opts['email'] ) {
				$email = '1';
				$order_email = $data['order_email'];
			} else {
				$email = '0';
				$order_email = '';
			}

			if( isset( $delivery['delivery_flag'] ) && 2 == $delivery['delivery_flag'] && !empty($data['mem_id']) && function_exists('msa_get_orderdestination') ){
				$orderdestination = msa_get_orderdestination( $order_id );
			}else{
				$orderdestination = array();
			}

			if( 0 < count($orderdestination) ){

				$msacart = msa_get_msacart_by_order( $order_id );
				foreach( $orderdestination as $group_id => $destination ){

					if( isset( $delivery['delivery_flag'] ) && 2 == $delivery['delivery_flag'] && !empty($data['mem_id']) && isset($orderdestination[$group_id]) && function_exists('msa_get_destination') ){
						$destination_info = msa_get_destination( $data['mem_id'], $orderdestination[$group_id]['destination_id'] );
					}else{
						$destination_info = array();
					}

					$cart_org = $msacart[$group_id]['cart'];
					$individual_cart = self::make_individual_cart( $cart_org );
					foreach( $individual_cart as $cart ){

						$ldata = array(
                            'お届け先郵便番号' => str_replace( '-', '', $destination_info['msa_zip']),
                            'お届け先氏名' => mb_substr(mb_convert_kana($destination_info['msa_name'], 'A')."　".mb_convert_kana($destination_info['msa_name2'], 'A'),0,16 ),
                            'お届け先敬称' => '様',
                            'お届け先住所1行目' => mb_substr(mb_convert_kana($destination_info['msa_pref'].$destination_info['msa_address1'], 'A'),0,32 ),
                            'お届け先住所2行目' => mb_substr(mb_convert_kana($destination_info['msa_address2'], 'A'),0,16 ),
                            'お届け先住所3行目' => mb_substr(mb_convert_kana($destination_info['msa_company'], 'A'),0,16 ),
                            'お届け先住所4行目' => '',
                            '内容品' => mb_substr($cart[0]['item_name'],0,25 ) . (isset($cart[1]) ? 'その他' : '')
						);
						$line_data = apply_filters( 'wcyncp_filter_outcsv_data', $ldata, $order_id, $data, $cart );
						foreach( $line_data as $lkey => $lvalue ){
							$line .= '"'.$lvalue.'",';
						}
						rtrim( $line, ',' );
						$line .= "\r\n";
					}
				}

			}else{

				$cart_org = usces_get_ordercartdata($order_id);
				$individual_cart = self::make_individual_cart( $cart_org );
				foreach( $individual_cart as $cart ){

					$ldata = array(
                        'お届け先郵便番号' => str_replace( '-', '', $delivery['zipcode']),
                        'お届け先氏名' => mb_substr(mb_convert_kana($delivery['name1']."　".$delivery['name2'], 'A'),0,16 ),
                        'お届け先敬称' => '様',
                        'お届け先住所1行目' => mb_substr(mb_convert_kana($delivery['pref'].$delivery['address1'], 'A'),0,32 ),
                        'お届け先住所2行目' => mb_substr(mb_convert_kana($delivery['address2'], 'A'),0,16 ),
                        'お届け先住所3行目' => mb_substr(mb_convert_kana($delivery['address3'], 'A'),0,16 ),
                        'お届け先住所4行目' => '',
                        '内容品' => mb_substr($cart[0]['item_name'],0,25 ) . (isset($cart[1]) ? 'その他' : '')
					);
					$line_data = apply_filters( 'wcyncp_filter_outcsv_data', $ldata, $order_id, $data, $cart );
					foreach( $line_data as $lkey => $lvalue ){
						$line .= '"'.$lvalue.'",';
					}
					rtrim( $line, ',' );
					$line .= "\r\n";
				}
			}


		}
		ob_end_clean();
		$line = mb_convert_encoding( $line, "SJIS-win", "UTF-8" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=\"$filename\"" );
		//mb_http_output( "pass" );
		print( $line );
		exit();
	}
}
