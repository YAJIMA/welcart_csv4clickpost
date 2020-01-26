# CSV Export for Click Post
ウェルカート対応プラグイン、クリックポスト用CSVファイルエクスポート機能を提供します。

## サポート環境

以下の環境で動作を確認しています。  

- Wordpress 5.3.2
- Welcart e-Commerce 1.9.25

## インストール方法

1. ZIPファイルでダウンロードします。  
* バージョンを選択してダウンロードしたい場合は、[Releasesページ](https://github.com/YAJIMA/welcart_csv4clickpost/releases)から選択可能です。  
![download_zip](https://user-images.githubusercontent.com/3177471/73037783-a8b88100-3e93-11ea-917e-fc26b04f21b8.jpg)

2. Wordpress管理画面のプラグイン新規追加から **1でダウンロードしたZIPファイル**をアップロードします。  
アップロードが完了したら、プラグインを有効にしてください。  
![add_plugin](https://user-images.githubusercontent.com/3177471/73037722-6a22c680-3e93-11ea-9fb1-647148e14c7a.jpg)

3. Welcart Shop システム設定で有効化します。  
**品名に出力する項目**には、item_name(商品名) / item_code(商品コード) / sku_code(SKUコード) のいずれかが使用可能です。  
![system_setting](https://user-images.githubusercontent.com/3177471/73038174-c89c7480-3e94-11ea-82b5-70a4d7bcfbb3.jpg)

## 使用方法

1. Welcart Managemant 受注リストでCSVダウンロードできるようになります。  
受注リストページに**クリックポストCSVデータ出力**ボタンがありますので、ダウンロードしたい注文リストのチェックを入れてからボタンを押してください。  
![orderlist_csv_downloads-2](https://user-images.githubusercontent.com/3177471/73038398-a5be9000-3e95-11ea-854b-f517cd019f59.jpg)

