<?php
/*
Plugin Name: 		GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership
Plugin URI: 		https://gourl.io/bitcoin-wordpress-plugin.html
Description: 		Official <a href="https://gourl.io">GoUrl.io</a> Bitcoin Payment Gateway Plugin for Wordpress. Provides <a href="https://gourl.io/lib/examples/pay-per-product-multi.php">Pay-Per-Product</a>, <a href="https://gourl.io/lib/examples/pay-per-download-multi.php">Pay-Per-Download</a>, <a href="https://gourl.io/lib/examples/pay-per-membership-multi.php">Pay-Per-Membership</a>, <a href="https://gourl.io/lib/examples/pay-per-page-multi.php">Pay-Per-View</a> and bitcoin/altcoin payment gateways for - <a href='https://gourl.io/bitcoin-payments-woocommerce.html'>WooCommerce</a>, <a href='https://gourl.io/bitcoin-payments-wp-ecommerce.html'>WP eCommerce</a>, <a href='https://gourl.io/bitcoin-payments-jigoshop.html'>Jigoshop</a>, <a href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html'>MarketPress</a>, <a href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html'>AppThemes</a>, <a href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html'>Paid Memberships Pro</a>, <a href='https://gourl.io/bbpress-premium-membership.html'>bbPress</a>, <a href='https://gourl.io/bitcoin-donations-wordpress-plugin.html'>Give Donations</a>, etc. Accept Bitcoin, Litecoin, Dogecoin, Dash, Speedcoin, Reddcoin, Potcoin, Feathercoin, Paycoin, Vertcoin, Vericoin, Peercoin, MonetaryUnit, Swiscoin payments online. No Chargebacks, Global, Secure.  All in automatic mode.   
Version: 			1.3.13
Author: 			GoUrl.io
Author URI: 		https://gourl.io
License: 			GPLv2
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: 	https://github.com/cryptoapi/Bitcoin-Wordpress-Plugin

 *
 * GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership is free software:
 * you can redistribute/resell it and/or modify it under the terms of the
 * GNU General Public License as published by  the Free Software Foundation,
 * either version 2 of the License, or any later version.
 *
 * GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership is distributed
 * in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
*/


if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly  


$dir_arr = wp_upload_dir();

DEFINE('GOURL', 				"gourl");
DEFINE('GOURL_PREVIEW', 		"gourladmin");
DEFINE('GOURL_VERSION', 		"1.3.13");
DEFINE('GOURL_ADMIN', 			admin_url("admin.php?page="));
DEFINE('GOURL_DIR',  			$dir_arr["basedir"]."/".GOURL.'/');
DEFINE('GOURL_DIR2', 			$dir_arr["baseurl"]."/".GOURL.'/');
DEFINE('GOURL_BASENAME', 		plugin_basename(__FILE__));
DEFINE("GOURL_PERMISSION", 		"add_users");

DEFINE('GOURL_TAG_DOWNLOAD',	"gourl-download"); 		// [gourl-download id=1] 				- paid download tag
DEFINE('GOURL_TAG_PRODUCT',		"gourl-product"); 		// [gourl-product id=1] 				- paid product tag
DEFINE('GOURL_TAG_VIEW',		"gourl-lock"); 			// [gourl-lock img='image1.jpg'] 		- paid lock page tag
DEFINE('GOURL_TAG_MEMBERSHIP',	"gourl-membership"); 	// [gourl-membership img='image1.png'] 	- paid membership tag
DEFINE('GOURL_TAG_MEMCHECKOUT',	"gourl-membership-checkout"); 	// [gourl-membership-checkout img='image1.png'] 	- membership checkout page tag

DEFINE('GOURL_LOCK_START',		"<!-- start_gourlpayment_box -->"); 
DEFINE('GOURL_LOCK_END',		"<!-- end_gourlpayment_box -->");

DEFINE('CRYPTOBOX_WORDPRESS',	true);

unset($dir_arr);


require_once(plugin_dir_path( __FILE__ )."/gourl.php");


register_activation_hook(__FILE__, "gourl_activate");
register_deactivation_hook(__FILE__, "gourl_deactivate");

add_action('show_user_profile', 	'gourl_show_user_profile');
add_action('edit_user_profile', 	'gourl_edit_user_profile');
add_filter('plugin_action_links', 	'gourl_action_links', 10, 2);
add_action('plugins_loaded', 		'gourl_load_textdomain');

if (function_exists( 'mb_stripos' ) && function_exists( 'mb_strripos' ) && function_exists( 'curl_init' ) && function_exists( 'mysqli_connect' ) && version_compare(phpversion(), '5.4.0', '>=')) $gourl = new gourlclass();
   
   
