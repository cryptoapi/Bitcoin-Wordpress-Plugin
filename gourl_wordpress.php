<?php
/*
Plugin Name: 		GoUrl Bitcoin Payment Gateway & Paid Downloads & Membership
Plugin URI: 		https://gourl.io/bitcoin-wordpress-plugin.html
Description: 		Official <a href="https://gourl.io">GoUrl.io</a> Bitcoin Payment Gateway for Wordpress. White Label Solution. Provides bitcoin/altcoin payment gateways for - WooCommerce, Paid Memberships Pro, bbPress, Give Donations, Pay-Per-View, Pay-Per-Download, etc. Accept Bitcoin, BitcoinCash, BitcoinSV, Litecoin, Dash, Dogecoin, etc payments online. No Chargebacks, Global, Secure.  All in automatic mode.
Version: 		1.4.19
Author: 		GoUrl.io
Author URI: 		https://gourl.io
WC requires at least: 	2.1.0
WC tested up to: 	5.2.0
License: 		GPLv2
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


defined( 'ABSPATH' ) || exit; // Exit if accessed directly


$dir_arr = wp_upload_dir();

define( 'GOURL', 				'gourl' );
define( 'GOURL_PREVIEW', 		'gourladmin' );
define( 'GOURL_VERSION', 		'1.4.19' );
define( 'GOURL_ADMIN', 			admin_url( 'admin.php?page=' ) );
define( 'GOURL_DIR',  			$dir_arr["basedir"] . '/' . GOURL . '/' );
define( 'GOURL_DIR2', 			$dir_arr["baseurl"] . '/' . GOURL . '/' );
define( 'GOURL_PHP',  			rtrim( WP_PLUGIN_DIR, '/ ' ) . '/gourl-php' );
define( 'GOURL_PATH', 			dirname( __FILE__ ) );
define( 'GOURL_IMG', 			plugins_url( '/images/', __FILE__ ) );
define( 'GOURL_BASENAME', 		plugin_basename( __FILE__ ) );
define( 'GOURL_PERMISSION',		'add_users' );

define( 'GOURL_TAG_DOWNLOAD',		'gourl-download' ); 		// [gourl-download id=1] 				- paid download tag
define( 'GOURL_TAG_PRODUCT',		'gourl-product' ); 		// [gourl-product id=1] 				- paid product tag
define( 'GOURL_TAG_VIEW',			'gourl-lock' ); 			// [gourl-lock img='image1.jpg'] 		- paid lock page tag
define( 'GOURL_TAG_MEMBERSHIP',		'gourl-membership' ); 	// [gourl-membership img='image1.png'] 	- paid membership tag
define( 'GOURL_TAG_MEMCHECKOUT',	'gourl-membership-checkout' ); 	// [gourl-membership-checkout img='image1.png'] 	- membership checkout page tag

define( 'GOURL_LOCK_START',		'<!-- start_cryptopayment_box -->' );
define( 'GOURL_LOCK_END',		'<!-- end_cryptopayment_box -->' );

define( 'GOURL_RATES', json_encode( array(
	'USD' => 'US Dollar',
	'EUR' => 'Euro',
	'GBP' => "British Pound",
	'AUD' => 'Australian Dollar',
	'BRL' => 'Brazilian Real',
	'CAD' => 'Canadian Dollar',
	'CHF' => 'Swiss Franc',
	'CLP' => 'Chilean Peso',
	'CNY' => 'Chinese Yuan Renminbi',
	'DKK' => 'Danish Krone',
	'HKD' => 'Hong Kong Dollar',
	'INR' => 'Indian Rupee',
	'ISK' => 'Icelandic Krona',
	'JPY' => 'Japanese Yen',
	'KRW' => 'South Korean Won',
	'NZD' => 'New Zealand Dollar',
	'PLN' => 'Polish Zloty',
	'RUB' => 'Russian Ruble',
	'SEK' => 'Swedish Krona',
	'SGD' => 'Singapore Dollar',
	'THB' => 'Thai Baht',
	'TWD' => 'Taiwan New Dollar',
	) ) );

define( 'CRYPTOBOX_WORDPRESS', true );

unset( $dir_arr );


require_once( GOURL_PATH . '/gourl.php' );


register_activation_hook( __FILE__, 'gourl_activate' );
register_deactivation_hook( __FILE__, 'gourl_deactivate' );

add_action( 'show_user_profile', 	'gourl_show_user_profile' );
add_action( 'edit_user_profile', 	'gourl_edit_user_profile' );
add_filter( 'plugin_action_links', 	'gourl_action_links', 10, 2 );
add_action( 'plugins_loaded', 		'gourl_load_textdomain' );

if (function_exists( 'mb_stripos' ) && function_exists( 'mb_strripos' ) && function_exists( 'curl_init' ) && function_exists( 'mysqli_connect' ) && version_compare( phpversion(), '5.4.0', '>=' ) ) $gourl = new gourlclass();

