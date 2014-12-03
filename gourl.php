<?php
/*
Plugin Name: 	GoUrl Bitcoin Paid Downloads & Paid Access to Videos/Pages
Plugin URI: 	https://gourl.io/bitcoin-wordpress-plugin.html
Description: 	Official GoUrl.io Bitcoin Payment Gateway Plugin. Pay-Per-Download - make money on digital file downloads. Pay-Per-View/PageAccess - offer paid access to your premium content/videos. Easily Sell Files, Videos, Music, Photos, Software (digital downloads) on your WordPress site/blog and accept Bitcoin, Litecoin, Dogecoin, Speedcoin, Darkcoin, Vertcoin, Reddcoin, Feathercoin, Vericoin, Potcoin payments online. No Chargebacks, Global, Secure. Anonymous Bitcoins & Cryptocurrency Payments. All in automatic mode with GoUrl bitcoin / altcoin payment gateway.
Version: 		1.0
Author: 		GoUrl.io
Author URI: 	https://gourl.io
License: 		GPLv2
License URI: 	http://www.gnu.org/licenses/gpl-2.0.html
 *
 * GoUrl Bitcoin Paid Downloads & Paid Access to Videos/Pages is free software: 
 * you can redistribute/resell it and/or modify it under the terms of the 
 * GNU General Public License as published by  the Free Software Foundation, 
 * either version 2 of the License, or any later version.
 *
 * GoUrl Bitcoin Paid Downloads & Paid Access to Videos/Pages is distributed 
 * in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
*/

DEFINE('GOURL', 				"gourl");
DEFINE('GOURL_PREVIEW', 		"gourladmin");
DEFINE('GOURL_NAME', 			__('GoUrl Bitcoin Paid Downloads & Paid Access to Videos/Pages', GOURL));
DEFINE('GOURL_VERSION', 		1.00);
DEFINE('GOURL_ADMIN', 			get_bloginfo("wpurl")."/wp-admin/admin.php?page=");
DEFINE('GOURL_DIR',  			ABSPATH.'wp-content/uploads/'.GOURL.'/');
DEFINE('GOURL_DIR2', 			'/wp-content/uploads/'.GOURL.'/');

DEFINE('GOURL_TAG_DOWNLOAD',	"gourl-download"); 	// [gourl-download id=1] 			- paid download tag
DEFINE('GOURL_TAG_LOCK',		"gourl-lock"); 		// [gourl-lock img='image1.jpg'] 	- paid lock page tag

DEFINE('GOURL_LOCK_START',		"<!-- start_gourlpayment_box -->"); 
DEFINE('GOURL_LOCK_END',		"<!-- end_gourlpayment_box -->");

DEFINE('CRYPTOBOX_WORDPRESS',	true);


		
				

register_activation_hook(__FILE__, array(GOURL."class", "install"));

final class gourlclass 
{
	private $options 		= array(); 		// global setting values
	private $errors			= array(); 		// global setting errors
	private $payments		= array(); 		// global activated payments (bitcoin, litecoin, etc)
	
	private $page 			= array(); 		// current page url
	private $id 			= 0; 			// current record id
	private $record 		= array(); 		// current record values
	private $record_errors 	= array(); 		// current record errors
	private $record_info	= array(); 		// current record messages
	private $record_fields	= array(); 		// current record fields
	
	private $updated		= false;		// publish 'record updated' message
	
	private $coin_names 	= array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'DOGE' => 'dogecoin', 'SPD' => 'speedcoin', 'DRK' => 'darkcoin', 'RDD' => 'reddcoin', 'VTC' => 'vertcoin', 'FTC' => 'feathercoin', 'VRC' => 'vericoin', 'POT' => 'potcoin');
	private $coin_www   	= array('bitcoin' => 'https://bitcoin.org/', 'litecoin'  => 'https://litecoin.org/', 'dogecoin'  => 'http://dogecoin.com/', 'speedcoin'  => 'http://speedcoin.co/', 'darkcoin'  => 'https://www.darkcoin.io/', 'vertcoin'  => 'http://vertcoin.org/', 'reddcoin'  => 'http://reddcoin.com/', 'feathercoin' => 'http://www.feathercoin.com/', 'vericoin' => 'http://www.vericoin.info/', 'potcoin' => 'http://www.potcoin.com/');
	private $languages		= array("en"=>"English", "fr"=>"French", "ru"=>"Russian", "ar"=>"Arabic", "cn"=>"Simplified Chinese", "zh"=>"Traditional Chinese", "hi"=>"Hindi");
	private $fields_file 	= array("fileID" => 0,  "fileTitle" => "", "active" => 1, "fileName"  => "", "fileText" => "", "fileSize" => 0, "priceUSD"  => "0.00", "purchases"  => "0", "userFormat"  => "COOKIE", "expiryPeriod" => "2 DAYS", "lang"  => "en", "defCoin" => "", "defShow" => 0, "image"  => "", "imageWidth" => 200,  "priceShow" => 1, "paymentCnt" => 0, "paymentTime" => "", "updatetime"  => "", "createtime"  => "");
	private $expiry_period 	= array("NO EXPIRY", "1 HOUR", "2 HOURS", "3 HOURS", "6 HOURS", "12 HOURS", "1 DAY", "2 DAYS", "3 DAYS", "4 DAYS", "5 DAYS",  "1 WEEK", "2 WEEKS", "3 WEEKS", "4 WEEKS", "1 MONTH", "2 MONTHS", "3 MONTHS", "6 MONTHS", "12 MONTHS"); // payment expiry period
	private $store_visitorid = array('COOKIE','SESSION','IPADDRESS','MANUAL'); // Save auto-generated unique visitor ID in cookies, sessions or use the IP address to decide unique visitors (without use cookies)
	
	private $fields_ppv 	= array("ppvPrice" => "1", "ppvExpiry" => "1 DAY", "ppvLevel"  => 0, "ppvLang" => "en", "ppvCoin"  => "", "ppvOneCoin"  => "", "ppvImgMaxWidth"  => 0, "ppvTextAbove"  => "", "ppvTextBelow"  => "", "ppvTitle" => "", "ppvCommentAuthor"  => "", "ppvCommentBody"  => "", "ppvCommentReply"  => ""); 
	private $expiry_ppv		= array("2 DAYS", "1 DAY", "12 HOURS", "6 HOURS", "3 HOURS", "2 HOURS", "1 HOUR");
	private $lock_level_ppv = array("Unregistered Visitors", "Unregistered Visitors + Registered Subscribers", "Unregistered Visitors + Registered Subscribers/Contributors", "Unregistered Visitors + Registered Subscribers/Contributors/Authors");	
	
	
	
	/*
	 *  1. Initialize plugin
	 */
	public function __construct() 
	{

		// upload dir
		gourl_retest_dir();
		
		
		// Current Page, Record ID
		$this->page = (isset($_GET['page'])) ? $_GET['page'] : "";
		$this->id 	= (isset($_GET['id']) && intval($_GET['id'])) ? intval($_GET['id']) : 0;

		$this->updated = (isset($_GET['updated']) && $_GET["updated"] == "true") ? true : false;
				
		
		// Redirect
		if ($this->page == GOURL."space") { header("Location: ".GOURL_ADMIN.GOURL."payments"); die; }
		
				
		// General Plugin Settings
		$this->get_settings();
		$this->check_settings();
		

		// File Upload Page
		if ($this->page == GOURL.'file') 
		{
			$this->record_fields = $this->fields_file;
			$this->get_record();
			if ($this->id && !$_POST) $this->check_record();
			ini_set('max_execution_time', 3600);
			ini_set('max_input_time', 3600);
		}
		
		
		// Pay-Per-View (PPV) Settings
		if ($this->page == GOURL.'payperview')
		{
			$this->get_ppv_settings();
			if (!$_POST) $this->check_ppv_settings();
		}
		
		
		// Admin
		if (is_admin()) 
		{
			if ($this->errors) add_action('admin_notices', array(&$this, 'admin_warning'));
			if (!file_exists(GOURL_DIR."files") || !file_exists(GOURL_DIR."images") || !file_exists(GOURL_DIR."lockimg")) add_action('admin_notices', array(&$this, 'admin_warning_reactivate'));
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('init', array(&$this, 'admin_init'));
			add_action('admin_head', array(&$this, 'admin_header'), 15);
		} 
		else 
		{
			add_action("init", array(&$this, "front_init"));
			add_action("wp_head", array(&$this, "front_header"));
			add_shortcode(GOURL_TAG_DOWNLOAD, array(&$this, "front_shortcode_download"));
			add_shortcode(GOURL_TAG_LOCK, 	  array(&$this, "front_shortcode_payview"));
		}
		
		
		// Process Callbacks from GoUrl.io Payment Server
		add_action('parse_request', array(&$this, 'callback_parse_request'));
		
	}
	
	
	
	
	/*******************************************************************************************/
	
	
	
	/*
	 *  2. Get values from the options table
	*/
	private function get_settings()
	{
		$this->options["version"] 		= "";
		$this->options["box_width"] 	= "";
		$this->options["box_height"] 	= "";
		$this->options["box_style"] 	= "";
		$this->options["message_style"] = "";
		$this->options["message_style"] = "";
		$this->options["rec_per_page"] = "";
		$this->options["popup_message"] = "";
		
		foreach($this->coin_names as $k => $v)
		{
			$this->options[$v."public_key"] = "";
			$this->options[$v."private_key"] = "";
		}
			
		foreach ($this->options as $key => $value) 
		{
			$this->options[$key] = get_option(GOURL.$key);
		}
		
		$this->options["version"] = GOURL_VERSION;
		
		// default
		if (!$this->options["box_width"])  		$this->options["box_width"] 	= 520;
		if (!$this->options["box_height"]) 		$this->options["box_height"] 	= 230;
		if (!$this->options["rec_per_page"]) 	$this->options["rec_per_page"] 	= 20;
		if (!$this->options["popup_message"]) 	$this->options["popup_message"] = __('It is a Paid Download ! Please pay below', GOURL);
		
		return true;
	}
	
	
	
	
	/*
	 *  3.
	*/
	private function post_settings()
	{
		foreach ($this->options as $key => $value)
		{
			$this->options[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options[$key])) $this->options[$key] = trim($this->options[$key]);
		}
		
		$this->options["version"] = GOURL_VERSION;
	
		return true;
	}

	
	
	/*
	 *  4.
	*/	
	private function check_settings()
	{
		$this->errors = array();

		$f = true;
		foreach($this->coin_names as $k => $v)
		{
			$public_key  = trim($this->options[$v."public_key"]);
			$private_key = trim($this->options[$v."private_key"]);
			
			$boxID = $this->left($public_key, "AA");
			if ($public_key &&  (strpos($public_key, " ") !== false  || strlen($public_key) != 50  || $public_key != preg_replace('/[^A-Za-z0-9]/', '', $public_key)   || !strpos($public_key, "AA")  || !$boxID || !is_numeric($boxID) || !strpos($public_key, ucfirst(strtolower($v))."77".strtoupper($k)."PUB")))  $this->errors[$v."public_key"] = ucfirst($v) . ' ' . __('Box Invalid Public Key', GOURL)  . ' : ' . $public_key;
			
			$boxID = $this->left($private_key, "AA");
			if ($private_key && (strpos($private_key, " ") !== false || strlen($private_key) != 50 || $private_key != preg_replace('/[^A-Za-z0-9]/', '', $private_key) || !strpos($private_key, "AA") || !$boxID || !is_numeric($boxID) || !strpos($private_key, ucfirst(strtolower($v))."77".strtoupper($k)."PRV") || $boxID != $this->left($public_key, "AA"))) $this->errors[$v."private_key"] = ucfirst($v) . ' ' . __('Box Invalid Private Key', GOURL) . ' : ' . $private_key;
			
			if ($public_key && !$private_key) $this->errors[$v."private_key"] = ucfirst($v) . ' ' . __('Box Private Key  - cannot be empty', GOURL);
			if ($private_key && !$public_key) $this->errors[$v."public_key"]  = ucfirst($v) . ' ' . __('Box Public Key  - cannot be empty', GOURL);

			if ($public_key || $private_key) $f = false;
			
			if ($public_key && $private_key  && !isset($this->errors[$v."public_key"]) && !isset($this->errors[$v."private_key"])) $this->payments[$k] = ucfirst($v);
		}
		
		if ($f)  $this->errors[] = __('You need at least one payment method. Please enter your GoUrl Public/Private Keys', GOURL);

		if (!is_numeric($this->options["box_width"]) || round($this->options["box_width"]) != $this->options["box_width"] || $this->options["box_width"] < 480 || $this->options["box_width"] > 700) $this->errors[] = __('Invalid Payment Box Width. Allowed 480..700px', GOURL);
		if (!is_numeric($this->options["box_height"]) || round($this->options["box_height"]) != $this->options["box_height"] || $this->options["box_height"] < 200 || $this->options["box_height"] > 400) $this->errors[] = __('Invalid Payment Box Height. Allowed 200..400px', GOURL);

		if (!is_numeric($this->options["rec_per_page"]) || round($this->options["rec_per_page"]) != $this->options["rec_per_page"] || $this->options["rec_per_page"] < 5 || $this->options["rec_per_page"] > 200) $this->errors[] = __('Invalid Records Per Page value. Allowed 5..200', GOURL);

		if (mb_strlen($this->options["popup_message"]) < 15 || mb_strlen($this->options["popup_message"]) > 400) $this->errors[] = __('Invalid Popup Message text size. Allowed 15 - 400 characters text length', GOURL);
		
		if ($this->options["box_style"] && (in_array($this->options["box_style"][0], array("'", "\"")) || $this->options["box_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["box_style"]))) $this->errors[] = __('Invalid Payment Box Style', GOURL);
		if ($this->options["message_style"] && (in_array($this->options["message_style"][0], array("'", "\"")) || $this->options["message_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["message_style"]))) $this->errors[] = __('Invalid Payment Messages Style', GOURL);
		
		return true;
	}
	
	
	
	
	/*
	 *  5.
	*/
	private function save_settings()
	{
		foreach ($this->options as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}
	
		return true;
	}
	
	
	
	
	/*******************************************************************************************/
	
	
	
	/*
	 *  6.
	*/
	private function get_record()
	{
		global $wpdb;
		
		$this->record = array();
	
		if ($this->id && $this->page == GOURL.'file') 
		{	
			$tmp = $wpdb->get_row("SELECT * FROM crypto_files WHERE fileID = ".$this->id." LIMIT 1", ARRAY_A);
			if (!$tmp) { header('Location: '.GOURL_ADMIN.GOURL.'file'); die(); }
		}
		
		// values - from db or default 
		foreach ($this->record_fields as $key => $val) $this->record[$key] = ($this->id) ? $tmp[$key] : $val;
	
		return true;
	}

	
	
	
	/*
	 *  7.
	*/
	private function post_record()
	{
		$this->record = array();

		foreach ($this->record_fields as $key => $val)
		{
			$this->record[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->record[$key])) $this->record[$key] = trim($this->record[$key]);
		}
	
		return true;
	}
		
	
	
	

	/*
	 *  8.
	*/
	private function check_record()
	{
		$this->record_errors = array();
		
		if ($this->page == GOURL.'file')
		{
			if ($this->record["fileID"] != $this->id) $this->record_errors[] = __('Invalid Record ID, Please reload page', GOURL);
			
			// upload file
			$file = (isset($_FILES[GOURL."fileName2"]["name"]) && $_FILES[GOURL."fileName2"]["name"]) ? $_FILES[GOURL."fileName2"] : "";
			if ($file) $this->record["fileName"] = $this->upload_file($file, "files");
			elseif (!$this->record["fileName"])  $this->record_errors[] = __('Your File - cannot be empty', GOURL);
			
			
			// upload featured image
			$file = (isset($_FILES[GOURL."image2"]["name"]) && $_FILES[GOURL."image2"]["name"]) ? $_FILES[GOURL."image2"] : "";
			if ($file) $this->record["image"] = $this->upload_file($file, "images");
			elseif (!$this->record["image"])  $this->record_errors[] = __('Featured Image - select image', GOURL);
			
			
			if (!$this->record["fileTitle"]) 								$this->record_errors[] = __('Title - cannot be empty', GOURL);
			elseif (mb_strlen($this->record["fileTitle"]) > 100) 			$this->record_errors[] = __('Title - Max size 100 symbols', GOURL);
			
			if ($this->record["priceUSD"] == 0)								$this->record_errors[] = __('Price - cannot be empty', GOURL);
			elseif (!is_numeric($this->record["priceUSD"]) || round($this->record["priceUSD"], 2) != $this->record["priceUSD"] || $this->record["priceUSD"] < 0.01 || $this->record["priceUSD"] > 100000) $this->record_errors[] = __('Price - invalid value', GOURL);
			
			if ($this->record["purchases"] && (!is_numeric($this->record["purchases"]) || round($this->record["purchases"]) != $this->record["purchases"] || $this->record["purchases"] < 0)) $this->record_errors[] = __('Purchase Limit - invalid value', GOURL);

			if (!$this->record["expiryPeriod"] || strlen($this->record["expiryPeriod"]) > 15) $this->record_errors[] = __('Expiry Period - cannot be empty', GOURL);
				
			if (!in_array($this->record["userFormat"], $this->store_visitorid)) $this->record_errors[] = __('Store Visitor IDs In - invalid value', GOURL);
			
			if (!isset($this->languages[$this->record["lang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

			if (!$this->record["defCoin"]) $this->record_errors[] = __('Default Coin - cannot be empty', GOURL);
			elseif (!isset($this->coin_names[$this->record["defCoin"]])) $this->record_errors[] = __('Default Coin - invalid value', GOURL);
			elseif (!isset($this->payments[$this->record["defCoin"]])) $this->record_errors[] = sprintf( __('Default Coin - payments in %s not available. Please re-save record', GOURL), $this->coin_names[$this->record["defCoin"]]);
				
			if (!is_numeric($this->record["imageWidth"]) || round($this->record["imageWidth"]) != $this->record["imageWidth"] || $this->record["imageWidth"] < 1 || $this->record["imageWidth"] > 2000) $this->record_errors[] = __('Invalid Image Width. Allowed 1..2,000px', GOURL);
		}
		
		return true;
	}	
	
	
	
	
	/*
	 *  9.
	*/
	private function save_record()
	{
		global $wpdb;
		
		$dt = gmdate('Y-m-d H:i:s');
		
		if ($this->page == GOURL.'file')
		{
			$fileSize = ($this->record['fileName']) ? filesize(GOURL_DIR."files/".$this->record['fileName']) : 0;
			
			if ($this->id)
			{
				$sql = "UPDATE crypto_files 
						SET 
							fileTitle 	= '".esc_sql($this->record['fileTitle'])."', 
							active 		= '".$this->record['active']."', 
							fileName 	= '".esc_sql($this->record['fileName'])."',
							fileText	= '".esc_sql($this->record['fileText'])."',
							fileSize 	= ".$fileSize.",
							priceUSD 	= '".$this->record['priceUSD']."', 
							purchases 	= '".$this->record['purchases']."',
							userFormat 	= '".$this->record['userFormat']."',
							expiryPeriod= '".esc_sql($this->record['expiryPeriod'])."',
							lang 		= '".$this->record['lang']."',
							defCoin		= '".esc_sql($this->record['defCoin'])."',
							defShow 	= '".$this->record['defShow']."',
							image 		= '".esc_sql($this->record['image'])."',
							imageWidth 	= '".$this->record['imageWidth']."',
							priceShow	= '".$this->record['priceShow']."',
							updatetime 	= '".$dt."'
						WHERE fileID 	= ".$this->id."
						LIMIT 1";		 	
			}
			else
			{
				$sql = "INSERT INTO crypto_files (fileTitle, active, fileName, fileText, fileSize, priceUSD, purchases, userFormat, expiryPeriod, lang, defCoin, defShow, image, imageWidth, priceShow, paymentCnt, updatetime, createtime) 
						VALUES (
								'".esc_sql($this->record['fileTitle'])."',
								1,
								'".esc_sql($this->record['fileName'])."',
								'".esc_sql($this->record['fileText'])."',
								".$fileSize.",
								'".$this->record['priceUSD']."',
								'".$this->record['purchases']."',
								'".$this->record['userFormat']."',
								'".esc_sql($this->record['expiryPeriod'])."',
								'".$this->record['lang']."',
								'".esc_sql($this->record['defCoin'])."',
								'".$this->record['defShow']."',
								'".esc_sql($this->record['image'])."',
								'".$this->record['imageWidth']."',
								'".$this->record['priceShow']."',
								0,
								'".$dt."',
								'".$dt."'
							)";
			}
			
		}

		if ($wpdb->query($sql) === false) $this->record_errors[] = "Error in SQL : " . $sql;
		elseif (!$this->id) $this->id = $wpdb->insert_id;
		
		return true;
	}
	
	
	
	
	/*******************************************************************************************/
	
	
	
	/*
	 *  10.
	*/
	private function get_ppv_settings() 
	{
		$this->options2 = array();
		
		foreach ($this->fields_ppv as $key => $value)
		{
			$this->options2[$key] = get_option(GOURL.$key);
			if (!$this->options2[$key])
			{
				if ($value || $key == "ppvImgMaxWidth") $this->options2[$key] = $value; // default
				elseif ($key == "ppvCoin" && $this->payments)
				{
					$values = array_keys($this->payments);
					$this->options2[$key] = array_shift($values); 
				}
			}
			 
		}
		
		return true;
	}

	
	
	/*
	 *  11.
	*/
	private function post_ppv_settings()
	{
		$this->options2 = array();
	
		foreach ($this->fields_ppv as $key => $value)
		{
			$this->options2[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options2[$key])) $this->options2[$key] = trim($this->options2[$key]);
		}
	
		return true;
	}
	
	
	
	/*
	 *  12.
	*/
	private function check_ppv_settings() 
	{
		$this->record_errors = array();
		
		if (!is_numeric($this->options2["ppvPrice"]) || $this->options2["ppvPrice"] < 0.01 || $this->options2["ppvPrice"] > 10000) $this->record_errors[] = __('Pages Access Price - invalid value', GOURL);
		if (!in_array($this->options2["ppvExpiry"], $this->expiry_ppv))	$this->record_errors[] = __('Expiry Period - invalid value', GOURL);	
		if ($this->lock_level_ppv && !in_array($this->options2["ppvLevel"], array_keys($this->lock_level_ppv)))	$this->record_errors[] = __('Lock Page Level - invalid value', GOURL);
		if (!isset($this->languages[$this->options2["ppvLang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

		if (!$this->options2["ppvCoin"]) $this->record_errors[] = __('PaymentBox Default Coin - cannot be empty', GOURL);
		elseif (!isset($this->coin_names[$this->options2["ppvCoin"]])) $this->record_errors[] = __('PaymentBox Default Coin - invalid value', GOURL);
		elseif (!isset($this->payments[$this->options2["ppvCoin"]])) $this->record_errors[] = sprintf( __('PaymentBox Default Coin - payments in %s not available. Please click on "Save Settings" button', GOURL), $this->coin_names[$this->options2["ppvCoin"]]);
		
		
		if (!is_numeric($this->options2["ppvImgMaxWidth"]) ||  round($this->options2["ppvImgMaxWidth"]) != $this->options2["ppvImgMaxWidth"] || $this->options2["ppvImgMaxWidth"] > 2000)	$this->record_errors[] = __('Max Image Width - invalid value', GOURL);	
				
		return true;
	}
	
	
	/*
	 *  13.
	*/
	private function save_ppv_settings()
	{
		foreach ($this->options2 as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}
	
		return true;
	}
	
	
	
	/*******************************************************************************************/
	
	
	
	
	/*
	 *  14.
	*/	
	public function page_settings() 
	{
		
		if ($this->errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
		
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
		
		
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL));
		$tmp .= "<div class='".GOURL."intro postbox'>" . sprintf( __('Simple register on <a target="_blank" href="https://gourl.io/info/memberarea/My_Account.html">GoUrl.io</a> and get your Free Public/Private Payment Box keys. &#160; Start to use on your website - <a href="%s">Pay-Per-Download Files</a> and <a href="%spayperview">Pay-Per-View/Page Video Access</a>. &#160; Make Money Online!', GOURL), GOURL_ADMIN.GOURL, GOURL_ADMIN.GOURL) ."</div>";
		$tmp .= $message;
		
		$tmp .= "<form method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."settings'>";
		
		$tmp .= "<div class='postbox'>"; 
		$tmp .= "<h3 class='hndle'>".__('General Settings', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
		
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_settings" />';
		
		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'settings">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div>';
		
		
		
		$tmp .= "<table class='".GOURL."table ".GOURL."settings'>";
		
		
		foreach ($this->coin_names as $k => $v)
		{
			$v2 = ucfirst($v);
		
			$tmp .= '<tr><th>'.$v2.' '.__('Payments', GOURL).':<br /><a target="_blank" href="https://gourl.io/'.$v.'-payment-gateway-api.html"><img title="'.$v2.' Payment API" src="'.plugins_url('/images/'.$v.'.png', __FILE__).'" border="0"></a></th>';
			$tmp .= '<td>';
			$tmp .= '<div>'.$v2.' '.__('Box Public Key', GOURL).' -</div><input type="text" id="'.GOURL.$v.'public_key" name="'.GOURL.$v.'public_key" value="'.htmlspecialchars($this->options[$v.'public_key'], ENT_QUOTES).'" class="widefat">';
			$tmp .= '<div>'.$v2.' '.__('Box Private Key', GOURL).' -</div><input type="text" id="'.GOURL.$v.'private_key" name="'.GOURL.$v.'private_key" value="'.htmlspecialchars($this->options[$v.'private_key'], ENT_QUOTES).'" class="widefat">';
			$tmp .= '<br /><em>'.__('If you want to start accepting payments in <a target="_blank" href="'.$this->coin_www[$v].'">'.$v2.'s ('.$k.')</a>, please create a <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0/">'.$v2.' Payment Box</a> on GoUrl.io and enter the received GoUrl Public/Private Keys. Leave blank if you do not accept payments in '.$v2.'s', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}
		

		$tmp .= '<tr><th><br />'.__('Payment Box Width', GOURL).':</th>';
		$tmp .= '<td><br /><input class="gourlnumeric" type="text" id="'.GOURL.'box_width" name="'.GOURL.'box_width" value="'.htmlspecialchars($this->options['box_width'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br /><em>'.__('Cryptocoin Payment Box Width, default 520px', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Payment Box Height', GOURL).':</th>';
		$tmp .= '<td><input class="gourlnumeric" type="text" id="'.GOURL.'box_height" name="'.GOURL.'box_height" value="'.htmlspecialchars($this->options['box_height'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br /><em>'.__('Cryptocoin Payment Box Height, default 230px', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Payment Box Style', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'box_style" name="'.GOURL.'box_style" class="widefat" style="height: 60px;">'.htmlspecialchars($this->options['box_style'], ENT_QUOTES).'</textarea><br /><em>'.__('Optional, Payment Box Visual CSS Style.<br />Example: border-radius:15px;border:1px solid #eee;padding:3px 6px;margin:10px', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Payment Messages Style', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'message_style" name="'.GOURL.'message_style" class="widefat" style="height: 50px;">'.htmlspecialchars($this->options['message_style'], ENT_QUOTES).'</textarea><br /><em>'.__('Optional, Payment Notifications Visual CSS Style.<br />Example: display:inline-block;max-width:570px;padding:15px 20px;box-shadow:0 0 3px #aaa;margin:7px;line-height:25px;', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Your Callback Url', GOURL).':</th>';
		$tmp .= '<td><b>'.trim(get_site_url(), "/ ").'/?cryptobox.callback.php</b><br /><br /><em>'.__('IMPORTANT - Please place this Callback URL above in fields "Callback URL (optional)" to all your Payment Box records on website gourl.io', GOURL).'</em><br />';
		$tmp .= '<a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0"><img title="Payment Box Edit - GoUrl.io" src="'.plugins_url('/images/callback_field.png', __FILE__).'" border="0"></a>';
		$tmp .= '</td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th><br />'.__('Records Per Page', GOURL).':</th>';
		$tmp .= '<td><br /><input class="gourlnumeric" type="text" id="'.GOURL.'rec_per_page" name="'.GOURL.'rec_per_page" value="'.htmlspecialchars($this->options['rec_per_page'], ENT_QUOTES).'" class="widefat"><label>'.__('records', GOURL).'</label><br /><em>'.__('Set number of records per page in tables "All Payments" and "All Files"', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th><br />'.__('Popup Message', GOURL).':</th>';
		$tmp .= '<td><br /><input type="text" id="'.GOURL.'popup_message" name="'.GOURL.'popup_message" value="'.htmlspecialchars($this->options['popup_message'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('A pop-up message that a visitor will see when trying to download a paid file without payment<br/>Default text: It is a Paid Download ! Please pay below It', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '</table>';

		$tmp .= '</div></div>';
		$tmp .= '</form>';

		$btcdonation = "1KPBVmXLeY6MCDMPJfKHcTnf4P2SW3b46U";
		$tmp .= "<div class='".GOURL."intro postbox'><div class='inside donation'><table align='center' border='0'><tr><td><a href='bitcoin:".$btcdonation."?label=Donation'><img src='".plugins_url('/images/bitcoin_donate.png', __FILE__)."' border='0'></a></td><td>" . sprintf( __('If you like this GoUrl Bitcoin Plugin, please consider a donation.<br/> Donations are anonymous in bitcoin, and help support future plugin development. <br/> Donation Bitcoin Address - %s', GOURL), "<a href='bitcoin:".$btcdonation."?label=Donation'>".$btcdonation."</a>") ."</td></tr></table></div></div>";
		
		$tmp .= '</div>';
		
		echo $tmp;
							
		return true;							
	}
	
	
	
	
	
	

	/*
	 *  15.
	*/
	public function page_new_file()
	{

		$preview = ($this->id && isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
		
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Record has been saved <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
		
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
		
		
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title($this->id?__('Edit File', GOURL):__('New File', GOURL), 2);
		$tmp .= $message;
		
		
		if ($preview)
		{
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Paid Download #%s Preview', GOURL), $this->id);
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."' class='gourlpreview ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";
			$tmp .= $this->front_shortcode_download(array("id"=>$this->id));
			$tmp .= "</div>";
			$tmp .= '<div class="gourlpreview"><small>'.__('Shortcode', GOURL).': &#160;  ['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]</small></div>';
			$tmp .= "</div>";
		}

		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."file&id=".$this->id."'>";
		
		$tmp .= "<div class='postbox'>";
		$tmp .= "<h3 class='hndle'>".__(($this->id?'Edit file':'Upload New File, Music, Picture, Video'), GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
		
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_file" />';
		
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id && !$preview) $tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&preview=true' class='".GOURL."button button-primary'>".__('Show Preview', GOURL)."</a>".$this->space(2);
		if ($this->id && $this->record['paymentCnt']) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payments&s=file_".$this->id."' class='".GOURL."button button-secondary'>".sprintf(__('Sold %d copies', GOURL), $this->record['paymentCnt'])."</a>".$this->space();
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file">'.__('New File', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file&id='.$this->id.'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'">'.__('All Paid Files', GOURL).'</a>';
		$tmp .= '</div>';
		

		$tmp .= "<table class='".GOURL."table ".GOURL."file'>";
		
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Record ID', GOURL).':</th>';
			$tmp .= '<td>'.$this->record['fileID'].'</td>';
			$tmp .= '</tr>';
			$tmp .= '<tr><th>'.__('Shortcode', GOURL).':</th>';
			$tmp .= '<td><b>['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]</b><br /><em>'.__('<p>Just add this shortcode to any your page or post (in html view) and cryptocoin payment box will be display', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}
		
		$tmp .= '<tr><th>'.__('Title', GOURL).':';
		$tmp .= '<input type="hidden" name="'.GOURL.'fileID" id="'.GOURL.'fileID" value="'.htmlspecialchars($this->record['fileID'], ENT_QUOTES).'">';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'fileTitle" id="'.GOURL.'fileTitle" value="'.htmlspecialchars($this->record['fileTitle'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Title / Friendly name for the file. Visitors will see this title', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Active ?', GOURL).'</th>';
			$tmp .= '<td><input type="checkbox" name="'.GOURL.'active" id="'.GOURL.'active" value="1" '.$this->chk($this->record['active'], 1).' class="widefat"><br /><em>'.__('<p>If box is not checked, visitors cannot pay you for this file', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}	
			
		$tmp .= '<tr><th>'.__('Your File', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'fileName" id="'.GOURL.'fileName" onchange="document.getElementById(\''.GOURL.'fileSize_info\').innerHTML=\'\'; var v=document.getElementById(\''.GOURL.'preview\');v.style.display=(this.value?\'inline\':\'none\');v.title=this.value; v.href=\''.GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'=\'+this.value;">';
		$tmp .= '<option value="">-- '.__('Select pre-uploaded file', GOURL).' --</option>';
		
		
		$files = array();
		if (file_exists(GOURL_DIR."files") && is_dir(GOURL_DIR."files"))
		{
			$all_files = scandir(GOURL_DIR."files");
			for ($i=0; $i<sizeof($all_files); $i++)
				if (!in_array($all_files[$i], array(".", "..", "index.htm", "index.html", "index.php", ".htaccess")) && is_file(GOURL_DIR.'/files/'.$all_files[$i]))
				{
					$files[] = $all_files[$i];
				}
		}
		
		for ($i=0; $i<sizeof($files); $i++)$tmp .= '<option value="'.htmlspecialchars($files[$i], ENT_QUOTES).'"'.$this->sel($files[$i], $this->record['fileName']).'>'.htmlspecialchars($files[$i], ENT_QUOTES).'</option>';
		
		
		$tmp .= "</select>";
		$tmp .= '<label> &#160; <small><a '.($this->record['fileName']?'':'style="display:none"').' id="'.GOURL.'preview" title="'.$this->record['fileName'].'" href="'.GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'='.$this->record['fileName'].'">'.__('Download', GOURL).'</a> <span id="'.GOURL.'fileSize_info">'.($this->record['fileSize']?$this->space(2).__('size', GOURL).': '.gourl_byte_format($this->record['fileSize']):'').'</span></small></label>';
		$tmp .= '<br /><em>'.sprintf(__('If the file has already been uploaded to the server, you can select that file from this drop-down list (folder %sfiles)<br /><strong>OR</strong><br /> upload new file below -', GOURL), GOURL_DIR2).'</em><br /><br />';
		$tmp .= '<input type="file" name="'.GOURL.'fileName2" id="'.GOURL.'fileName2" class="widefat"><br /><em>'.__('Please use simple file names on <b>English</b>. Click on the Choose File button. Locate the file that you want to use, left click on it and click on the Open button. The path of the file that you have selected will appear in the File field', GOURL).'</em>';
		$tmp .= '</td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Price', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'priceUSD" id="'.GOURL.'priceUSD" value="'.htmlspecialchars($this->record['priceUSD'], ENT_QUOTES).'"><label>'.__('USD', GOURL).'</label>';
		$tmp .= '<br /><em>'.__('Please specify file price in USD and payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don\'t need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target="_blank" href="http://goo.gl/L8H9gG">Cryptsy "autosell" feature</a> (auto trade your cryptocoins to USD).', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th>'.__('Show File Name/Price', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'priceShow" id="'.GOURL.'priceShow" value="1" '.$this->chk($this->record['priceShow'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, visitors will see approximate amount in USD and uploaded file name/size', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Purchase Limit', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'purchases" id="'.GOURL.'purchases" value="'.htmlspecialchars($this->record['purchases'], ENT_QUOTES).'"><label>'.__('copies', GOURL).'</label><br /><em>'.__('The maximum number of times a file may be purchased/downloaded. Leave blank or set to 0 for unlimited number of purchases/downloads.', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'expiryPeriod" id="'.GOURL.'expiryPeriod">';
		
		foreach($this->expiry_period as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['expiryPeriod']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown for this file (you can use it to take new payments from users periodically on daily/monthly basis).<br/>If Expiry Period > 2days, please use option - Store Visitor IDs in: "Registered Users"; because If use option "Cookie/Session" below and user clear browser cookies, new payment box will be displayed.', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Store Visitor IDs in', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'userFormat" id="'.GOURL.'userFormat">';

		foreach($this->store_visitorid as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['userFormat']).'>'.($v=="MANUAL"?"Registered Users":$v).'</option>';

		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('For Unregistered Website Visitors - Save auto-generated unique visitor ID in cookies, sessions or use the IP address to decide unique visitors (without use cookies).<br />If you use "session", value in Expiry Period will be ignored.  A PHP session cookie has a default life time until the browser is closed.<br/>-----<br/>If you have registration on the website enabled, <u>please use option "Registered Users"</u> - only registered users can pay/download this file (Gourl will use wordpress userID instead of cookies for user identification)<br/>It is much better to use "Registered users" than "Cookie/Session/Ipaddress"', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		
		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'lang" id="'.GOURL.'lang">';
		
		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['lang']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';
		

		
		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'defCoin" id="'.GOURL.'defCoin">';
		
		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['defCoin']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br /><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		
		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'defShow" id="'.GOURL.'defShow" value="1" '.$this->chk($this->record['defShow'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" for this file (no multiple coins)', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Description (Optional)', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->record['fileText'], GOURL.'fileText', array('textarea_name' => GOURL.'fileText', 'quicktags' => true, 'media_buttons' => false, 'textarea_rows' => 8));
		$tmp  = '<br /><em>'.__('Short File Description', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		
		
		$tmp .= '<tr><th>'.__('Featured Image', GOURL).':</th><td>';
		
		if (file_exists(GOURL_DIR."images") && is_dir(GOURL_DIR."images"))
		{
			$arr = scandir(GOURL_DIR."images/");
			sort($arr);
			foreach ($arr as $v)
				if (in_array(substr($v, -4), array(".png", ".jpg", ".jpeg", ".gif")))
				{
					$tmp .= '<div class="gourlimagebox"><input type="radio" name="'.GOURL.'image" id="'.$v.'" value="'.$v.'"'.$this->chk($this->record['image'], $v).'><label for="'.$v.'"><img width="100" src="'.GOURL_DIR2."images/".$v.'" border="0"></label></div>';
				}
		}
			
		$tmp .= '<div class="clear"></div>';
		$tmp .= '... '.__('OR', GOURL).' ...';
		$tmp .= '<div class="clear"></div>';
		$tmp .= '<div class="gourlimagebox"><input type="radio" name="'.GOURL.'image" value=""'.$this->chk($this->record['image'], '').'>'.__('Custom Featured Image', GOURL).'<br />';
		$tmp .= '<input type="file" accept="image/*" id="'.GOURL.'image2" name="'.GOURL.'image2" class="widefat"><br /><em>'.__('This featured image represent your uploaded file above. Max sizes: 800px x 600px, allowed images: JPG, GIF, PNG.', GOURL).'</em></div>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Image Width', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'imageWidth" id="'.GOURL.'imageWidth" value="'.htmlspecialchars($this->record['imageWidth'], ENT_QUOTES).'"><label>'.__('px', GOURL).'</label><br /><em>'.__('Your featured image width', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		if ($this->id)
		{	
			$tmp .= '<tr><th>'.__('Total Sold', GOURL).':</th>';
			$tmp .= '<td><b>'.$this->record['paymentCnt'].' '.__('copies', GOURL).'</b></td>';
			$tmp .= '</tr>';
				
			if ($this->record['paymentCnt'])
			{
				$tmp .= '<tr><th>'.__('Latest Received Payment', GOURL).':</th>';
				$tmp .= '<td><b>'.date('d M Y, H:i:s a', strtotime($this->record['paymentTime'])).' GMT</b></td>';
				$tmp .= '</tr>';
			}
				
			if ($this->record['updatetime'] != $this->record['createtime'])
			{
				$tmp .= '<tr><th>'.__('Record Updated', GOURL).':</th>';
				$tmp .= '<td>'.date('d M Y, H:i:s a', strtotime($this->record['updatetime'])).' GMT</td>';
				$tmp .= '</tr>';
			}
			
			$tmp .= '<tr><th>'.__('Record Created', GOURL).':</th>';
			$tmp .= '<td>'.date('d M Y, H:i:s a', strtotime($this->record['createtime'])).' GMT</td>';
			$tmp .= '</tr>';
		
		}
		
		$tmp .= '</table>';
		
		
		$tmp .= '</div></div>';
		$tmp .= '</form></div>';
		
		echo $tmp;
		
		return true;
	}	
	
	
	
	
	
	
	
	/*
	 *  16.
	*/
	public function page_files()
	{
		global $wpdb;
		
		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = esc_sql(trim($_GET["s"]));
			if (strtolower($s) == "registered users") $s = "MANUAL";
			if (in_array(strtolower($s), $this->coin_names)) $s = array_search(strtolower($s), $this->coin_names);
			if (in_array(ucwords(strtolower($s)), $this->languages)) $s = array_search(ucwords(strtolower($s)), $this->languages);
			if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
			$search = " && (fileTitle LIKE '%".$s."%' || fileName LIKE '%".$s."%' || fileText LIKE '%".$s."%' || priceUSD LIKE '%".$s."%' || userFormat LIKE '%".$s."%' || expiryPeriod LIKE '%".$s."%' || defCoin LIKE '%".$s."%' || image LIKE '%".$s."%' || imageWidth LIKE '%".$s."%' || paymentCnt LIKE '%".$s."%' || lang LIKE '%".$s."%' || DATE_FORMAT(createtime, '%d %M %Y') LIKE '%".$s."%')";
		}
		
		
		$res = $wpdb->get_row("SELECT sum(paymentCnt) as total from crypto_files WHERE paymentCnt > 0".$search);
		$total = (int)$res->total; 
		
		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active != 0".$search);
		$active = (int)$res->cnt; 
		
		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active = 0".$search);
		$inactive = (int)$res->cnt; 
		
		
		$wp_list_table = new  gourltable_files($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();
		
		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Paid Files', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'file">' . __('Add New File', GOURL) . '</a>', 2);
		echo "<div class='".GOURL."intro postbox'>";
		echo  '<a style="float:right" target="_blank" href="https://gourl.io/lib/examples/pay-per-download-multi.php"><img title="Example - Pay Per Download" src="'.plugins_url('/images/pay-per-download.png', __FILE__).'" border="0"></a>';
		echo  __('Easily Sell Files, Videos, Music, Photos, Software (digital downloads) on your WordPress site/blog and accept <b>Bitcoin</b>, Litecoin, Dogecoin, Speedcoin, Darkcoin, Vertcoin, Reddcoin, Feathercoin, Vericoin, Potcoin payments online. No Chargebacks, Global, Secure. Anonymous Bitcoins & Cryptocurrency Payments. All in automatic mode.', GOURL);
		echo '<br /><br />';
		echo  __('<b>Simple Instruction :</b>', GOURL);
		echo '<ol>';	
		echo  '<li>'.__('<a target="_blank" href="https://gourl.io/view/registration/New_User.html">Register</a> or <a target="_blank" href="https://gourl.io/info/memberarea/My_Account.html">login</a> on GoUrl.io and create Payment Box Records for all coins which you will accept on your website.', GOURL).'</li>';
		echo  '<li>'.__('For <b>Callback URL</b> use: ', GOURL).'<b>'.trim(get_site_url(), "/ ").'/?cryptobox.callback.php</b></li>';
		echo  '<li>'.sprintf(__('You will get Free GoUrl Public/Private keys, save them on  <a href="%ssettings">Settings</a> page', GOURL), GOURL_ADMIN.GOURL).'</li>';
		echo  '<li>'.sprintf(__('Create <a href="%sfile">Paid File Downloads</a> and place new generated shortcode on your public page/post. Done!', GOURL), GOURL_ADMIN.GOURL).'</li>';
				echo '</ol>';
		echo  "</div>";
		
		echo '<form style="float:left" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
		
		echo "<div class='".GOURL."tablestats'>";
		echo "<b>" . __('Total Files', GOURL). ":</b> " . ($active+$inactive) . " " . __('files', GOURL) . $this->space(1) . "( ";
		echo "<b>" . __('Active', GOURL). ":</b> " . $active . " " . __('files', GOURL) . $this->space(2);
		echo "<b>" . __('Inactive', GOURL). ":</b> " . $inactive . " " . __('files', GOURL) . $this->space(1) . ")" . $this->space(4);
		echo "<b>" . __('Total Sold', GOURL). ":</b> " . $total . " " . __('files', GOURL) . $this->space(4);
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		
		echo '<div class="'.GOURL.'widetable">';
		echo '<div style="min-width:1640px;width:100%;">';
		
		$wp_list_table->display();
		
		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
		
		return true;
	}
	
	
	
	
	
	
	/*
	 *  17.
	*/
	public function page_payperview()
	{
		$example = 0;
		$preview = (isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
		
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Pay-Per-View Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
	
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Pay-Per-View Settings', GOURL), 3);

		
		if ($preview)
		{
			$example = ($_GET["example"] == "2") ? 2 : 1;
			$tag 	 =  '['.GOURL_TAG_LOCK.' img="image'.$example.'.jpg"]';
			
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".__('Preview for', GOURL).$this->space().$tag;
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview' class='gourlpreview ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";
			$tmp .= $this->front_shortcode_payview(array("img"=>"image".$example.".jpg"));
			$tmp .= "</div>";
			$tmp .= '<div class="gourlpreview"><small>'.__('Shortcode', GOURL).': &#160; '.$tag.'</small></div>';
			$tmp .= "</div>";
		}
		else
		{	
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= "<div style='float:right'>";
			$tmp .= "<div align='center'>";
			$tmp .= '<a target="_blank" href="https://gourl.io/lib/examples/pay-per-page-multi.php"><img title="Example - Pay Per View - Video/Page Access for Unregistered Visitors" src="'.plugins_url('/images/pay-per-page.png', __FILE__).'" border="0"></a>';
			$tmp .= "<br/><br/>".__('Buttons For Your Website: ', GOURL)."<br/>";
			$tmp .= '<img src="'.plugins_url('/images/bitcoin_accepted.png', __FILE__).'" border="0"><br/>';
			$tmp .= '<img src="'.plugins_url('/images/btc_accepted.png', __FILE__).'" border="0">';
			$tmp .= "</div>";
			$tmp .= "</div>";
			$tmp .= __('<b>Pay-Per-View Summary</b> - Your unregistered anonymous website visitors  will need to send you a set amount of cryptocoins for access to your website\'s specific pages & videos during a specific time. All will be in automatic mode - allowing you to receive payments, open webpage access to your visitors, showing after the time a new payment form, payment notifications to your email, etc.', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= __('For example, you might offer paid access to 20 (twenty) of your website pages/posts for the price of 1 USD for 1 DAY giving unlimited access to all locked pages for website visitors (non-registered users or registered subscribers). You can add  simple shortcode below for all those twenty WordPress pages/posts. When visitors to on any of those pages, they will see automatic cryptocoin payment box (the original page content will be hidden). After visitor makes his payment, he will get access to original pages content/videos and after 24 hours  will see a new payment box. Visitor needs to make payment on any locked page and he will get access to all other locked pages also. Website Editors / Admins will have all the time full access to locked pages and see original page content.', GOURL);
			$tmp .= ' <a target="_blank" href="https://gourl.io/lib/examples/pay-per-page-multi.php">'.__('See Example', GOURL).'</a>';
			$tmp .= "<br /><br />";
			$tmp .= __('You can customize lock image for each page or no images at all. Default image directory: <b>'.GOURL_DIR2.'lockimg</b> or use full image path (http://...)', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= __('Shortcode: ', GOURL);
			$tmp .= '<span class="gourlshortcode">['.GOURL_TAG_LOCK.' img="image1.jpg"]</span>';
			$tmp .= __('- place this tag anywhere in the original text on your premium pages/posts', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= __('Ready to use shortcodes: ', GOURL);
			$tmp .= "<ol>";
			$tmp .= '<li>['.GOURL_TAG_LOCK.' img="image1.jpg"] &#160; - <small>'.__('lock page with default page lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_LOCK.' img="image2.jpg"] &#160; - <small>'.__('lock page with default video lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_LOCK.' img="my_image_etc.jpg"] &#160; - <small>'.sprintf(__('lock page with any custom lock image stored in directory %slockimg', GOURL), GOURL_DIR2).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_LOCK.' img="http://....."] &#160; - <small>'.__('lock page with any custom lock image', GOURL).'</small></li>';
			$tmp .= "</ol>";
			$tmp .= "</div>";
		}
		
		$tmp .= $message;
		
		

	
		$tmp .= "<form method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."payperview'>";
	
		$tmp .= "<div class='postbox'>";
		$tmp .= "<h3 class='hndle'>".__('Paid Access to Selected Pages for Unregistered Visitors', GOURL).$this->space(2)."<small>" . __('(optional - for subscribers, contributors also)', GOURL) . "</small></h3>";
		$tmp .= "<div class='inside'>";
		
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_payperview" />';
		
		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($example != 2 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=2&preview=true' class='".GOURL."button button-primary'>".__('Show Preview 1', GOURL)."</a>";
		if ($example != 1 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=1&preview=true' class='".GOURL."button button-primary'>".__('Show Preview 2', GOURL)."</a>";
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'payperview">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div>';
		
		
		
		$tmp .= "<table class='".GOURL."table ".GOURL."payperview'>";
	

		$tmp .= '<tr><th>'.__('Pages Access Price', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'ppvPrice" id="'.GOURL.'ppvPrice" value="'.htmlspecialchars($this->options2['ppvPrice'], ENT_QUOTES).'"><label>'.__('USD', GOURL).'</label>';
		$tmp .= '<br /><em>'.__('Please specify pages access price in USD and payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min)', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvExpiry" id="'.GOURL.'ppvExpiry">';
		
		foreach($this->expiry_ppv as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->options2['ppvExpiry']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown (you can use it to take new payments from users periodically on daily basis.
								We uses randomly generated string as user identification and this is saved in user cookies. If user clear browser cookies, new payment box will be displayed. Therefore max expiry period is 2 DAYS', GOURL).'</em></td>';
		$tmp .= '</tr>';
		

		$tmp .= '<tr><th>'.__('Lock Page Level', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvLevel" id="'.GOURL.'ppvLevel">';
		
		foreach($this->lock_level_ppv as $k=>$v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvLevel']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Select Visitors/Users level who will see lock page/blog contents and need to make payment for unlock.
								 Website Editors / Admins will have all the time full access to locked pages and see original page content.', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		
		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvLang" id="'.GOURL.'ppvLang">';
		
		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvLang']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvCoin" id="'.GOURL.'ppvCoin">';
		
		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvCoin']).'>'.$v.'</option>';
		
		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br /><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvOneCoin" id="'.GOURL.'ppvOneCoin" value="1" '.$this->chk($this->options2['ppvOneCoin'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" (no multiple coins)', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Max Image Width', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'ppvImgMaxWidth" id="'.GOURL.'ppvImgMaxWidth" value="'.htmlspecialchars($this->options2['ppvImgMaxWidth'], ENT_QUOTES).'"><label>'.__('px', GOURL).'</label><br /><em>'.__('Optional, Set the maximum width of your custom lock images in [gourl-lock img="my_image_etc.jpg"] or use "0" - if you don\'t want to use it', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '<tr><th>'.__('Text - Above Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextAbove'], GOURL.'ppvTextAbove', array('textarea_name' => GOURL.'ppvTextAbove', 'quicktags' => true, 'media_buttons' => true));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Above</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		
		$tmp .= '<tr><th>'.__('Text - Below Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextBelow'], GOURL.'ppvTextBelow', array('textarea_name' => GOURL.'ppvTextBelow', 'quicktags' => true, 'media_buttons' => true));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Below</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th>'.__('Hide All Titles ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvTitle" id="'.GOURL.'ppvTitle" value="1" '.$this->chk($this->options2['ppvTitle'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid visitors/users will not see any link titles on page', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Hide Comments Authors ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentAuthor" id="'.GOURL.'ppvCommentAuthor" value="1" '.$this->chk($this->options2['ppvCommentAuthor'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid visitors/users will not see authors of comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Hide Comments Body ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentBody" id="'.GOURL.'ppvCommentBody" value="1" '.$this->chk($this->options2['ppvCommentBody'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid visitors/users will not see comments body on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Disable Comments Reply ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentReply" id="'.GOURL.'ppvCommentReply" value="1" '.$this->chk($this->options2['ppvCommentReply'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid visitors/users cannot reply/add comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		
		$tmp .= '</table>';
	
	
		$tmp .= '</div></div>';
		$tmp .= '</form></div>';
	
		echo $tmp;
		
		return true;
	}
	
	

	
		
	
	

	/*
	 *  18.
	*/
	public function page_payments()
	{
		global $wpdb;
		
		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = trim($_GET["s"]);
			
			if ($s == "payperview") $search = " && orderID = 'payperview'";
			elseif ((strpos($s, "file_") === 0 || strpos($s, "user_") === 0) && is_numeric(substr($s, 5))) 
			{
				$m = substr($s, 0, 5);
				$s = intval(substr($s, 5)); 
				if ($s) $search = " && ".($m=="user_"?"userID":"orderID")." = '".$m.$s."'"; else $s = trim($_GET["s"]); 
			}
			
			if (!$search)
			{
				include_once(plugin_dir_path( __FILE__ )."cryptobox.class.php");
				
				$key = array_search(strtolower($s), array("afg"=>"afghanistan", "ala"=>"aland islands", "alb"=>"albania", "dza"=>"algeria", "asm"=>"american samoa", "and"=>"andorra", "ago"=>"angola", "aia"=>"anguilla", "ata"=>"antarctica", "atg"=>"antigua and barbuda", "arg"=>"argentina", "arm"=>"armenia", "abw"=>"aruba", "aus"=>"australia", "aut"=>"austria", "aze"=>"azerbaijan", "bhs"=>"bahamas", "bhr"=>"bahrain", "bgd"=>"bangladesh", "brb"=>"barbados", "blr"=>"belarus", "bel"=>"belgium", "blz"=>"belize", "ben"=>"benin", "bmu"=>"bermuda", "btn"=>"bhutan", "bol"=>"bolivia", "bih"=>"bosnia and herzegovina", "bwa"=>"botswana", "bvt"=>"bouvet island", "bra"=>"brazil", "iot"=>"british indian ocean territory", "brn"=>"brunei", "bgr"=>"bulgaria", "bfa"=>"burkina faso", "bdi"=>"burundi", "khm"=>"cambodia", "cmr"=>"cameroon", "can"=>"canada", "cpv"=>"cape verde", "bes"=>"caribbean netherlands", "cym"=>"cayman islands", "caf"=>"central african republic", "tcd"=>"chad", "chl"=>"chile", "chn"=>"china", "cxr"=>"christmas island", "cck"=>"cocos (keeling) islands", "col"=>"colombia", "com"=>"comoros", "cog"=>"congo", "cod"=>"congo, democratic republic", "cok"=>"cook islands", "cri"=>"costa rica", "civ"=>"cгґte dвђ™ivoire", "hrv"=>"croatia", "cub"=>"cuba", "cuw"=>"curacao", "cbr"=>"cyberbunker", "cyp"=>"cyprus", "cze"=>"czech republic", "dnk"=>"denmark", "dji"=>"djibouti", "dma"=>"dominica", "dom"=>"dominican republic", "tmp"=>"east timor", "ecu"=>"ecuador", "egy"=>"egypt", "slv"=>"el salvador", "gnq"=>"equatorial guinea", "eri"=>"eritrea", "est"=>"estonia", "eth"=>"ethiopia", "eur"=>"european union", "flk"=>"falkland islands", "fro"=>"faroe islands", "fji"=>"fiji islands", "fin"=>"finland", "fra"=>"france", "guf"=>"french guiana", "pyf"=>"french polynesia", "atf"=>"french southern territories", "gab"=>"gabon", "gmb"=>"gambia", "geo"=>"georgia", "deu"=>"germany", "gha"=>"ghana", "gib"=>"gibraltar", "grc"=>"greece", "grl"=>"greenland", "grd"=>"grenada", "glp"=>"guadeloupe", "gum"=>"guam", "gtm"=>"guatemala", "ggy"=>"guernsey", "gin"=>"guinea", "gnb"=>"guinea-bissau", "guy"=>"guyana", "hti"=>"haiti", "hmd"=>"heard island and mcdonald islands", "hnd"=>"honduras", "hkg"=>"hong kong", "hun"=>"hungary", "isl"=>"iceland", "ind"=>"india", "idn"=>"indonesia", "irn"=>"iran", "irq"=>"iraq", "irl"=>"ireland", "imn"=>"isle of man", "isr"=>"israel", "ita"=>"italy", "jam"=>"jamaica", "jpn"=>"japan", "jey"=>"jersey", "jor"=>"jordan", "kaz"=>"kazakstan", "ken"=>"kenya", "kir"=>"kiribati", "kwt"=>"kuwait", "kgz"=>"kyrgyzstan", "lao"=>"laos", "lva"=>"latvia", "lbn"=>"lebanon", "lso"=>"lesotho", "lbr"=>"liberia", "lby"=>"libya", "lie"=>"liechtenstein", "ltu"=>"lithuania", "lux"=>"luxembourg", "mac"=>"macao", "mkd"=>"macedonia", "mdg"=>"madagascar", "mwi"=>"malawi", "mys"=>"malaysia", "mdv"=>"maldives", "mli"=>"mali", "mlt"=>"malta", "mhl"=>"marshall islands", "mtq"=>"martinique", "mrt"=>"mauritania", "mus"=>"mauritius", "myt"=>"mayotte", "mex"=>"mexico", "fsm"=>"micronesia, federated states", "mda"=>"moldova", "mco"=>"monaco", "mng"=>"mongolia", "mne"=>"montenegro", "msr"=>"montserrat", "mar"=>"morocco", "moz"=>"mozambique", "mmr"=>"myanmar", "nam"=>"namibia", "nru"=>"nauru", "npl"=>"nepal", "nld"=>"netherlands", "ant"=>"netherlands antilles", "ncl"=>"new caledonia", "nzl"=>"new zealand", "nic"=>"nicaragua", "ner"=>"niger", "nga"=>"nigeria", "niu"=>"niue", "nfk"=>"norfolk island", "prk"=>"north korea", "mnp"=>"northern mariana islands", "nor"=>"norway", "omn"=>"oman", "pak"=>"pakistan", "plw"=>"palau", "pse"=>"palestine", "pan"=>"panama", "png"=>"papua new guinea", "pry"=>"paraguay", "per"=>"peru", "phl"=>"philippines", "pcn"=>"pitcairn", "pol"=>"poland", "prt"=>"portugal", "pri"=>"puerto rico", "qat"=>"qatar", "reu"=>"rг©union", "rom"=>"romania", "rus"=>"russia", "rwa"=>"rwanda", "blm"=>"saint barthelemy", "shn"=>"saint helena", "kna"=>"saint kitts and nevis", "lca"=>"saint lucia", "maf"=>"saint martin", "spm"=>"saint pierre and miquelon", "vct"=>"saint vincent and the grenadines", "wsm"=>"samoa", "smr"=>"san marino", "stp"=>"sao tome and principe", "sau"=>"saudi arabia", "sen"=>"senegal", "srb"=>"serbia", "syc"=>"seychelles", "sle"=>"sierra leone", "sgp"=>"singapore", "sxm"=>"sint maarten", "svk"=>"slovakia", "svn"=>"slovenia", "slb"=>"solomon islands", "som"=>"somalia", "zaf"=>"south africa", "sgs"=>"south georgia and the south sandwich islands", "kor"=>"south korea", "ssd"=>"south sudan", "esp"=>"spain", "lka"=>"sri lanka", "sdn"=>"sudan", "sur"=>"suriname", "sjm"=>"svalbard and jan mayen", "swz"=>"swaziland", "swe"=>"sweden", "che"=>"switzerland", "syr"=>"syria", "twn"=>"taiwan", "tjk"=>"tajikistan", "tza"=>"tanzania", "tha"=>"thailand", "tgo"=>"togo", "tkl"=>"tokelau", "ton"=>"tonga", "tto"=>"trinidad and tobago", "tun"=>"tunisia", "tur"=>"turkey", "tkm"=>"turkmenistan", "tca"=>"turks and caicos islands", "tuv"=>"tuvalu", "uga"=>"uganda", "ukr"=>"ukraine", "are"=>"united arab emirates", "gbr"=>"united kingdom", "umi"=>"united states minor outlying islands", "ury"=>"uruguay", "usa"=>"usa", "uzb"=>"uzbekistan", "vut"=>"vanuatu", "vat"=>"vatican (holy see)", "ven"=>"venezuela", "vnm"=>"vietnam", "vgb"=>"virgin islands, british", "vir"=>"virgin islands, u.s.", "wlf"=>"wallis and futuna", "esh"=>"western sahara", "yem"=>"yemen", "zmb"=>"zambia", "zwe"=>"zimbabwe"));
				if ($key !== false) $s = strtoupper($key);
				elseif (in_array(ucfirst(strtolower($s)), $this->coin_names)) $s = array_search(strtolower($s), $this->coin_names);
				elseif (in_array(strtolower($s), $this->coin_names)) $s = array_search(strtolower($s), $this->coin_names);
				elseif (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
				$s = esc_sql($s);
				$search = " && (orderID LIKE '%".$s."%' || userID LIKE '%".$s."%' || countryID LIKE '%".$s."%' || coinLabel LIKE '%".$s."%' || amount LIKE '%".$s."%' || amountUSD LIKE '%".$s."%' || addr LIKE '%".$s."%' || txID LIKE '%".$s."%' || DATE_FORMAT(txDate, '%d %M %Y') LIKE '%".$s."%')";
			}
		}	
		
		
		$res = $wpdb->get_row("SELECT sum(amountUSD) as total from crypto_payments WHERE 1".$search);
		$total = $res->total; 
		$total = number_format($total, 2);
		if (strpos($total, ".")) $num = rtrim(rtrim($total, "0"), ".");
		
		$res = $wpdb->get_row("SELECT DATE_FORMAT(txDate, '%d %M %Y, %H:%i %p') as latest from crypto_payments WHERE 1".$search." ORDER BY txDate DESC LIMIT 1");
		$latest = ($res) ? $res->latest . " " . __('GMT', GOURL) : "";
		
		
		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised = 0".$search);
		$recognised = (int)$res->cnt; 
		
		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised != 0".$search);
		$unrecognised = (int)$res->cnt; 
		
		
		$wp_list_table = new  gourltable_payments($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();
		
		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Received Payments', GOURL));
		
		echo '<form style="float:left" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
		
		echo "<div class='".GOURL."tablestats2'>";
		echo "<span><b>" . __('Total Received', GOURL). ":</b> " . number_format($recognised+$unrecognised) . " " . __('payments', GOURL) . $this->space(1) . "</span> <span><small>( ";
		echo "<b>" . __('Recognised', GOURL). ":</b> " . number_format($recognised) . " " . __('payments', GOURL) . $this->space(1);
		echo "<b>" . __('Unrecognised', GOURL). ":</b> " . number_format($unrecognised) . " " . __('payments', GOURL) . " )</small></span>" . $this->space(4);
		echo "<span><b>" . __('Total Sum', GOURL). ":</b> " . $total . " " . __('USD', GOURL) . "</span>" . $this->space(4);
		echo "<span><b>" . __('Latest Payment', GOURL). ":</b> " . $latest . "</span>";
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."payments'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		
		echo '<div class="'.GOURL.'widetable">';
		echo '<div style="min-width:1640px;width:100%;">';
		
		$wp_list_table->display();
		
		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
		
		return true;
	}
	

	
	/**************************************************************/
	

	
	
	
	/*
	 *  19.
	*/
	public function  front_init()
	{
		ob_start();
		
		return true;
	}
	
	
	
	
	/*
	 *  20.
	*/
	public function front_header()
	{
		echo '<script src="'.plugins_url('/js/cryptobox.min.js?ver='.GOURL_VERSION, __FILE__).'" type="text/javascript"></script>
			  <link rel="stylesheet" type="text/css" href="'.plugins_url('/css/style.front.css?ver='.GOURL_VERSION, __FILE__).'" media="all" />';
	
		return true;
	}
	
	
	
	
	
	
	/*
	 *  21.
	*/
	public function front_shortcode_download($arr)
	{
		global $wpdb, $current_user;

		// not available activated coins
		if (!$this->payments) return "";
		
		if (!isset($arr["id"]) || !intval($arr["id"])) return '<div>'.__('Invalid format. Use &#160; ['.GOURL_TAG_DOWNLOAD.' id="..id.."]', GOURL).'</div>';

		$id 			= intval($arr["id"]);
		$short_code 	= '['.GOURL_TAG_DOWNLOAD.' id="<b>'.$id.'</b>"]';
		$download_key	= 'gourldownload_file';
		
		
		$is_paid		= false;
		$coins_list 	= "";	
		$languages_list	= "";
		
		
		// Current File Info
		// --------------------------
		$arr = $wpdb->get_row("SELECT * FROM crypto_files WHERE fileID = ".$id." LIMIT 1", ARRAY_A);
		if (!$arr) return '<div>'.__('Invalid record id "'.$id.'" - ', GOURL).$short_code.'</div>';

		
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->options["box_style"];
		$message_style	= $this->options["message_style"];
		
		$active 		= $arr["active"];
		$fileTitle 		= $arr["fileTitle"];
		$fileName 		= $arr["fileName"];
		$fileText 		= $arr["fileText"];
		$fileSize 		= $arr["fileSize"];
		$priceUSD 		= $arr["priceUSD"];
		$purchases 		= $arr["purchases"];
		$userFormat 	= $arr["userFormat"];
		$expiryPeriod	= $arr["expiryPeriod"];
		$lang 			= $arr["lang"];
		$defCoin		= $this->coin_names[$arr["defCoin"]];
		$defShow		= $arr["defShow"];
		$image			= $arr["image"];
		$imageWidth		= $arr["imageWidth"];
		$priceShow		= $arr["priceShow"];
		$paymentCnt		= $arr["paymentCnt"];
		$paymentTime	= $arr["paymentTime"];
		$updatetime		= $arr["updatetime"];
		$createtime		= $arr["createtime"];
		$userID 		= ($userFormat == "MANUAL" ? "user_".$current_user->ID : "");
		$orderID 		= "file_".$id; // file_+fileID as orderID
		$filePath 		= GOURL_DIR."files/".$fileName;
		$anchor 		= "gbx".$this->icrc32($id);
		
		if (strip_tags(mb_strlen($fileText)) < 5) $fileText = '';
		
		
		// Registered Users can Pay Only
		// --------------------------
		
		if ($userFormat == "MANUAL" && (!is_user_logged_in() || !$current_user->ID))
		{
			$box_html = "<img width='527' alt='".__('Please register or login to download this file', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_login.png' border='0'><br /><br />";
			$download_link = "onclick='alert(\"".__('Please register or login to download this file', GOURL)."\")' href='#a'";
		}
		else if (!$fileName || !file_exists($filePath) || !is_file($filePath))
		{
			$box_html = "<img width='527' alt='".__('File does not exist on the server', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_nofile.png' border='0'><br /><br />";
			$download_link = "onclick='alert(\"".__('Error! File does not exist on the server !', GOURL)."\")' href='#a'";
		}
		else
		{	
			
			// GoUrl Payments
			// --------------------------
			
			$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
			$available_coins 		= array(); 		// List of coins that you accept for payments
			$cryptobox_private_keys = array();		// List Of your private keys
			
			foreach ($this->coin_names as $k => $v)
			{
				$public_key 	= $this->options[$v.'public_key'];
				$private_key 	= $this->options[$v.'private_key'];
	
				if ($public_key && !strpos($public_key, "PUB"))    return '<div>'.sprintf(__('Invalid %s Public Key %s - ', GOURL), $v, $public_key).$short_code.'</div>';
				if ($private_key && !strpos($private_key, "PRV"))  return '<div>'.sprintf(__('Invalid %s Private Key - ', GOURL), $v).$short_code.'</div>';
								
				if ($private_key) $cryptobox_private_keys[] = $private_key;
				if ($private_key && $public_key && (!$defShow || $v == $defCoin)) 
				{
					$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
					$available_coins[] = $v;
				}
			}
			
			if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
			
			if (!$available_coins) return '<div>'.__('No Available Payments - ', GOURL).$short_code.'</div>';
			
			if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }
				
			
			
			
			/// GoUrl Payment Class
			// --------------------------
			include_once(plugin_dir_path( __FILE__ )."cryptobox.class.php");
			
			
			
			// Current selected coin by user
			$coinName = cryptobox_selcoin($available_coins, $defCoin);
			
			
			// Current Coin public/private keys
			$public_key  = $all_keys[$coinName]["public_key"];
			$private_key = $all_keys[$coinName]["private_key"];
			
			
			// PAYMENT BOX CONFIG
			$options = array(
					"public_key"  => $public_key, 		// your box public key
					"private_key" => $private_key, 		// your box private key
					"orderID"     => $orderID, 			// file name hash as order id
					"userID"      => $userID, 			// unique identifier for each your user
					"userFormat"  => $userFormat, 		// save userID in 
					"amount"   	  => 0,					// file price in USD below
					"amountUSD"   => $priceUSD,			// we use file price in USD
					"period"      => $expiryPeriod, 	// download link valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);
			
			
			
			// Initialise Payment Class
			$box = new Cryptobox ($options);
			

			// Coin name
			$coinName = $box->coin_name();
				
			
			// Paid or not
			$is_paid = $box->is_paid();
				
			// $is_paid = true;
			// Download Link
			if ($is_paid)
			{
				$get_arr = $_GET;
				if (isset($get_arr[$download_key])) unset($get_arr[$download_key]);
				$download_link = 'href="'.$this->left($_SERVER["REQUEST_URI"], "?")."?".http_build_query($get_arr).($get_arr?"&amp;":"").$download_key."=".$this->icrc32($orderID).'"';
			}
			else 
			{
				$download_link = "onclick='alert(\"".htmlspecialchars($this->options['popup_message'], ENT_QUOTES)."\")' href='#".$anchor."'";
			}
			
			
			
			
			
			// Payment Box HTML
			// ----------------------
			if (!$is_paid && $purchases > 0 && $paymentCnt >= $purchases)
			{
				// A. Sold
				$box_html = "<img width='527' alt='".__('Sold Out', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_sold.png' border='0'><br /><br />";
			
			}
			elseif (!$is_paid && !$active)
			{
				// B. Box Not Active
				$box_html = "<img width='527' alt='".__('Cryptcoin Payment Box Disabled', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_disabled.png' border='0'><br /><br />";
			}
			else 
			{
				// Coins selection list (html code)
				$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "", plugins_url('/images', __FILE__), $anchor) : "";
				
				
				// Language selection list for payment box (html code)
				$languages_list = display_language_box($lang, $anchor);
				
				
				// C. Active Box
				$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
			}
			
			

			// User Paid and Start To Download File - Send file to user browser
			// ---------------------
			if ($is_paid && isset($_GET[$download_key]) && $_GET[$download_key] == $this->icrc32($orderID))
			{
				// Starting Download
				$this->download_file($filePath);
				
				// Set Status - User Downloaded File
				$box->set_status_processed();
				
				// Flush Cache
				ob_flush();
					
				die;
			}
		}

		
		
		
		// Html code
		// ---------------------
		
		$tmp  = "<div class='gourlbox' style='min-width:".$box_width."px'>";
		$tmp .= "<h1>".htmlspecialchars($fileTitle, ENT_QUOTES)."</h1>";
		
		// Display Price in USD
		if ($priceShow)
		{
			$tmp .= "<h3> &#160; ".__('File', GOURL).": &#160; <a class='gourlfilename' style='text-decoration:none;color:inherit;' ".$download_link.">".$fileName."</a>".$this->space(2)."<small style='white-space:nowrap'>".__('size', GOURL).": ".gourl_byte_format($fileSize)."</small></h3>";
			$tmp .= "<div class='gourlprice'>".__('Price', GOURL).": ~".$priceUSD." ".__('USD', GOURL)."</div>";
		}
		
		// Download Link
		$tmp .= "<div><a ".$download_link."><img class='gourlimg' width='".$imageWidth."' alt='".htmlspecialchars($fileTitle, ENT_QUOTES)."' src='".GOURL_DIR2."images/".$image."' border='0'></a></div>";
		if ($fileText) $tmp .= "<br /><div class='gourlfiledescription'>" . $fileText . "</div><br /><br />";
		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		$tmp .= "<div class='gourldownloadlink'><a ".$download_link.">".__('Download File', GOURL)."</a></div>";
		
		if ($is_paid) 			$tmp .= "<br /><br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br /><br />";
		else 					$tmp .= $coins_list;
		
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div class='gourllanguage'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;

		// End
		$tmp .= "</div>";
		
		return $tmp;
	}
	
	

	
	
	
	/*
	 *  22.
	*/
	public function front_shortcode_payview($arr)
	{
		global $wpdb, $current_user;
	
		// not available activated coins
		if (!$this->payments) return "";
		
		
		$image = "";
		if (isset($arr["img"])) $image = $orig = trim($arr["img"]);
		if ($image && strpos($image, "/") === false) $image = GOURL_DIR2 . "lockimg/" . $image;  
		if ($image && strpos($image, "://") === false && (!file_exists(ABSPATH.$image) || !is_file(ABSPATH.$image))) $image = "";
		
		$short_code 	= '['.GOURL_TAG_LOCK.($image?' img="<b>'.$orig.'</b>':'').'"]';
	
	
		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";

		$preview_mode	= (stripos($_SERVER["REQUEST_URI"], "admin.php?") && $this->page == "gourlpayperview") ? true : false;
		
		
	
		// Current Settings
		// --------------------------
		$this->get_ppv_settings();
		
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->options["box_style"];
		$message_style	= $this->options["message_style"];
	
		

		$priceUSD 		= $this->options2["ppvPrice"];
		$expiryPeriod	= $this->options2["ppvExpiry"];
		$lang 			= $this->options2["ppvLang"];
		$defCoin		= $this->coin_names[$this->options2["ppvCoin"]];
		$defShow		= $this->options2["ppvOneCoin"];
		
		$level			= $this->options2["ppvLevel"];
		$imageWidthMax	= $this->options2["ppvImgMaxWidth"];
		$textAbove		= $this->options2["ppvTextAbove"];
		$textBelow		= $this->options2["ppvTextBelow"];
		$hideTitles		= $this->options2["ppvTitle"];
		$commentAuthor	= $this->options2["ppvCommentAuthor"];
		$commentBody	= $this->options2["ppvCommentBody"];
		$commentReply	= $this->options2["ppvCommentReply"];
		
		
		$userFormat 	= "COOKIE";	
		$userID 		= "";	// We uses randomly generated string as user identification and this is saved in user cookies
		$orderID 		= "payperview";
		$anchor 		= "gbx".$this->icrc32($orderID);
	

		
		// Default Values
		if (!$priceUSD || $priceUSD < 0.01) $priceUSD = 1;
		if (!$expiryPeriod) $expiryPeriod = "1 DAY";
		if (!$level || !in_array($level, array_keys($this->lock_level_ppv))) $level = 0;
		
		
		
		
		
		// Wordprtess roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber') 
		$_administrator =  $_editor = $_author = $_contributor = false;
		if (is_user_logged_in())
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
			$_author 		= in_array('author', 		$current_user->roles);
			$_contributor 	= in_array('contributor', 	$current_user->roles);
		}
		
		
		
			
		$activate = false;
		if (!$level && !is_user_logged_in()) $activate = true;  												// Unregistered Visitors will see lock screen all time
		elseif ($level == 1 && !$_administrator && !$_editor && !$_author && !$_contributor) $activate = true; 	// Unregistered Visitors + Registered Subscribers
		elseif ($level == 2 && !$_administrator && !$_editor && !$_author) 					 $activate = true; 	// Unregistered Visitors + Registered Subscribers/Contributors
		elseif ($level == 3 && !$_administrator && !$_editor) 					 			 $activate = true; 	// Unregistered Visitors + Registered Subscribers/Contributors/Authors
		


		if (!$activate && !$preview_mode) return "";
		
		
	
		// GoUrl Payments
		// --------------------------
			
		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys
			
		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];

			if ($public_key && !strpos($public_key, "PUB"))    return '<div>'.sprintf(__('Invalid %s Public Key %s - ', GOURL), $v, $public_key).$short_code.'</div>';
			if ($private_key && !strpos($private_key, "PRV"))  return '<div>'.sprintf(__('Invalid %s Private Key - ', GOURL), $v).$short_code.'</div>';

			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$defShow || $v == $defCoin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}
			
		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
			
		if (!$available_coins) return '<div>'.__('No Available Payments - ', GOURL).$short_code.'</div>';
			
		if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }
			
			
			
		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."cryptobox.class.php");
			
			
			
		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $defCoin);
			
			
		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];
			
			
		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 		// your box public key
				"private_key" => $private_key, 		// your box private key
				"orderID"     => $orderID, 			// file name hash as order id
				"userID"      => $userID, 			// unique identifier for each your user
				"userFormat"  => $userFormat, 		// save userID in
				"amount"   	  => 0,					// file price in USD below
				"amountUSD"   => $priceUSD,			// we use file price in USD
				"period"      => $expiryPeriod, 	// download link valid period
				"language"	  => $lang  			// text on EN - english, FR - french, etc
		);
			
			
			
		// Initialise Payment Class
		$box = new Cryptobox ($options);
			

		// Coin name
		$coinName = $box->coin_name();

			
		// Paid or not
		$is_paid = $box->is_paid();

			
		// Paid Already
		if ($is_paid) return "";
		

		
		// Payment Box HTML
		// ----------------------
		
		// Coins selection list (html code)
		$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "", plugins_url('/images', __FILE__), $anchor) : "";


		// Language selection list for payment box (html code)
		$languages_list = display_language_box($lang, $anchor);


		// C. Active Box
		$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
			
			
	
	
	
	
		// Html code
		// ---------------------
	
		$tmp  = "<br />";
		
		if ($textAbove) $tmp .= $textAbove . ($image ? "<br /><br />" : ""); else $tmp .= "<br />"; 
		
		$tmp .= "<div class='gourlbox' style='min-width:".$box_width."px'>";
	
		
		if ($image) $tmp .= "<a href='#".$anchor."'><img ".($imageWidthMax>0?"style='max-width:".$imageWidthMax."px'":"")." title='".__('Page Content Locked! Please pay below ', GOURL)."' alt='".__('Page Content Locked! Please pay below ', GOURL)."' border='0' src='".$image."'></a><br/>";
		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		
		if ($is_paid) 			$tmp .= "<br /><br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br /><br />";
		else 					$tmp .= $coins_list;
	
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div class='gourllanguage'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;
	
		// End
		$tmp .= "</div>";
	
	
		if ($textBelow) $tmp .= "<br /><br /><br />" . $textBelow;
		
		
		
		// Lock Page
		if (!$is_paid && !$preview_mode)
		{
			$tmp = GOURL_LOCK_START.$tmp.GOURL_LOCK_END;
			
			add_filter('the_content', 'gourl_lock_filter', 11111);
			add_filter('the_content_rss', 'gourl_lock_filter', 11111);
			add_filter('the_content_feed', 'gourl_lock_filter', 11111);

			if ($hideTitles)
			{
				add_filter('the_title', 	'gourl_lock_comments', 11111);
				add_filter('the_title_rss', 'gourl_lock_comments', 11111);
			}
			
			if ($commentAuthor) add_filter('get_comment_author_link', 	'gourl_return_false', 11111);
			
			if ($commentBody) add_filter('comment_text',	'gourl_lock_comments', 11111);
				

			if ($commentBody || $commentReply)
			{	
				add_filter('post_comments_link',     'gourl_return_false', 1);
				add_filter('comment_reply_link',     'gourl_return_false', 1);
			}
					
			if ($commentReply)
			{
				add_filter('comments_open', 		'gourl_return_false', 1);
			}
			
			add_action('do_feed',      'gourl_disable_feed', 1);
			add_action('do_feed_rdf',  'gourl_disable_feed', 1);
			add_action('do_feed_rss',  'gourl_disable_feed', 1);
			add_action('do_feed_rss2', 'gourl_disable_feed', 1);
			add_action('do_feed_atom', 'gourl_disable_feed', 1);			
		}
	
	
	
		return $tmp;
	}
	
	

	
	
	
	/********************************************************************/
	
	
	
	/*
	 *  23.
	*/
	public function admin_init()
	{
		ob_start();
	
		// Actions
	
		if (isset($_POST['ak_action']) && strpos($this->page, GOURL) === 0)
		{
			switch($_POST['ak_action'])
			{
				case GOURL.'save_settings':
	
					$this->post_settings();
					$this->check_settings();
	
					if (!$this->errors)
					{
						$this->save_settings();
						header('Location: '.GOURL_ADMIN.GOURL.'settings&updated=true');
						die();
					}
	
					break;
	
				case GOURL.'save_file':
						
					$this->post_record();
					$this->check_record();
						
					if (!$this->record_errors)
					{
						$this->save_record();
	
						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'file&id='.$this->id.'&updated=true');
							die();
						}
					}
						
					break;

				case GOURL.'save_payperview':
				
					$this->post_ppv_settings();
					$this->check_ppv_settings();
									
					if (!$this->record_errors)
					{
						$this->save_ppv_settings();
						header('Location: '.GOURL_ADMIN.GOURL.'payperview&updated=true');
						die();
					}
				
					break;
							
				default:
						
					break;
			}
		}
	
		return true;
	}
	
	
	
	
	
	
	
	/*
	 *  24.
	*/
	public function admin_header()
	{
		global $current_user;
		
		// File Preview Downloads
		
		// Wordprtess roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator = $_editor = false;
		if (is_user_logged_in())
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
		}
		
		if (isset($_GET[GOURL_PREVIEW]) && $_GET[GOURL_PREVIEW] && !$_POST && ($_administrator || $_editor))
		{
			$filePath = GOURL_DIR."files/".$_GET[GOURL_PREVIEW];
			
			if (file_exists($filePath) && is_file($filePath))
			{
				// Starting Download
				$this->download_file($filePath);
					
				// Flush Cache
				ob_flush();
				
				die;
			}	
		}
		
		
		echo '<script src="'.plugins_url('/js/cryptobox.min.js?ver='.GOURL_VERSION, __FILE__).'" type="text/javascript"></script>
			  <link rel="stylesheet" type="text/css" href="'.plugins_url('/css/style.admin.css?ver='.GOURL_VERSION, __FILE__).'" media="all" />
			  <link rel="stylesheet" type="text/css" href="'.plugins_url('/css/style.front.css?ver='.GOURL_VERSION, __FILE__).'" media="all" />';
	
		return true;
	}
	
	

	
	
	
	
	/*
	 *  25.
	*/
	public function admin_warning()
	{
		echo '<div class="updated"><p>'.sprintf(__('<strong>%s Plugin is almost ready to use!</strong> All you need to do is to <a style="text-decoration:underline" href="admin.php?page=%ssettings">update your plugin settings</a>', GOURL), GOURL_NAME, GOURL).'</p></div>';
	
		return true;
	}
	
	
	
	/*
	 *  26.
	*/
	public function admin_warning_reactivate()
	{
		echo '<div class="error"><p>'.sprintf(__('<strong>Please deactivate %s Plugin and activate it again.</strong> if you have already done so, please create three folders below manually and set folder permissions to 0777:<br />- %sfiles/<br />- %simages/<br />- %slockimg/', GOURL), GOURL_NAME, GOURL_DIR2, GOURL_DIR2, GOURL_DIR2).'</p></div>';
	
		return true;
	}
	
	
	
	
	/*
	 *  27.
	*/
	public function admin_menu()
	{
		global $submenu;
		
		if (get_bloginfo('version') >= 3.0)
		{
			define("GOURL_PERMISSION", "add_users");
		}
		else{
			define("GOURL_PERMISSION", "edit_themes");
		}
		
		add_menu_page(
				__("GoUrl Bitcoin", GOURL)		
				, __('GoUrl Bitcoin', GOURL)
				, GOURL_PERMISSION
				, GOURL
				, array(&$this, 'page_files'),
				plugins_url('/images/btc_icon.png', __FILE__),
				'21.777'
		);

		add_submenu_page(
		GOURL
				, __('&#149; Paid Download Files', GOURL)
				, __('&#149; Paid Download Files', GOURL)
				, GOURL_PERMISSION
				, GOURL
				, array(&$this, 'page_files')
		);
		
		add_submenu_page(
		GOURL
				, $this->space(2).__('Add New File', GOURL)
				, $this->space(2).__('Add New File', GOURL)
				, GOURL_PERMISSION
				, GOURL."file"
				, array(&$this, 'page_new_file')
		);

		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-View', GOURL)
				, __('&#149; Pay-Per-View', GOURL)
				, GOURL_PERMISSION
				, GOURL."payperview"
				, array(&$this, 'page_payperview')
		);
	
		
		add_submenu_page(
		GOURL
				, '________________'
				, '________________'
				, GOURL_PERMISSION
				, GOURL."space"
				, array(&$this, 'page_payments')
		);
		
		add_submenu_page(
		GOURL
				, __('All Payments', GOURL)
				, __('All Payments', GOURL)
				, GOURL_PERMISSION
				, GOURL."payments"
				, array(&$this, 'page_payments')
		);
	
		add_submenu_page(
		GOURL
				, __('Settings', GOURL)
				, __('Settings', GOURL)
				, GOURL_PERMISSION
				, GOURL."settings"
				, array(&$this, 'page_settings')
		);
		

		
	
		return true;
	}
	
		

	
	
	/********************************************************************/
	

	
	
	
	/*
	 *  28.
	*/
	private function page_title($title, $type = 1) // 1 - Plugin Name, 2 - Pay-Per-Download,  3 - Pay-Per-View 
	{
		if ($type == 2) 		$text = __("GoUrl Bitcoin Digital Paid Downloads", GOURL);
		elseif ($type == 3) 	$text = __("GoUrl Premium Paid Anonymous Page/Video Access", GOURL);
		else 					$text = GOURL_NAME;
	
		$tmp = "<div class='".GOURL."logo'><a href='https://gourl.io/' target='_blank'><img title='".__('CRYPTO-CURRENCY PAYMENT GATEWAY', GOURL)."' src='".plugins_url('/images/gourl.png', __FILE__)."' border='0'></a></div>";
		if ($title) $tmp .= "<div id='icon-options-general' class='icon32'><br /></div><h2>".__($text.' - '.$title, GOURL)."</h2><br />";
		
		return $tmp;
	}
	
	
	
	/*
	 *  29.
	*/
	private function upload_file($file, $dir, $english = true)
	{
		$fileName 	= mb_strtolower($file["name"]);
		$ext 		= $this->right($fileName, ".", false);
		
		if ($fileName == $ext) $ext = "";
		$ext = trim($ext); 
		if (mb_strpos($ext, " ")) $ext = str_replace(" ", "_", $ext);
		
		if (!is_uploaded_file($file["tmp_name"])) $this->record_errors[] = sprintf(__('Cannot upload file "%s" on server. Alternatively, you can upload your file to "%s" using the FTP File Manager', GOURL), $file["name"], GOURL_DIR2.$dir);
		elseif ($dir == "images" && !in_array($ext, array("jpg", "jpeg", "png", "gif"))) $this->record_errors[] = sprintf(__('Invalid image file "%s", supported *.gif, *.jpg, *.png files only', GOURL), $file["name"]);
		else
		{
			if ($english) $fileName = preg_replace('/[^A-Za-z0-9\.\_\&]/', ' ', $fileName); // allowed english symbols only
			else $fileName = preg_replace('/[\(\)\?\!\;\,\>\<\'\"\%\&]/', ' ', $fileName);
			
			$fileName = trim(mb_strtolower(str_replace(" ", "_", preg_replace("{[ \t]+}", " ", trim($fileName)))), ".,!;_-");
			$fileName = str_replace("_.", ".", $fileName);
			$fileName = mb_substr($fileName, 0, 95);
			if (mb_strlen($fileName) < (mb_strlen($ext) + 3)) $fileName = date("Ymd")."_".strtotime("now").".".$ext;
			if ($dir == "images" && is_numeric($fileName[0])) $fileName = "i".$fileName;
				
			$i = 1;
			$fileName1 = $this->left($fileName, ".", false);
			$fileName2 = (mb_strpos($fileName, ".")) ? "." . $this->right($fileName, ".", false) : "";
			while (file_exists(GOURL_DIR.$dir."/".$fileName)) { $i++; $fileName = $fileName1 . "-" . $i . $fileName2; }
				
			if (!move_uploaded_file($file["tmp_name"], GOURL_DIR.$dir."/".$fileName)) $this->record_errors[] = sprintf(__('Cannot move file "%s" to directory "%s" on server. Please check directory permissions', GOURL), $file["name"], GOURL_DIR2.$dir);
			elseif ($dir == "images")
			{
				$this->record_info[] = sprintf(__('Your Featured Image %s has been uploaded <strong>successfully</strong>', GOURL), ($file["name"] == $fileName ? '"'.$fileName.'"' : ''));
				
				return $fileName;
				
			}
			else
			{
				$this->record_info[] = sprintf(__('Your File %s has been uploaded <strong>successfully</strong>', GOURL), ($file["name"] == $fileName ? '"'.$fileName.'"' : '')) . ($file["name"] != $fileName ? sprintf(__('. New File Name is <strong>%s</strong>', GOURL), $fileName):""); 
				
				return $fileName;
			}
		}
		
		return "";
	}
	
	
	
	
	/*
	 *  30.
	*/
	private function download_file($file)
	{
		// Erase Old Cache
		ob_clean();
		
		// Starting Download
		$size = filesize($file);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . $size);
		readfile($file);
		
		return true;
	}	
	
	
	
	
	
	/*
	 *  31.
	*/
	public function callback_parse_request( &$wp )
	{
		global $wp;
	
		if (in_array(trim($_SERVER["REQUEST_URI"], "/ "), array("?cryptobox.callback.php", "index.php?cryptobox.callback.php")))
		{
			ob_clean();
			
			$cryptobox_private_keys = array();
			foreach($this->coin_names as $k => $v)
			{ 
				$val = get_option(GOURL.$v."private_key");
				if ($val) $cryptobox_private_keys[] = $val;
			}
			
			DEFINE("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
			
			include_once("cryptobox.callback.php");
			
			ob_flush();
			
			die;
		}
	
		return true;
	}
	
	
	
	
	/********************************************************************/
	
	/*
	 *  32. Install Plugin
	*/
	public function install ()
	{
		global $wpdb;
	
		$sql = "CREATE TABLE `crypto_files` (
			  `fileID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `fileTitle` varchar(100) NOT NULL DEFAULT '',
			  `active` tinyint(1) NOT NULL DEFAULT '1',
			  `fileName` varchar(100) NOT NULL DEFAULT '',
			  `fileSize` double(15,0) NOT NULL DEFAULT '0',
			  `fileText` text,
			  `priceUSD` double(10,2) NOT NULL DEFAULT '0.00',
			  `purchases` mediumint(8) NOT NULL DEFAULT '0',
			  `userFormat` enum('MANUAL','COOKIE','SESSION','IPADDRESS') NOT NULL,
			  `expiryPeriod` varchar(15) NOT NULL DEFAULT '',
			  `lang` varchar(2) NOT NULL DEFAULT '',
			  `defCoin` varchar(5) NOT NULL DEFAULT '',
			  `defShow` tinyint(1) NOT NULL DEFAULT '1',
			  `image` varchar(100) NOT NULL DEFAULT '',
			  `imageWidth` smallint(5) NOT NULL DEFAULT '0',
			  `priceShow` tinyint(1) NOT NULL DEFAULT '1',
			  `paymentCnt` smallint(5) NOT NULL DEFAULT '0',
			  `paymentTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `updatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `createtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  PRIMARY KEY (`fileID`),
			  KEY `fileTitle` (`fileTitle`),
			  KEY `active` (`active`),
			  KEY `fileName` (`fileName`),
			  KEY `fileSize` (`fileSize`),
			  KEY `priceUSD` (`priceUSD`),
			  KEY `purchases` (`purchases`),
			  KEY `userFormat` (`userFormat`),
			  KEY `expiryPeriod` (`expiryPeriod`),
			  KEY `lang` (`lang`),
			  KEY `defCoin` (`defCoin`),
			  KEY `defShow` (`defShow`),
			  KEY `image` (`image`),
			  KEY `imageWidth` (`imageWidth`),
			  KEY `priceShow` (`priceShow`),
			  KEY `paymentCnt` (`paymentCnt`),
			  KEY `paymentTime` (`paymentTime`),
			  KEY `updatetime` (`updatetime`),
			  KEY `createtime` (`createtime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	
	
		$sql = "CREATE TABLE `crypto_payments` (
			  `paymentID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `boxID` int(11) unsigned NOT NULL DEFAULT '0',
			  `boxType` enum('paymentbox','captchabox') NOT NULL,
			  `orderID` varchar(50) NOT NULL DEFAULT '',
			  `userID` varchar(50) NOT NULL DEFAULT '',
			  `countryID` varchar(3) NOT NULL DEFAULT '',
			  `coinLabel` varchar(6) NOT NULL DEFAULT '',
			  `amount` double(20,8) NOT NULL DEFAULT '0.00000000',
			  `amountUSD` double(20,8) NOT NULL DEFAULT '0.00000000',
			  `unrecognised` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `addr` varchar(34) NOT NULL DEFAULT '',
			  `txID` char(64) NOT NULL DEFAULT '',
			  `txDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `txConfirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `txCheckDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `processed` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `processedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `recordCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  PRIMARY KEY (`paymentID`),
			  KEY `boxID` (`boxID`),
			  KEY `boxType` (`boxType`),
			  KEY `userID` (`userID`),
			  KEY `countryID` (`countryID`),
			  KEY `orderID` (`orderID`),
			  KEY `amount` (`amount`),
			  KEY `amountUSD` (`amountUSD`),
			  KEY `coinLabel` (`coinLabel`),
			  KEY `unrecognised` (`unrecognised`),
			  KEY `addr` (`addr`),
			  KEY `txID` (`txID`),
			  KEY `txDate` (`txDate`),
			  KEY `txConfirmed` (`txConfirmed`),
			  KEY `txCheckDate` (`txCheckDate`),
			  KEY `processed` (`processed`),
			  KEY `processedDate` (`processedDate`),
			  KEY `recordCreated` (`recordCreated`),
			  KEY `key1` (`boxID`,`orderID`),
			  KEY `key2` (`boxID`,`orderID`,`userID`),
			  KEY `key3` (`boxID`,`orderID`,`userID`,`txID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	
		// upload dir
		gourl_retest_dir();
	
		ob_flush();
		
		return true;
	}
	
	
	
	
	
		
	/********************************************************************/
	

	
	
	
	/*
	 *  33. Supported Functions
	 */ 
	private function sel($val1, $val2)
	{
		$tmp = ((is_array($val1) && in_array($val2, $val1)) || strval($val1) == strval($val2)) ? ' selected="selected"' : '';
	
		return $tmp;
	}
	private function chk($val1, $val2)
	{
		$tmp = (strval($val1) == strval($val2)) ? ' checked="checked"' : '';
	
		return $tmp;
	}
	private function left($str, $findme, $firstpos = true)
	{
		$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);
	
		if ($pos === false) return $str;
		else return mb_substr($str, 0, $pos);
	}
	private function right($str, $findme, $firstpos = true)
	{
		$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);
	
		if ($pos === false) return $str;
		else return mb_substr($str, $pos + mb_strlen($findme));
	}
	private function icrc32($str)
	{
		$in = crc32($str);
		$int_max = pow(2, 31)-1;
		if ($in > $int_max) $out = $in - $int_max * 2 - 2;
		else $out = $in;
		$out = abs($out);
			
		return $out;
	}
	private function space($n=1)
	{
		$tmp = "";
		for ($i=1;$i<=$n;$i++) $tmp .= " &#160; ";
		return $tmp;
	} 
}
// end class gourlclass


// Call Main Class
// ----------------
$gourl = new gourlclass();
// ----------------







/*
 *  I.
*/
function gourl_retest_dir()
{
	$dir = plugin_dir_path( __FILE__ )."images/dir/";
	
	if (!file_exists(GOURL_DIR."files")) wp_mkdir_p(GOURL_DIR."files");
	if (!file_exists(GOURL_DIR."files/.htaccess")) copy($dir."files/.htaccess", GOURL_DIR."files/.htaccess");
	if (!file_exists(GOURL_DIR."files/index.htm")) copy($dir."files/index.htm", GOURL_DIR."files/index.htm");

	if (!file_exists(GOURL_DIR."lockimg")) wp_mkdir_p(GOURL_DIR."lockimg");
	if (!file_exists(GOURL_DIR."lockimg/index.htm")) copy($dir."lockimg/index.htm", GOURL_DIR."lockimg/index.htm");
	if (!file_exists(GOURL_DIR."lockimg/image1.jpg")) copy($dir."lockimg/image1.jpg", GOURL_DIR."lockimg/image1.jpg");
	if (!file_exists(GOURL_DIR."lockimg/image2.jpg")) copy($dir."lockimg/image2.jpg", GOURL_DIR."lockimg/image2.jpg");
	
	if (!file_exists(GOURL_DIR."images"))
	{
		wp_mkdir_p(GOURL_DIR."images");
		
		$files = scandir($dir."images");
		foreach($files as $file)
			if (is_file($dir."images/".$file) && !in_array($file, array(".", "..")))
			copy($dir."images/".$file, GOURL_DIR."images/".$file);
	}

	return true;
}




/*
 *  II.
*/
function gourl_byte_format ($num, $precision = 1)
{
	if ($num >= 1000000000000)
	{
		$num = round($num / 1099511627776, $precision);
		$unit = __('TB', GOURL);
	}
	elseif ($num >= 1000000000)
	{
		$num = round($num / 1073741824, $precision);
		$unit = __('GB', GOURL);
	}
	elseif ($num >= 1000000)
	{
		$num = round($num / 1048576, $precision);
		$unit = __('MB', GOURL);
	}
	elseif ($num >= 1000)
	{
		$num = round($num / 1024, $precision);
		$unit = __('kb', GOURL);
	}
	else
	{
		$unit = __('Bytes', GOURL);
		return number_format($num).' '.$unit;
	}

	$num = number_format($num, $precision);
	if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");

	return $num.' '.$unit;
}






/*
 *  III. User-defined function for new payment
*/
function cryptobox_new_payment($paymentID, $arr)
{
	$fileID = ($arr["order"] && strpos($arr["order"], "file_") === 0) ? substr($arr["order"], 5) : 0;
	
	if ($fileID && is_numeric($fileID))
	{
		$sql = "UPDATE crypto_files SET paymentCnt = paymentCnt + 1, paymentTime = '".gmdate('Y-m-d H:i:s')."' WHERE fileID = '".$fileID."' LIMIT 1";
		run_sql($sql);
	}
	
	return true;
}





	
/*
 *  IV.
*/
function gourl_lock_filter($content)
{

	$content = mb_substr($content, mb_strpos($content, GOURL_LOCK_START));
	$content = mb_substr($content, 0, mb_strpos($content, GOURL_LOCK_END));

	return $content;
}




/*
 *  V.
*/
function gourl_lock_comments($content)
{
	$content = "<br/>* * * * * * * * * * * * * * * * * * * * * * *<br/> * * * * * * * * * * * * * * * * * * * * * * *";

	return $content;
}




/*
 *  VI.
*/
function gourl_return_false()
{

	return false;
}




/*
 *  VII.
*/
function gourl_disable_feed() 
{
	wp_die(sprintf(__('<h1>Feed not available, please visit our <a href="%s">Home Page</a> !</h1>'), get_bloginfo('url')));
}







/********************************************************************/








// VIII. TABLE1 - "All Paid Files"  WP_Table Class
// ----------------------------------------

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class gourltable_files extends WP_List_Table 
{
	private $coin_names = array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'DOGE' => 'dogecoin', 'SPD' => 'speedcoin', 'DRK' => 'darkcoin', 'RDD' => 'reddcoin', 'VTC' => 'vertcoin', 'FTC' => 'feathercoin', 'VRC' => 'vericoin', 'POT' => 'potcoin');
	private $coin_www   = array('bitcoin' => 'https://bitcoin.org/', 'litecoin'  => 'https://litecoin.org/', 'dogecoin'  => 'http://dogecoin.com/', 'speedcoin'  => 'http://speedcoin.co/', 'darkcoin'  => 'https://www.darkcoin.io/', 'vertcoin'  => 'http://vertcoin.org/', 'reddcoin'  => 'http://reddcoin.com/', 'feathercoin' => 'http://www.feathercoin.com/', 'vericoin' => 'http://www.vericoin.info/', 'potcoin' => 'http://www.potcoin.com/');
	private $languages	= array("en"=>"English", "fr"=>"French", "ru"=>"Russian", "ar"=>"Arabic", "cn"=>"Simplified Chinese", "zh"=>"Traditional Chinese", "hi"=>"Hindi");
	private $search = '';

	function __construct($search = '', $rec_per_page = 20)
	{

		$this->search 		= $search;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;

		
		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false

		) );
	}

	function column_default( $item, $column_name ) 
	{
		$tmp = "";
		switch( $column_name ) 
		{
			case 'active':
			case 'defShow':
			case 'priceShow':
				$val = ($item->$column_name) ? "checked" : "unchecked";
				$tmp = "<img alt='".$val."' src='".plugins_url('/images/'.$val.'.gif', __FILE__)."' border='0'>";
				break;
				
			case 'fileSize':
				$tmp = gourl_byte_format($item->$column_name);
				break;
				
			case 'priceUSD':
				$num = number_format($item->$column_name, 2);
				if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");
				$tmp = $num . ' ' . __('USD', GOURL);
				break;

			case 'paymentCnt':
				$tmp = ($item->$column_name > 0) ? '<a href="'.GOURL_ADMIN.GOURL.'payments&s=file_'.$item->fileID.'">'.$item->$column_name.'</a>' : '-';
				break;
				
			case 'image':
				$img = GOURL_DIR2.'images/'.$item->$column_name;
				$tmp = "<a target='_blank' href='".$img."'><img width='80' height='80' src='".$img."' border='0'></a>";
				break;

			case 'defCoin':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".$this->coin_www[$val]."' target='_blank'><img width='40' alt='".$val."' title='".$val."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;
				
			case 'lang':
				$tmp = $this->languages[$item->$column_name];
				break;
				
			case 'purchases':
				$tmp = ($item->$column_name == 0) ?  __('unlimited', GOURL) : $item->$column_name . ' ' . __('copies', GOURL);
				break;
				
			case 'imageWidth':
				$tmp = ($item->$column_name > 0) ?  $item->$column_name. ' ' . __('px', GOURL) : '-';
				break;
				
			case 'userFormat':
				$tmp = ($item->$column_name == 'MANUAL') ?   __('Registered Users', GOURL) : $item->$column_name;
				break;
				
			case 'paymentTime':
			case 'updatetime':
			case 'createtime':
				$tmp = ($item->$column_name != '0000-00-00 00:00:00') ? date("d M Y, H:i A", strtotime($item->$column_name)) : '-';
				break;
				
			default:
				$tmp = $item->$column_name;
				break;
		}
		
		return $tmp;
	}

	


	function get_columns()
	{
		$columns = array(
				'fileID'  		=> __('ID', GOURL),
				'active'  		=> __('Acti-ve?', GOURL),
				'fileName'  	=> __('File Name', GOURL),
				'fileTitle' 	=> __('Title', GOURL),
				'fileSize'  	=> __('File Size', GOURL),
				'priceUSD'  	=> __('Price', GOURL),
				'priceShow'  	=> __('Show FileName Price?', GOURL),
				'paymentCnt'  	=> __('Total Sold', GOURL),
				'paymentTime'  	=> __('Latest Received Payment, GMT', GOURL),
				'updatetime'  	=> __('Record Updated, GMT', GOURL),
				'createtime'  	=> __('Record Created, GMT', GOURL),
				'image'  		=> __('Featured Image', GOURL),
				'imageWidth'  	=> __('Image Width', GOURL),
				'expiryPeriod'  => __('Payment Expiry Period', GOURL),
				'defCoin'  		=> __('Default Payment Box Coin', GOURL),
				'defShow'  		=> __('Default Coin only?', GOURL),
				'lang'  		=> __('Default Box Language', GOURL),
				'purchases'  	=> __('Purchase Limit', GOURL),
				'userFormat'  	=> __('Store Visitor IDs in', GOURL)
		);
		return $columns;
	}
	
	
	function get_sortable_columns() 
	{
		$sortable_columns = array
		(
				'fileID'  		=> array('fileID', false),
				'active'  		=> array('active', true),
				'fileName'  	=> array('fileName', false),
				'fileTitle' 	=> array('fileTitle', false),
				'fileSize'  	=> array('fileSize', false),
				'priceUSD'  	=> array('priceUSD', false),
				'priceShow'  	=> array('priceShow', true),
				'paymentCnt'  	=> array('paymentCnt', true),
				'paymentTime'  	=> array('paymentTime', true),
				'updatetime'  	=> array('updatetime', true),
				'createtime'  	=> array('createtime', true),
				'image'  		=> array('image', false),
				'imageWidth'  	=> array('imageWidth', false),
				'expiryPeriod'  => array('expiryPeriod', false),
				'defCoin'  		=> array('defCoin', false),
				'defShow'  		=> array('defShow', true),
				'lang'  		=> array('lang', false),
				'purchases'  	=> array('purchases', false),
				'userFormat'  	=> array('userFormat', false)
		);
		
		return $sortable_columns;
	}
	

	function column_fileName($item)
	{
		$actions = array(
				'download'  => sprintf('<a href="'.GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'='.$item->fileName.'">'.__('Download File', GOURL).'</a>',$_REQUEST['page'],'download',$item->fileID)
		);
	
		return sprintf('%1$s %2$s', $item->fileName, $this->row_actions($actions) );
	}

	
	function column_fileTitle($item)
	{
		$actions = array(
				'edit'      => sprintf('<a href="'.GOURL_ADMIN.GOURL.'file&id='.$item->fileID.'">'.__('Edit', GOURL).'</a>',$_REQUEST['page'],'edit',$item->fileID),
				'delete'    => sprintf('<a href="'.GOURL_ADMIN.GOURL.'file&id='.$item->fileID.'&gourlcryptocoin='.$this->coin_names[$item->defCoin].'&gourlcryptolang='.$item->lang.'&preview=true">'.__('Preview', GOURL).'</a>',$_REQUEST['page'],'preview',$item->fileID),
		);
	
		return sprintf('%1$s %2$s', $item->fileTitle, $this->row_actions($actions) );
	}
	
	
	function prepare_items() 
	{
		global $wpdb, $_wp_column_headers;
		
		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_files WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : '';
		if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY updatetime DESC';
		
		
		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';
		
		if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

} 
// end class gourltable_files






/********************************************************************/









// IX. TABLE2 - "All Payments"  WP_Table Class
// ----------------------------------------
class gourltable_payments extends WP_List_Table
{
	private $coin_names = array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'DOGE' => 'dogecoin', 'SPD' => 'speedcoin', 'DRK' => 'darkcoin', 'RDD' => 'reddcoin', 'VTC' => 'vertcoin', 'FTC' => 'feathercoin', 'VRC' => 'vericoin', 'POT' => 'potcoin');
	private $coin_www   = array('bitcoin' => 'https://bitcoin.org/', 'litecoin'  => 'https://litecoin.org/', 'dogecoin'  => 'http://dogecoin.com/', 'speedcoin'  => 'http://speedcoin.co/', 'darkcoin'  => 'https://www.darkcoin.io/', 'vertcoin'  => 'http://vertcoin.org/', 'reddcoin'  => 'http://reddcoin.com/', 'feathercoin' => 'http://www.feathercoin.com/', 'vericoin' => 'http://www.vericoin.info/', 'potcoin' => 'http://www.potcoin.com/');
	private $coin_chain = array('bitcoin' => 'https://blockchain.info/', 'litecoin'  => 'http://ltc.blockr.io/', 'dogecoin'  => 'https://dogechain.info/chain/Dogecoin/', 'speedcoin'  => 'http://speedcoin.co:2750/', 'darkcoin'  => 'http://explorer.darkcoin.io/', 'vertcoin'  => 'http://cryptexplorer.com/chain/VertCoin/', 'reddcoin'  => 'http://live.reddcoin.com/', 'feathercoin' => 'https://explorer.feathercoin.com/chain/Feathercoin/', 'vericoin' => 'http://bitinfocharts.com/vericoin/', 'potcoin' => 'http://www.potchain.net/');
	
	private $search = '';

	function __construct($search = '', $rec_per_page = 20)
	{

		$this->search = $search;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;
		
		
		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false
				)
			);
		
		include_once(plugin_dir_path( __FILE__ )."cryptobox.class.php");
		
	}

	function column_default( $item, $column_name )
	{
		$tmp = "";
		switch( $column_name )
		{
			case 'unrecognised':
			case 'txConfirmed':
			case 'processed':
				if ($column_name != "processed" || $item->orderID != "payperview")
				{
					$title = "";
					if ($column_name=='processed') $title = "title='". (($item->$column_name) ? __('User already downloaded this file from your website ', GOURL) : __('User not downloaded this file yet', GOURL))."'";					
					$val = ($item->$column_name) ? "checked" : "unchecked";
					$tmp = "<img ".$title." alt='".$val."' src='".plugins_url('/images/'.$val.'.gif', __FILE__)."' border='0'>";
				}
				break;

			case 'boxID':
				if ($item->$column_name)
				{
					$tmp = "<a title='".__('View Statistics', GOURL)."' href='https://gourl.io/view/coin_boxes/".$item->$column_name."/statistics.html' target='_blank'>".$item->$column_name."</a>";
				}
				break;
				
			case 'orderID':
				if ($item->$column_name)
				{
					if ($item->$column_name == "payperview") $url = GOURL_ADMIN.GOURL."payperview";
					elseif (strpos($item->$column_name, "file_") === 0) $url = GOURL_ADMIN.GOURL."file&id=".substr($item->$column_name, 5);
					else $url = "";
					
					$tmp = ($url) ? "<a href='".$url."'>".$item->$column_name."</a>" : $item->$column_name; 
				}
				break;
				

			case 'userID':
				if ($item->$column_name)
				{
					if (strpos($item->$column_name, "user_") === 0) $url = "/wp-admin/user-edit.php?user_id=".substr($item->$column_name, 5);
					else $url = "";
						
					$tmp = ($url) ? "<a href='".$url."'>".$item->$column_name."</a>" : $item->$column_name;
				}
				elseif ($item->unrecognised) $tmp = "? ".__('<small>wrong paid amount</small>', GOURL);
				
				break;
				
				
			case 'amountUSD':
				$num = number_format($item->$column_name, 8);
				if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");
				$tmp = $num . ' ' . __('USD', GOURL);
				break;
				
					
			case 'amount':
				$num = number_format($item->$column_name, 8);
				if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");
				$tmp = $num . ' ' . $item->coinLabel;
				break;

				
			case 'coinLabel':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".$this->coin_www[$val]."' target='_blank'><img width='40' alt='".$val."' title='".$val."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;

				
			case 'countryID':
				if ($item->$column_name)
				{
					$tmp = "<a title='".__('Show Only Visitors from this Country', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=".$item->$column_name."'><img width='16' border='0' style='margin-right:7px' alt='".$item->$column_name."' src='".plugins_url('/images/flags/'.$item->$column_name.'.png', __FILE__)."' border='0'></a>" . get_country_name($item->$column_name);
				}
				break;

				
				
			case 'txID':
				if ($item->$column_name) $tmp = "<a title='".__('Transaction Details', GOURL)." - ".$item->$column_name."' href='".$this->coin_chain[$this->coin_names[$item->coinLabel]].'tx/'.$item->$column_name."' target='_blank'>".$item->$column_name."</a>";
				break;

				
			case 'addr':
				if ($item->$column_name) $tmp = "<a title='".__('Wallet Details', GOURL)." - ".$item->$column_name."' href='".$this->coin_chain[$this->coin_names[$item->coinLabel]].'address/'.$item->$column_name."' target='_blank'>".$item->$column_name."</a>";
				break;
				
					
			case 'txDate':
			case 'txCheckDate':
			case 'recordCreated':
			case 'processedDate':
				if ($column_name != "processedDate" || $item->orderID != "payperview")
				{
					$tmp = ($item->$column_name != '0000-00-00 00:00:00') ? date("d M Y, H:i A", strtotime($item->$column_name)) : '-';
				}
				break;

			default:
				$tmp = $item->$column_name;
				break;
		}

		return $tmp;
	}




	function get_columns()
	{
		$columns = array(
					'paymentID'  		=> __('Payment ID', GOURL),
					'boxID'				=> __('Payment Box ID', GOURL),
					'coinLabel'			=> __('Coin', GOURL),
					'orderID'			=> __('Order ID', GOURL),
					'amount'			=> __('Paid Amount', GOURL),
					'amountUSD'			=> __('Approximate in USD', GOURL),
					'unrecognised'		=> __('Unrecogn. Payment?', GOURL),
					'userID'			=> __('Visitor ID', GOURL),
					'countryID'			=> __('Visitor Country', GOURL),
					'txConfirmed'		=> __('Confirmed Payment?', GOURL),
					'txDate'			=> __('Payment Date, GMT', GOURL),
					'processed'			=> __('User Downl. File?', GOURL),
					'processedDate'		=> __('File Downloaded Time, GMT', GOURL),
					'txID'				=> __('Transaction ID', GOURL),
					'addr'				=> __('Your GoUrl Wallet Address', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'paymentID'  		=> array('paymentID', true),
				'boxID'				=> array('boxID', false),
				'boxType'			=> array('boxType', false),
				'orderID'			=> array('orderID', false),
				'userID'			=> array('userID', false),
				'countryID'			=> array('countryID', true),
				'coinLabel'			=> array('coinLabel', false),
				'amount'			=> array('amount', false),
				'amountUSD'			=> array('amountUSD', true),
				'unrecognised'		=> array('unrecognised', false),
				'txDate'			=> array('txDate', true),
				'txConfirmed'		=> array('txConfirmed', true),
				'addr'				=> array('addr', false),
				'txID'				=> array('txID', false),
				'txCheckDate'		=> array('txCheckDate', true),
				'processed'			=> array('processed', true),
				'processedDate'		=> array('processedDate', true),
				'recordCreated'		=> array('recordCreated', true)				
		);

		return $sortable_columns;
	}




	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_payments WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : '';
		if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY txDate DESC';


		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';

		if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

}
// end class gourltable_payments




?>