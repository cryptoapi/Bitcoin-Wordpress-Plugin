<?php
/*
Plugin Name: 		GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership
Plugin URI: 		https://gourl.io/bitcoin-wordpress-plugin.html
Description: 		Official <a href="https://gourl.io">GoUrl.io</a> Bitcoin Payment Gateway Plugin for Wordpress. <a href="http://gourl.io/lib/examples/pay-per-product-multi.php">Pay-Per-Product</a> - sell your products online. <a href="http://gourl.io/lib/examples/pay-per-download-multi.php">Pay-Per-Download</a> - make money on digital file downloads. <a href="http://gourl.io/lib/examples/pay-per-membership-multi.php">Pay-Per-Membership</a> - easy to use website membership system with bitcoin payments. <a href="http://gourl.io/lib/examples/pay-per-page-multi.php">Pay-Per-View</a> - offer paid access to your premium content/videos for unregistered visitors, no registration needed, anonymous. Easily Sell Files, Videos, Music, Photos, Premium Content on your WordPress site/blog and accept Bitcoin, Litecoin, Speedcoin, Dogecoin, Paycoin, Darkcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin payments online. No Chargebacks, Global, Secure.  All in automatic mode. Easy to integrate Bitcoin payments to other wordpress plugins with <a href="https://gourl.io/bitcoin-wordpress-plugin.html">Affiliate Program</a> to plugin owners using this plugin functionality.
Version: 			1.2.8
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


if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly in wordpress


$dir_arr = wp_upload_dir();

DEFINE('GOURL', 				"gourl");
DEFINE('GOURL_PREVIEW', 		"gourladmin");
DEFINE('GOURL_NAME', 			__('GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership', GOURL));
DEFINE('GOURL_VERSION', 		"1.2.8");
DEFINE('GOURL_ADMIN', 			admin_url("admin.php?page="));
DEFINE('GOURL_DIR',  			$dir_arr["basedir"]."/".GOURL.'/');
DEFINE('GOURL_DIR2', 			$dir_arr["baseurl"]."/".GOURL.'/');
DEFINE('GOURL_BASENAME', 		plugin_basename(__FILE__));

DEFINE('GOURL_TAG_DOWNLOAD',	"gourl-download"); 		// [gourl-download id=1] 				- paid download tag
DEFINE('GOURL_TAG_PRODUCT',		"gourl-product"); 		// [gourl-product id=1] 				- paid product tag
DEFINE('GOURL_TAG_VIEW',		"gourl-lock"); 			// [gourl-lock img='image1.jpg'] 		- paid lock page tag
DEFINE('GOURL_TAG_MEMBERSHIP',	"gourl-membership"); 	// [gourl-membership img='image1.png'] 	- paid membership tag

DEFINE('GOURL_LOCK_START',		"<!-- start_gourlpayment_box -->"); 
DEFINE('GOURL_LOCK_END',		"<!-- end_gourlpayment_box -->");

DEFINE('CRYPTOBOX_WORDPRESS',	true);

unset($dir_arr);


require_once(plugin_dir_path( __FILE__ )."/gourl.php");

register_deactivation_hook(__FILE__, "gourl_uninstall");

add_action('show_user_profile', 'gourl_show_user_profile');
add_action('edit_user_profile', 'gourl_edit_user_profile');
add_filter('plugin_action_links', 'gourl_action_links', 10, 2);

$gourl = new gourlclass();

