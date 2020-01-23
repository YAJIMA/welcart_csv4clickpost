<?php
/**
Plugin Name: CSV Export for Click Post
Plugin URI: https://github.com/YAJIMA/welcart_csv4clickpost
Description: このプラグインは Welcart とクリックポストとの連携プラグインです。Welcart 本体と一緒にご利用ください。
Version: 1.0.0
Author: YAJIMA Yuichiro
Author URI: https://www.lancers.jp/profile/yajima8818
**/

if( !defined( 'ABSPATH' ) ) {
	exit;
}

define('CLICKPOST_NUMBER', true);
define('CLICKPOST_NUMBER_VERSION', "1.0.0.20200123");
define('CLICKPOST_NUMBER_DIR', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)));
define('CLICKPOST_NUMBER_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));

if ( defined('USCES_VERSION') ){
	global $clickpost;
	if( is_object($clickpost) )
		return;

	require_once(CLICKPOST_NUMBER_DIR . '/ClickpostNumber.class.php');
	$clickpost = new CLICKPOST_NUMBER();
}
