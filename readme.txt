=== CSV Export for Click Post ===
Contributors: hatchbitco
Donate link: https://www.hatchbit.jp/
Tags: e-commerce, export
Requires at least: 5.3.2
Tested up to: 5.3.2
Stable tag: 5.3.2
Requires PHP: 7.1.2
License: MIT
License URI: https://opensource.org/licenses/mit-license.php

== Description ==

"CSV Export for Click Post" is a Welcart-only extension plug-in and is intended for use in Japan only.

Provides CSV file output function for click post.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/welcart_csv4clickpost` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Welcart Shop->システム設定->拡張機能 screen to configure the plugin

== Frequently Asked Questions ==

= CSV file is garbled =

The encoding of the CSV file is Shift JIS.
 
= CSV file is empty =

Check the checkbox of the target order information.


== Screenshots ==

1. screenshot-1.png


== Changelog ==

= 1.1.2 =
* properly sanitized, as we required

= 1.1.1 =
* Renewed README.

= 1.1.0 =
* Remove unnecessary functions.

= 1.0.2 =
* Supports file format errors.

= 1.0.1 =
* Initial version.

== Upgrade Notice ==

= 1.1.1 =
This version fixes a security related bug.  Upgrade immediately.
