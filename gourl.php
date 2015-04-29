<?php


if (!defined( 'ABSPATH' ) || !defined( 'GOURL' )) exit; 


final class gourlclass 
{
	private $options 		= array(); 		// global setting values
	private $errors			= array(); 		// global setting errors
	private $payments		= array(); 		// global activated payments (bitcoin, litecoin, etc)
	
	private $options2 		= array(); 		// pay-per-view settings
	private $options3 		= array(); 		// pay-per-membership settings
	
	private $page 			= array(); 		// current page url
	private $id 			= 0; 			// current record id
	private $record 		= array(); 		// current record values
	private $record_errors 	= array(); 		// current record errors
	private $record_info	= array(); 		// current record messages
	private $record_fields	= array(); 		// current record fields
	
	private $updated		= false;		// publish 'record updated' message
	
	private $lock_type		= "";			// membership or view
	
	private $coin_names     	= array();
	private $coin_chain     	= array();
	private $coin_www       	= array();
	private $languages			= array();
	
	private $custom_images 		= array('img_plogin'=>'Payment Login', 'img_flogin'=>'File Download Login', 'img_sold'=>'Product Sold', 'img_pdisable'=>'Payments Disabled', 'img_fdisable'=>'File Payments Disabled', 'img_nofile'=>'File Not Exists'); // custom payment box images
	private $expiry_period 		= array('NO EXPIRY', '10 MINUTES', '20 MINUTES', '30 MINUTES', '1 HOUR', '2 HOURS', '3 HOURS', '6 HOURS', '12 HOURS', '1 DAY', '2 DAYS', '3 DAYS', '4 DAYS', '5 DAYS',  '1 WEEK', '2 WEEKS', '3 WEEKS', '4 WEEKS', '1 MONTH', '2 MONTHS', '3 MONTHS', '6 MONTHS', '12 MONTHS'); // payment expiry period
	private $store_visitorid 	= array('COOKIE','SESSION','IPADDRESS','MANUAL'); // Save auto-generated unique visitor ID in cookies, sessions or use the IP address to decide unique visitors (without use cookies)
	private $addon 				= array("gourlwoocommerce", "gourlwpecommerce", "gourljigoshop", "gourlappthemes", "gourlmarketpress", "gourlpmpro", "gourlgive");
			
	private $fields_download 	= array("fileID" => 0,  "fileTitle" => "", "active" => 1, "fileName"  => "", "fileText" => "", "fileSize" => 0, "priceUSD"  => "0.00", "priceCoin"  => "0.0000", "priceLabel"  => "BTC", "purchases"  => "0", "userFormat"  => "COOKIE", "expiryPeriod" => "2 DAYS", "lang"  => "en", "defCoin" => "", "defShow" => 0, "image"  => "", "imageWidth" => 200,  "priceShow" => 1, "paymentCnt" => 0, "paymentTime" => "", "updatetime"  => "", "createtime"  => "");
	private $fields_product 	= array("productID" => 0,  "productTitle" => "", "active" => 1,"priceUSD"  => "0.00", "priceCoin"  => "0.0000", "priceLabel"  => "BTC", "purchases"  => "0", "expiryPeriod" => "NO EXPIRY", "lang"  => "en", "defCoin" => "", "defShow" => 0, "productText"  => "", "finalText" => "", "emailUser" => 0, "emailUserFrom" => "", "emailUserTitle" => "", "emailUserBody" => "", "emailAdmin" => 0, "emailAdminFrom" => "", "emailAdminTitle" => "", "emailAdminBody" => "", "emailAdminTo" => "", "paymentCnt" => 0, "paymentTime" => "", "updatetime"  => "", "createtime"  => "");
	
	private $fields_view 		= array("ppvPrice" => "0.00", "ppvPriceCoin" => "0.0000", "ppvPriceLabel" => "BTC", "ppvExpiry" => "1 DAY", "ppvLevel"  => 0, "ppvLang" => "en", "ppvCoin"  => "", "ppvOneCoin"  => "", "ppvTextAbove"  => "", "ppvTextBelow"  => "", "ppvTitle" => "", "ppvTitle2" => "", "ppvCommentAuthor"  => "", "ppvCommentBody"  => "", "ppvCommentReply"  => "");
	private $expiry_view		= array("2 DAYS", "1 DAY", "12 HOURS", "6 HOURS", "3 HOURS", "2 HOURS", "1 HOUR");
	private $lock_level_view 	= array("Unregistered Visitors", "Unregistered Visitors + Registered Subscribers", "Unregistered Visitors + Registered Subscribers/Contributors", "Unregistered Visitors + Registered Subscribers/Contributors/Authors");	
	
	private $fields_membership 			= array("ppmPrice" => "0.00", "ppmPriceCoin" => "0.0000", "ppmPriceLabel" => "BTC", "ppmExpiry" => "1 MONTH", "ppmLevel"  => 0, "ppmProfile" => 0, "ppmLang" => "en", "ppmCoin"  => "", "ppmOneCoin"  => "", "ppmTextAbove"  => "", "ppmTextBelow"  => "", "ppmTextAbove2"  => "", "ppmTextBelow2"  => "", "ppmTitle" => "", "ppmTitle2" => "", "ppmCommentAuthor"  => "", "ppmCommentBody"  => "", "ppmCommentReply"  => "");
	private $fields_membership_newuser 	= array("userID" => 0, "paymentID" => 0, "startDate"  => "", "endDate" => "", "disabled" => 0, "recordCreated"  => "");
	private $lock_level_membership 		= array("Registered Subscribers", "Registered Subscribers/Contributors", "Registered Subscribers/Contributors/Authors");
	
	

	/*
	 *  1. Initialize plugin
	 */
	public function __construct() 
	{

		$this->coin_names 	= self::coin_names();
		$this->coin_chain 	= self::coin_chain();
		$this->coin_www 	= self::coin_www();
		$this->languages 	= self::languages();
		
		// compatible test
		$ver = get_option(GOURL.'version');
		if (!$ver || version_compare($ver, GOURL_VERSION) < 0) $this->upgrade();
		elseif (is_admin()) gourl_retest_dir();
		
		
		// Current Page, Record ID
		$this->page = (isset($_GET['page'])) ? $_GET['page'] : "";
		$this->id 	= (isset($_GET['id']) && intval($_GET['id'])) ? intval($_GET['id']) : 0;

		$this->updated = (isset($_GET['updated']) && $_GET["updated"] == "true") ? true : false;
				
		
		// Redirect
		if ($this->page == GOURL."contact") { header("Location: ".GOURL_ADMIN.GOURL."#i7"); die; }
		if ($this->page == GOURL."addons") { header("Location: ".GOURL_ADMIN.GOURL."#j2"); die; }
		
				
		// A. General Plugin Settings
		$this->get_settings();
		if (!($_POST && $this->page == GOURL.'settings')) $this->check_settings();
		

		// B. Pay-Per-Download - New File
		if ($this->page == GOURL.'file')
		{
			$this->record_fields = $this->fields_download;
			$this->get_record('file');
			if ($this->id && !$_POST) $this->check_download();
			ini_set('max_execution_time', 3600);
			ini_set('max_input_time', 3600);
		}


		// C. Pay-Per-View
		if ($this->page == GOURL.'payperview')
		{
			$this->get_view();
			if (!$_POST) $this->check_view();
		}


		// D. Pay-Per-Membership
		if ($this->page == GOURL.'paypermembership')
		{
			$this->get_membership();
			if (!$_POST) $this->check_membership();
		}


		// E. Pay-Per-Membership - New User
		if ($this->page == GOURL.'paypermembership_user')
		{
			$this->record_fields = $this->fields_membership_newuser;
			if (!$this->id) // default for new record
			{
				$this->record["startDate"] = gmdate("Y-m-d");
				$this->record["endDate"] = gmdate("Y-m-d", strtotime("+1 month"));
				if (isset($_GET['userID']) && intval($_GET['userID'])) $this->record["userID"] = intval($_GET['userID']);
			}
		}


		// F. Pay-Per-Product - New Product
		if ($this->page == GOURL.'product')
		{
			$this->record_fields = $this->fields_product;
			$this->get_record('product');
			if ($this->id && !$_POST) $this->check_product();
		}

	
		// Admin
		if (is_admin())
		{
			if ($this->errors && $this->page != 'gourlsettings') add_action('admin_notices', array(&$this, 'admin_warning'));
			if (!file_exists(GOURL_DIR."files") || !file_exists(GOURL_DIR."images") || !file_exists(GOURL_DIR."lockimg")) add_action('admin_notices', array(&$this, 'admin_warning_reactivate'));
			add_action('admin_menu', 			array(&$this, 'admin_menu'));
			add_action('init', 					array(&$this, 'admin_init'));
			add_action('admin_head', 			array(&$this, 'admin_header'), 15);
			if (in_array($this->page, array("gourl", "gourlpayments", "gourlproducts", "gourlproduct", "gourlfiles", "gourlfile", "gourlpayperview", "gourlpaypermembership", "gourlpaypermembership_users", "gourlpaypermembership_user", "gourlsettings"))) add_action('admin_footer_text', array(&$this, 'admin_footer_text'), 15);
		} 
		else 
		{
			add_action("init", 					array(&$this, "front_init"));
			add_action("wp", 					array(&$this, "front_html"));
			add_action("wp_head", 				array(&$this, "front_header"));
			add_shortcode(GOURL_TAG_DOWNLOAD, 	array(&$this, "shortcode_download"));
			add_shortcode(GOURL_TAG_PRODUCT, 	array(&$this, "shortcode_product"));
			add_shortcode(GOURL_TAG_VIEW, 	  	array(&$this, "shortcode_view"));
			add_shortcode(GOURL_TAG_MEMBERSHIP, array(&$this, "shortcode_membership"));
			add_shortcode(GOURL_TAG_MEMCHECKOUT,array(&$this, "shortcode_memcheckout"));
		}
		
		
		// Process Callbacks from GoUrl.io Payment Server
		add_action('parse_request', array(&$this, 'callback_parse_request'));
		
	}
	

	
	/*
	 *  2.
	 */
	public function payments()
	{
		return $this->payments; 
	}

	
	/*
	 *  3.
	*/
	public static function coin_names()
	{
		return array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'XPY' => 'paycoin', 'DOGE' => 'dogecoin', 'DASH' => 'dash', 'SPD' => 'speedcoin', 'RDD' => 'reddcoin', 'POT' => 'potcoin', 'FTC' => 'feathercoin', 'VTC' => 'vertcoin', 'VRC' => 'vericoin', 'PPC' => 'peercoin');
	}
	
	
	/*
	 * 4.  
	*/
	public static function coin_chain()
	{
		return array('bitcoin' => 'https://blockchain.info/', 'litecoin' => 'https://bchain.info/LTC/', 'paycoin' => 'https://chainz.cryptoid.info/xpy/', 'dogecoin' => 'https://dogechain.info/', 'dash' => 'https://chainz.cryptoid.info/dash/', 'speedcoin' => 'http://speedcoin.co:2750/', 'reddcoin' => 'http://live.reddcoin.com/', 'potcoin' => 'http://www.potchain.net/', 'feathercoin' => 'http://explorer.feathercoin.com/', 'vertcoin' => 'https://explorer.vertcoin.org/exp/', 'vericoin' => 'https://chainz.cryptoid.info/vrc/', 'peercoin' => 'https://bkchain.org/ppc/');
	}
	
	
	/*
	 * 5.  
	*/
	public static function coin_www()
	{
		return array('bitcoin' => 'https://bitcoin.org/', 'litecoin' => 'https://litecoin.org/', 'paycoin' => 'https://paycoin.com/', 'dogecoin' => 'http://dogecoin.com/', 'dash' => 'https://www.dashpay.io/', 'speedcoin' => 'http://speedcoin.co/', 'reddcoin' => 'http://reddcoin.com/', 'potcoin' => 'http://www.potcoin.com/', 'feathercoin' => 'http://www.feathercoin.com/', 'vertcoin' => 'http://vertcoin.org/', 'vericoin' => 'http://www.vericoin.info/', 'peercoin' => 'http://peercoin.net/');
	}
	
	
	/*
	 * 6.
	*/
	public static function languages()
	{
		return array('en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'ru' => 'Russian', 'ar' => 'Arabic', 'cn' => 'Simplified Chinese', 'zh' => 'Traditional Chinese', 'hi' => 'Hindi');
	}
	

	/*
	 * 7.
	*/
	public function box_width()
	{
		return $this->options['box_width'];
	}
	
	
	/*
	 *
	*/
	public function box_height()
	{
		return $this->options['box_height'];
	}
	
	
	/*
	 * Return paymet box custom image 
	*/
	public function box_image($type = "plogin") // plogin, flogin, sold, pdisable, fdisable, nofile
	{
		$type = "img_" . $type;
		if (!isset($this->custom_images[$type])) return "";
		
		if ($this->options[$type] == 1 && $this->options[$type."url"] && file_exists(GOURL_DIR."box/".$this->options[$type.'url'])) 
			return GOURL_DIR2."box/".$this->options[$type.'url'];
		else 
			return plugins_url("/images/".$type.".png", __FILE__);  
	}
	
	
	/*
	 *  8.
	*/
	public function page_summary()
	{
		global $wpdb;
		
		

		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Summary', GOURL).$this->space(1).'<a class="add-new-h2" target="_blank" href="https://gourl.io/bitcoin-wordpress-plugin.html">' . __('version ', GOURL).GOURL_VERSION.'</a>');
		
		$tmp .= "<div class='postbox'>";
		$tmp .= "<div class='inside gourlsummary'>";
		
		foreach($this->coin_names as $k => $v)  $tmp .= '<a target="_blank" href="'.$this->coin_www[$v].'"><img width="70" hspace="20" vspace="15" alt="'.$v.'" src="'.plugins_url('/images/'.$v.'2.png', __FILE__).'" border="0"></a>';
		
		// 1
		$us_products = "";
		$dt_products = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt from crypto_products", OBJECT);
		$all_products = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'product\_%'", OBJECT);
		$tr_products = ($res) ? $res->cnt : 0;
		if ($tr_products)
		{
			$us_products = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'product\_%' order by txDate desc", OBJECT);
			$dt_products = "<span title='".__('Latest Payment to Pay-Per-Product', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a title='".__('Latest Payment', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}
				
		// 2
		$us_files = "";
		$dt_files = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt from crypto_files", OBJECT);
		$all_files = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'file\_%'", OBJECT);
		$tr_files = ($res) ? $res->cnt : 0;
		if ($tr_files)
		{
			$us_files = " ( $" . gourl_number_format($res->total, 2) . " )"; 
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'file\_%' order by txDate desc", OBJECT);
			$dt_files = "<span title='".__('Latest Payment to Pay-Per-Download', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}
		
		// 3		
		$us_membership = "";
		$dt_membership = "";
		$dt = gmdate('Y-m-d H:i:s');
		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership where startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);
		$all_users = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'membership%'", OBJECT);
		$tr_membership = ($res) ? $res->cnt : 0;
		if ($tr_membership)
		{
			$us_membership = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'membership%' order by txDate desc", OBJECT);
			$dt_membership = "<span title='".__('Latest Payment to Pay-Per-Membership', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 4
		$us_payperview = "";
		$dt_payperview = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID = 'payperview'", OBJECT);
		$tr_payperview = ($res) ? $res->cnt : 0;
		if ($tr_payperview)
		{
			$us_payperview = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID = 'payperview' order by txDate desc", OBJECT);
			$dt_payperview = "<span title='".__('Latest Payment to Pay-Per-View', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 5
		$sql_where = "";
		$us_addon = $dt_addon = $tr_addon = array();
		foreach ($this->addon as $v)
		{		
			$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like '".esc_sql($v).".%'", OBJECT);
			$tr_addon[$v] = ($res) ? $res->cnt : 0;
			if ($tr_addon[$v])
			{
				$us_addon[$v] = " ( $" . gourl_number_format($res->total, 2) . " )";
				$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like '".esc_sql($v).".%' order by txDate desc", OBJECT);
				$dt_addon[$v] = "<span title='".__('Latest Payment', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
				($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
				"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
			}
			$sql_where .= " && orderID not like '".esc_sql($v).".%'";
		}
		
		// 6
		$us_other = "";
		$dt_other = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like '%.%'".$sql_where, OBJECT);
		$tr_other = ($res) ? $res->cnt : 0;
		if ($tr_other)
		{
			$us_other = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like '%.%' ".$sql_where." order by txDate desc", OBJECT);
			$dt_other = "<span title='".__('Latest Payment to Other Plugins', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 7
		$us_unrecognised = "";
		$dt_unrecognised = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where unrecognised = 1", OBJECT);
		$tr_unrecognised = ($res) ? $res->cnt : 0;
		if ($tr_unrecognised)
		{
			$us_unrecognised = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where unrecognised = 1 order by txDate desc", OBJECT);
			$dt_unrecognised = "<span title='".__('Unrecognised Latest Payment', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}
		
		// 8
		$all_details = "";
		$dt_last = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments", OBJECT);
		$all_payments = ($res) ? $res->cnt : 0;
		if ($all_payments)
		{
			$all_details .= $this->space()."~ ".gourl_number_format($res->total, 2)." ".__('USD', GOURL);
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, amountUSD, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments order by txDate desc", OBJECT);
			$dt_last = ($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='20' border='0' style='margin-right:13px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") . 
						$res->dt.$this->space()."-".$this->space()."<a title='".__('Latest Payment', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . $this->space() . "<small>( " . gourl_number_format($res->amountUSD, 2)." ".__('USD', GOURL). " )</small>";
		}
			
		
		// Re-test MySQL connection
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
		$sql = "SELECT fileID as nme FROM crypto_files LIMIT 1";
		run_sql($sql);
		
		
		$tmp .= "<a name='i1'></a>";
		$tmp .= "<div class='gourltitle'>".__('Summary', GOURL)."</div>";
		$tmp .= "<div class='gourlsummaryinfo'>";
		$tmp .= '<div style="min-width:1200px;width:100%;">';
		
		$tmp .= "<table border='0'>";
		
		if ($tr_products || $tr_files || $tr_membership || $tr_payperview || !$all_payments)
		{	
			// 1
			$tmp .= "<tr><td>GoUrl Pay-Per-Product</td><td><a href='".GOURL_ADMIN.GOURL."products'>".sprintf(__('%s  paid products', GOURL), $all_products)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=products'>".$tr_products."</a> ".__('payments', GOURL).$us_products."</small></td><td><small>".$dt_products."</small></td></tr>";
			// 2
			$tmp .= "<tr><td>GoUrl Pay-Per-Download</td><td><a href='".GOURL_ADMIN.GOURL."files'>".sprintf(__('%s  paid files', GOURL), $all_files)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=files'>".$tr_files."</a> ".__('payments', GOURL).$us_files."</small></td><td><small>".$dt_files."</small></td></tr>";
			// 3		
			$tmp .= "<tr><td>GoUrl Pay-Per-Membership</td><td><a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=active'>".sprintf(__('%s  premium users', GOURL), $all_users)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=membership'>".$tr_membership."</a> ".__('payments', GOURL).$us_membership."</small></td><td><small>".$dt_membership."</small></td></tr>";
			// 4
			$tmp .= "<tr><td>GoUrl Pay-Per-View</td><td></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=payperview'>".$tr_payperview."</a> ".__('payments', GOURL).$us_payperview."</small></td><td><small>".$dt_payperview."</small></td></tr>";
		}

		// 5
		foreach ($us_addon as $k => $v)
		{
			if ($k == "gourlwoocommerce") 		$nme = "GoUrl WooCommerce";
			elseif ($k == "gourlwpecommerce") 	$nme = "GoUrl WP eCommerce";
			elseif ($k == "gourljigoshop") 		$nme = "GoUrl Jigoshop";
			elseif ($k == "gourlappthemes") 	$nme = "GoUrl AppThemes";
			elseif ($k == "gourlmarketpress") 	$nme = "GoUrl MarketPress";
			elseif ($k == "gourlpmpro") 		$nme = "GoUrl Paid Memberships Pro";
			elseif ($k == "gourlgive") 			$nme = "GoUrl Give/Donations";
			elseif (strpos($k, "gourl") === 0) 	$nme = "GoUrl " . ucfirst(str_replace("gourl", "", $k));
			else 								$nme = ucfirst($k);
			
			$tmp .= "<tr><td>".$nme."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=".$k."'>".$tr_addon[$k]." ".__('payments', GOURL)."</a> ".$us_addon[$k]."</small></td><td><small>".$dt_addon[$k]."</small></td></tr>";
		}	
		
		// 6
		$tmp .= "<tr><td>".__('Other Plugins with GoUrl', GOURL)."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=plugins'>".$tr_other." ".__('payments', GOURL)."</a> ".$us_other."</small></td><td><small>".$dt_other."</small></td></tr>";
		// 7
		$tmp .= "<tr><td>".__('Unrecognised Payments', GOURL)."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=unrecognised'>".$tr_unrecognised." ".__('payments', GOURL)."</a> ".$us_unrecognised."</small></td><td><small>".$dt_unrecognised."</small></td></tr>";
		// 8
		$tmp .= "<tr><td><small>---------</small><br />".__('Total Received', GOURL)."</td><td colspan='2'><br /><a href='".GOURL_ADMIN.GOURL."payments'>".sprintf(__('%s payments', GOURL), $all_payments)."</a>".$all_details."</td></tr>";
		$tmp .= "<tr><td><a name='chart' id='chart'></a>".__('Recent Payment', GOURL)."</td><td colspan='3'>".$dt_last."</td></tr>";
		$tmp .= "</table>";
		
		$charts = array('BTC' => 7777, 'LTC' => 3, 'XPY' => 466, 'DOGE' => 132, 'DASH' => 155, 'RDD' => 169, 'POT' => 173, 'FTC' => 5, 'VTC' => 151, 'VRC' => 209, 'PPC' => 28);
		$chart = (isset($_GET["chart"]) && isset($charts[$_GET["chart"]])) ? $_GET["chart"] : "BTC";
		
		$days = array(5=>"5 days", 10=>"10 days", 15=>"15 days", 31=>"1 month", 60=>"2 months", 90=>"3 months",120=>"4 months",180=>"6 months",240=>"9 months",360=>"1 year");
		$day = (isset($_GET["days"]) && isset($days[$_GET["days"]])) ? $_GET["days"] : 120;
		
		$tmp .= "<div style='margin:90px 0 30px 0;height:auto;'>";
		$tmp .= "<iframe width='1200' height='500' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='https://myip.ms/crypto.php?m=".$charts[$chart]."&amp;d=".$day."&amp;a=2&amp;c18=dddddd&amp;c19=dddddd&amp;h=500&amp;w=1200&amp;t=usd".($this->options['chart_reverse']?"":"&amp;r=1")."'></iframe>";
		$tmp .= "<div>";
		$tmp .= '<select id="'.GOURL.'chart" onchange="window.location.href = \''.admin_url('admin.php?page='.GOURL.'&amp;days='.$day).'&amp;chart=\'+this.options[this.selectedIndex].value+\'#chart\';">';
		foreach($this->coin_names as $k => $v) if (isset($charts[$k])) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $chart).'>'.ucfirst($v).$this->space().'('.$k.')</option>';
		$tmp .= '</select>';
		$tmp .= '<select id="'.GOURL.'days" onchange="window.location.href = \''.admin_url('admin.php?page='.GOURL.'&amp;chart='.$chart).'&amp;days=\'+this.options[this.selectedIndex].value+\'#chart\';">';
		foreach($days as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $day).'>'.__($v, GOURL).'</option>';
		$tmp .= '</select>' . $this->space(3);
		$url = ($chart == "BTC") ? "http://myip.ms/browse/market_bitcoin/Bitcoin_Price_History.html" : "http://myip.ms/view/market_coins/".$charts[$chart]."/".$this->coin_names[$chart]."_analytics.html";
		$tmp .= "<a class='".GOURL."smalltext' target='_blank' href='".$url."'>".__(ucfirst($this->coin_names[$chart])." analytics on altcoins.wiki &#187", GOURL)."</a>";
		$tmp .="</div>";
		$tmp .="</div>";
		
		$tmp .="</div></div>";
		
		$tmp .= "<div class='gourlimgphone'><a target='_blank' href='https://gourl.io/'><img src='".plugins_url('/images/screen.png', __FILE__)."' border='0'></a></div>";
		
		
		$tmp .= "<a name='i2'></a>";
		$tmp .= "<br><br><br><br>";
		$tmp .= "<div class='gourltitle'>".__('What Makes Us Unique', GOURL)."</div>";
		
		$tmp .="<div class='gourllist'>";
		
		$img  = "<img title='".__('Example', GOURL)."' class='gourlimgpreview' src='".plugins_url('/images/example.png', __FILE__)."' border='0'>";
		$tmp .= "<ul>";
		$tmp .= "<li> ".__('100% Free Open Source on <a target="_blank" href="https://github.com/cryptoapi/">Github.com</a>', GOURL)."</li>";
		$tmp .= "<li> ".__('Accept Bitcoin/Altcoin Payments Online on your Website and use our Bitcoin <a href="#addon">Free Add-ons</a>.', GOURL)."</li>";
		$tmp .= "<li> ".sprintf(__('No Monthly Fee, Transaction Fee from 0%%. Set your own prices in USD, <a href="%s">EUR, CNY, RUB, IDR... (100 currencies)</a>', GOURL), "https://wordpress.org/plugins/gourl-woocommerce-bitcoin-altcoin-payment-gateway-addon/")."</li>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-Download</a> - simple solution for your <b>unregistered</b> visitors: make money on file downloads <a target="_blank" href="http://gourl.io/lib/examples/pay-per-download-multi.php">'.$img.'</a>', GOURL), GOURL_ADMIN.GOURL."files")."</li>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-View/Page</a> - for your <b>unregistered</b> visitors: offer paid access to your premium content/videos <a target="_blank" href="http://gourl.io/lib/examples/pay-per-page-multi.php">'.$img.'</a>', GOURL), GOURL_ADMIN.GOURL."payperview")."</li>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-Membership</a> - for your <b>registered users</b>: offer paid access to your premium content, custom <a href="%s">actions</a> <a target="_blank" href="http://gourl.io/lib/examples/pay-per-membership-multi.php">'.$img.'</a>', GOURL), GOURL_ADMIN.GOURL."paypermembership", plugins_url('/images/dir/membership_actions.txt', __FILE__))."</li>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-Product</a> - advanced solution for your <b>registered users</b>: sell any products on website, invoices with buyer confirmation email, etc <a target="_blank" href="http://gourl.io/lib/examples/pay-per-product-multi.php">'.$img.'</a>', GOURL), GOURL_ADMIN.GOURL."products")."</li>";
		$tmp .= "<li> ".__('<a href="#addon">Working with third-party plugins</a> - good support for third party plugins (WoCommerce, Jigoshop, bbPress, AppThemes, etc)', GOURL)."</li>";
		$tmp .= "<li> ".__('No Chargebacks, Global, Secure, Anonymous. All in automatic mode', GOURL)."</li>";
		$tmp .= "<li> ".__('Support Bitcoin, Litecoin, Paycoin, Dogecoin, Dash, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin, Peercoin payments', GOURL)."</li>";
		$tmp .= "<li> ".__('<b>Auto Synchronization</b> - between payments data stored on your GoUrl.io account and your Website. If GoUrl attempts to deliver a payment notification/transaction confirmation but your website is unavailable, the notification is stored on the queue, and delivered to the your website when it becomes available (re-check connection with your website every hour).', GOURL)."</li>";
		$tmp .= "<li> ".__('Free <a href="https://gourl.io/view/contact/Contact_Us.html">Plugin Support</a> and <a href="#addon">Free Add-ons</a> for You', GOURL)."</li>";
		$tmp .= "</ul>";
		
		$tmp .= "<a name='j2'></a>";
		$tmp .= "</div>";

		$tmp .= "<a name='addon'></a>";
		$tmp .= "<br><br><br><br>";
		$tmp .= "<div class='gourltitle'>".__('Free Bitcoin Gateway Add-ons', GOURL)."</div>";
		$tmp .= "<p>".__('The following Add-ons extend the functionality of GoUrl -', GOURL);
		$tmp .= '<a style="margin-left:20px" target="_blank" href="https://wordpress.org/plugins/browse/author/?author=gourl" class="button-primary">'.__('All Add-ons on Wordpress.prg', GOURL).'<span class="dashicons dashicons-external"></span></a>';
		$tmp .= '<a style="margin-left:30px" href="'.admin_url('plugin-install.php?tab=search&type=author&s=gourl').'" class="button-primary">'.__('View on Add Plugins Page', GOURL).'<span class="dashicons dashicons-external"></span></a>';
		$tmp .= "</p>";
		
		$tmp .= "<table class='gourltable gourltable-addons'>";
		$tmp .= "<tr><th style='width:10px'></th><th>".__('Bitcoin/Altcoin Gateway', GOURL)."</th><th style='padding-left:60px'>".__('Description', GOURL)."</th><th>".__('Homepage', GOURL)."</th><th>".__('Wordpress.org', GOURL)."</th><th>".__('Installation pages', GOURL)."</th></tr>";
		$tmp .= "<tr><td class='gourlnum'>1.</td><td><a target='_blank' href='https://wordpress.org/plugins/woocommerce/'><img src='".plugins_url('/images/logos/woocommerce.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway for wordpress E-Commerce - <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce 2.1+</a>', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-woocommerce-bitcoin-altcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Woocommerce'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce+excelling+eCommerce+WooThemes+Beautifully')."'>".__('WooCommerce', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>2.</td><td><a target='_blank' href='https://wordpress.org/plugins/bbpress/'><img src='".plugins_url('/images/logos/bbpress.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('This addon will add Premium Membership and Bitcoin Gateway to <a target="_blank" href="https://wordpress.org/plugins/bbpress/">bbPress 2.5+</a> Forum / Customer Support System.<br>You can mark some topics on your forum/customer support system as Premium and can easily monetise it with Bitcoins/altcoins - pay to read / pay to create / add new replies to the topic, etc.<br>You can add premium user support to your web site using <a target="_blank" href="https://wordpress.org/plugins/bbpress/">bbPress</a>. Any user can place questions (create new topic in bbPress),  and only paid/premium users will see your answers.', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bbpress-premium-membership.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bbpress-premium-membership.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bbpress-premium-membership-bitcoin-payments/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/bbPress-Premium-Membership-Bitcoins'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=bbPress+forum+keeping+lean')."'>".__('bbPress', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>3.</td><td><a target='_blank' href='https://wordpress.org/plugins/give/'><img src='".plugins_url('/images/logos/give.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Bitcoin/Altcoin & Paypal Donations in Wordpress. Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target="_blank" href="https://wordpress.org/plugins/give/">Give 0.8+</a> - easy to use wordpress donation plugin for accepting bitcoins, altcoins, paypal, authorize.net, stripe, paymill donations directly onto your website.', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-donations-wordpress-plugin.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-donations-wordpress-plugin.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bitcoin-paypal-donations-give-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Paypal-Donations-Wordpress'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+donation+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=Give+Democratizing+Generosity')."'>".__('Give', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>4.</td><td><a target='_blank' href='https://www.appthemes.com/themes/'><img src='".plugins_url('/images/logos/appthemes.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway and Escrow for all <a target="_blank" href="https://www.appthemes.com/themes/">AppThemes Premium Themes</a> - Classipress, Vantage, JobRoller, Clipper, Taskerr, HireBee, Ideas, Quality Control, etc.', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-appthemes-bitcoin-payments-classipress-vantage-jobroller/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Appthemes'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+appthemes+escrow')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='https://www.appthemes.com/themes/'>".__('AppThemes', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>5.</td><td><a target='_blank' href='https://wordpress.org/plugins/paid-memberships-pro/'><img src='".plugins_url('/images/logos/paid-memberships-pro.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway for advanced wordpress membership plugin - <a target="_blank" href="https://wordpress.org/plugins/paid-memberships-pro/">Paid Memberships Pro 1.8+</a>', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bitcoin-paid-memberships-pro/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Gateway-Paid-Memberships-Pro'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+paid+memberships+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=paid+memberships+pro+easiest+level')."'>".__('PaidMembPro', GOURL)." &#187;</a><br><a style='font-size:12px;margin-left:20px' target='_blank' href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html#notes'>".__('Important Notes', GOURL)."</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>6.</td><td><a target='_blank' href='https://wordpress.org/plugins/jigoshop/'><img src='".plugins_url('/images/logos/jigoshop.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target="_blank" href="https://wordpress.org/plugins/jigoshop/">Jigoshop 1.12+</a>', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-jigoshop.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-jigoshop.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-jigoshop-bitcoin-payment-gateway-processor/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Jigoshop'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+jigoshop+processor')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=jigoshop+excellent+performance')."'>".__('Jigoshop', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>7.</td><td><a target='_blank' href='https://wordpress.org/plugins/wp-e-commerce/'><img src='".plugins_url('/images/logos/wp-ecommerce.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target="_blank" href="https://wordpress.org/plugins/wp-e-commerce/">WP eCommerce 3.8.10+</a>', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-wp-ecommerce.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-wp-ecommerce.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-wp-ecommerce-bitcoin-altcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-WP-eCommerce'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+wp+ecommerce+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=wp+ecommerce+empowers+sell+anything')."'>".__('WP eCommerce', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>8.</td><td><a target='_blank' href='https://wordpress.org/plugins/wordpress-ecommerce/'><img src='".plugins_url('/images/logos/marketpress.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".__('Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target="_blank" href="https://wordpress.org/plugins/wordpress-ecommerce/">MarketPress 2.9+</a>', GOURL)."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-wpmudev-marketpress-bitcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-MarketPress'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+marketpress+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=marketpress+WordPress+eCommerce+Beautiful')."'>".__('MarketPress', GOURL)." &#187;</a><br><a style='font-size:12px;margin-left:20px' target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html#notes'>".__('Important Notes', GOURL)."</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>9.</td><td><a target='_blank' href='https://gourl.io/affiliates.html'><img src='".plugins_url('/images/logos/affiliate.png', __FILE__)."' border='0'></a><td colspan='4' class='gourldesc'><h4>".__('Bitcoin/Altcoin Payments for Any Other Wordpress Plugins', GOURL)."</h4>";
		$tmp .= __('Other wordpress plugin developers can easily integrate Bitcoin payments to their own plugins (<a target="_blank" href="https://github.com/cryptoapi/Bitcoin-Payments-Appthemes/blob/master/gourl-appthemes.php">source example</a> and <a target="_blank" href="https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html#screenshot">result</a>) using this GoUrl Plugin with payment gateway functionality. Please ask Wordpress Plugin Developers to add <a href="#i6">a few lines of code below</a> to their plugins (gourl bitcoin payment gateway with optional <a target="_blank" href="https://gourl.io/affiliates.html">Bitcoin Affiliate Program - 33.3% lifetime revenue share</a> for them) and bitcoin/litecoin/dogecoin/etc payments will be automatically used in their plugins. It\'s easy!', GOURL);
		$tmp .= "</td></tr>";
		$tmp .= "<tr><td class='gourlnum'>10.</td><td colspan='5'><h3>Webmaster Spelling Notifications Plugin</h3>Plugin allows site visitors to send reports to the webmaster/owner about any spelling or grammatical errors. Spelling checker on your website. <a href='https://gourl.io/php-spelling-notifications.html#live'>Live Demo</a>";
		$tmp .= "<div style='margin:20px 0 10px 0'>";
		$tmp .= "<a target='_blank' href='https://gourl.io/php-spelling-notifications.html'>".__('Plugin Homepage', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a target='_blank' href='https://wordpress.org/plugins/gourl-spelling-notifications/'>".__('Wordpress Page', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a target='_blank' href='https://github.com/cryptoapi/Wordpress-Spelling-Notifications'>".__('Open Source', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+spelling')."'>".__('Install Now', GOURL)." &#187;</a>";
		$tmp .= "</div>";
		$tmp .= "<a target='_blank' href='https://wordpress.org/plugins/gourl-spelling-notifications/'><img src='".plugins_url('/images/logos/spelling.png', __FILE__)."' border='0'></a>";
		$tmp .= "<a name='i3'></a>";
		$tmp .= "</td></tr>";
		$tmp .= "</table>";
		
		
		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>3. ".__('GoUrl Instruction', GOURL)."</div>";
		
		$tmp .= "<ul class='gourllist'>";
		$tmp .= "<li> ".__('Free <a target="_blank" href="https://gourl.io/view/registration">Register</a> or <a target="_blank" href="https://gourl.io/info/memberarea/My_Account.html">Login</a> on GoUrl.io - Global Bitcoin Payment Gateway', GOURL)."</li>";
		$tmp .= "<li> ".__('Create <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0">Payment Box</a> Records for all coin types you will accept on your website', GOURL)."</li>";
		$tmp .= "<li> ".sprintf(__('You will need to place Callback URL on Gourl.io, please use: <b>%s</b>', GOURL), trim(get_site_url(), "/ ")."/?cryptobox.callback.php")."</li>";
		$tmp .= "<li> ".sprintf(__('You will get Free GoUrl Public/Private keys from new created <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0">payment box</a>, save them on <a href="%s">Settings Page</a>'), GOURL_ADMIN.GOURL."settings")."</li>";
		$tmp .= "</ul>";
		
		$tmp .= "<p>".__('THAT\'S IT! YOUR WEBSITE IS READY TO ACCEPT BITCOINS ONLINE!', GOURL)."</p>";
		
		$tmp .= "<br><p>".__('<b>Testing environment</b>: You can use <a target="_blank" href="http://speedcoin.co/info/free_coins/Free_Speedcoins.html">110 free Speedcoins</a> or <a target="_blank" href="http://goo.gl/L8H9gG">Dogecoins</a> for testing', GOURL);
		$tmp .= "<a name='i4'></a>";
		$tmp .= "</p>";
		
	
	
	
		$tmp .= "<br><br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>4. ".__('Differences between Pay-Per-View and Pay-Per-Membership', GOURL)."</div>";
	
		$tmp .= "<div class='gourlimginstruction'>";
		$tmp .= '<a target="_blank" title="'.__('Click to see full size image', GOURL).'" href="'.plugins_url('/images/tagexample_membership_full.png', __FILE__).'"><img width="400" height="379" alt="'.__('Add GoUrl Shortcodes to pages. Example', GOURL).'" src="'.plugins_url('/images/tagexample.png', __FILE__).'" border="0"></a>';
		$tmp .= "</div>";
		
		
		$tmp .= "<ul class='gourllist'>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-View</a> - shortcode <b>['.GOURL_TAG_VIEW.']</b> - you can use it for unregistered website visitors. Plugin will automatically generate a unique user identification for every user and save it in user browser cookies.
						User can have a maximum of 2 days membership with Pay-Per-View and after they will need to pay again.
				 		Because if a user clears browser cookies, they will lose their membership and a new payment box will be displayed.', GOURL), GOURL_ADMIN.GOURL."payperview")."</li>";
		$tmp .= "<li> ".sprintf(__('<a href="%s">Pay-Per-Membership</a> - shortcode <b>['.GOURL_TAG_MEMBERSHIP.']</b> - similar to pay-per-view but for registered users only. It is a better safety solution because plugin uses registered userID not cookies.
						And a membership period from 1 hour to 1 year of your choice. You need to have website <a href="%s">registration enabled</a>.', GOURL), GOURL_ADMIN.GOURL."paypermembership", admin_url('options-general.php'))."</li>";
		$tmp .= "<li> ".__('You can use <b>custom actions with Pay-Per-Membership</b> on your website (premium and free webpages).<br>For example, hide ads for premium users, php code below - ', GOURL)."<br>";
		$tmp .= "<a href='".plugins_url('/images/dir/membership_actions.txt', __FILE__)."'><img src='".plugins_url('/images/paypermembership_code.png', __FILE__)."'></a>";
		$tmp .= "</li>";
		$tmp .= "<li> ".__('You can use <b>custom actions with Pay-Per-View</b> on your website too -', GOURL)."<br>";
		$tmp .= "<a href='".plugins_url('/images/dir/payperview_actions.txt', __FILE__)."'><img src='".plugins_url('/images/payperview_code.png', __FILE__)."'></a>";
		$tmp .= "</li>";
		$tmp .= "<li> ".sprintf(__('<b>Pay-Per-Membership</b> integrated with <a href="%s">bbPress Forum/Customer Support</a> also ( use our <a href="%s">GoUrl bbPress Addon</a> ). You can mark some topics on your bbPress as Premium and can easily monetise it with Bitcoins/altcoins.', GOURL), admin_url('plugin-install.php?tab=search&type=term&s=bbPress+forum+keeping+lean'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics'))."</li>";
		$tmp .= "<li> ".__('<b>Both solutions</b> - Pay-Per-Membership and Pay-Per-View hide content on premium pages from unpaid users/visitors and allow to use custom actions on free website pages; Pay-Per-Membership provides premium membership mode in <a href="https://wordpress.org/plugins/bbpress/">bbPress</a> also.', GOURL)."</li>";
		$tmp .= "<li> ".__('If a visitor goes to a premium page and have not logged in -<br>
						Pay-Per-View will show a payment box and accept payments from the unregistered visitor.<br>
						Pay-Per-Membership will show a message that the user needs to login/register on your website first and after show a payment box for logged in users only.', GOURL)."</li>";
		$tmp .= "</ul>";
		
		$tmp .= "<br><p>";
		$tmp .= sprintf(__("For example, you might offer paid access to your 50 website premium pages/posts for the price of 1 USD for 2 DAYS giving unlimited access to all locked pages for website visitors (<span class='gourlnowrap'>non-registered</span> visitors or registered users). You can add simple shortcode <a href='%s'>[".GOURL_TAG_VIEW."]</a> or <a href='%s'>[".GOURL_TAG_MEMBERSHIP."]</a> or <a href='%s'>your custom code</a> for all those fifty WordPress premium pages/posts. When visitors go on any of those pages, they will see automatic cryptocoin payment box (the original page content will be hidden). After visitor makes their payment, they will get access to original pages content/videos and after 2 days will see a new payment box. Visitor needs to make payment on any locked page and they will get access to all other locked pages also.<br>Also you can <a href='%s'>show ads</a> for unpaid users on free webpages, etc.<br><br><b>Notes:</b><br>- Do not use [".GOURL_TAG_VIEW."] and [".GOURL_TAG_MEMBERSHIP."] together on the same page.<br>- Website Editors / Admins will have all the time full access to locked pages and see original page content", GOURL), GOURL_ADMIN.GOURL."payperview", GOURL_ADMIN.GOURL."paypermembership", plugins_url('/images/paypermembership_code.png', __FILE__), plugins_url('/images/paypermembership_code.png', __FILE__));
		$tmp .= "<a name='i5'></a>";
		$tmp .= "</p>";
		
		
		
		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>5. ".__('Adding Custom Actions after Payment has been received', GOURL)."</div>";
		$tmp .= "<p><b>".__('Using for Pay-Per-Product, Pay-Per-Download, Pay-Per-View, Pay-Per-Membership only', GOURL)."</b></p>";
		$tmp .= "<p>".sprintf(__('Optional - You can use additional actions after a payment has been received (for example create/update database records, etc) using gourl instant payment notification system. Simply edit php file <a href="%s">gourl_ipn.php</a> in directory %s and add section with your order_ID in function <b>gourl_successful_payment</b>($user_ID = 0, $order_ID = "", $payment_details = array(), $box_status = "").', GOURL), plugins_url('/images/dir/gourl_ipn.default.txt', __FILE__), GOURL_DIR2."files/"); 
		$tmp .= __('This function will appear every time when a new payment from any user is received successfully. Function gets user_ID - user who made payment, current order_ID (the same value as at the bottom of record edit page) and payment details as array. ', GOURL)."</p>";
		
		$tmp .= "<p><a target='_blank' href='https://gourl.io/affiliates.html#wordpress'><img alt='".__('Example of PHP code', GOURL)."' src='".plugins_url('/images/output.png', __FILE__)."' border='0'></a></p>";
		$tmp .= "<br><p>".__('P.S. If you use <a href="#addon">separate add-on</a> with gourl payment gateway, you can add your custom actions inside of function <b>..addonname.."_gourlcallback"</b> ($user_ID = 0, $order_ID = "", $payment_details = array(), $box_status = "") That function will appear when a payment is received. Variables sent to that add-on function identically  to variables sent in function gourl_successful_payment(), see screenshot above.', GOURL);
		$tmp .= "<a name='i6'></a></p>";
		
		
		
		
		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>6. ".__('Bitcoin Payments with Any Other Wordpress Plugins', GOURL)."</div>";
		$tmp .= "<p>".__('<b>Other wordpress plugin developers can easily integrate Bitcoin payments to their own plugins</b> using this plugin with payment gateway functionality. For example, see other plugin <a target="_blank" href="https://github.com/cryptoapi/Bitcoin-Payments-Appthemes/blob/master/gourl-appthemes.php">PHP source code</a> and <a target="_blank" href="https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html#screenshot">result</a> - Bitcoin payments for Appthemes, which uses this plugin functionality. Please ask Wordpress Plugin Developers to add a few lines of code below to their plugins (gourl bitcoin payment gateway with optional <a target="_blank" href="https://gourl.io/affiliates.html">Bitcoin Affiliate Program - 33.3% lifetime revenue share</a> for them ) and bitcoin/altcoin payments will be automatically used in their plugins. GoUrl Payment Gateway will do all the work - display payment form, process received payments, etc and will submit that information to the plugin used. Around 5 seconds after cryptocoin payment is made, user will see confirmation on your webpage with any wordpress plugin that payment is received (i.e. very fast).', GOURL)."</p>";
		$tmp .= "<p>".sprintf(__('<b>Beneficial for You and other Websites.</b> Simply use this GoUrl Bitcoin Gateway wordpress plugin which will automatically be used by other plugins and you will only need to enter your bitcoin/litecoin/dogecoin wallet addresses once. No multiple times, for different plugins. Also you will see the bitcoin/altcoin payment statistics in one common table "<a href="%s">All Payments</a>" with details of all received payments. So it is easy to control everything. Of course, other plugins can also show bitcoin/altcoin transactions which link to other plugins.', GOURL), GOURL_ADMIN.GOURL."payments")."</p>";
		
		$tmp .= "<br/><h3>".__('Example of Bitcoin Payment Gateway code for other wordpress plugins -', GOURL)."<br/>";
		$tmp .= "<a target='_blank' href='https://gourl.io/affiliates.html#wordpress'><img alt='".__('Example of PHP code', GOURL)."' src='".plugins_url('/images/script.png', __FILE__)."' border='0'></a>";
		$tmp .= "</h3>";
		$tmp .= "<p>And add custom actions after payment has been received. <a target='_blank' href='https://gourl.io/affiliate-bitcoin-wordpress-plugins.html'>".__('Integration Instruction &#187;', GOURL)."</a>";
		$tmp .= "<a name='i7'></a>";
		$tmp .= "</p>";
		
		
		
		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>7. ".__('GoUrl Contacts', GOURL)."</div>";

		$btc = "1KPBVmXLeY6MCDMPJfKHcTnf4P2SW3b46U";
		$ltc = "LarmyXoQpydpUCYHx9DZeYoxcQ4YzMfHDt";
		$spd = "SiDHas473qf8JPJFvFLcNuAAnwXhxtvv9s";
		$doge = "DNhHdAxV7CCqjPuwg2W4qTESd5jkF7iC1C";
		$pay = "PMLDPeS1j7W5e4mYRwgsqs3a7Bnv2LKkx9";
		$dash = "XfMTeciUUZEvRRHB49qaY9Jzi1E5HAJawJ";
		$rdd = "RmB8ysK4YG4D3axNPHsKEoqxvg5KwySSJz";
		$pot = "PKwNNWo6YdweQk2F87UDGp84TQK878PWho";
		$ftc = "6otKdaB1aasmQ5kA9wKBXJM5mi9e19VxYQ";
		$vtc = "VeRUojCEkZn9u8AswqiKvpfHW4BW8Uas7V";
		$vrc = "VMr4YsLufTgx5ForMV7nP2sQJSSbec593f";
		$ppc = "PUxNprg24a8JjgG5pETKqesSiC5HprutvB";
		
		$tmp .= "<p>".__('Please contact us with any questions - ', GOURL)."<a href='https://gourl.io/view/contact/Contact_Us.html'>https://gourl.io/view/contact/Contact_Us.html</a></p>";
		
		$tmp .= "<p>".__("A great way to get involved in open source is to contribute to the existing projects you're using. GitHub is home to more than 5 million open source projects. <a target='_blank' href='http://readwrite.com/2014/07/02/github-pull-request-etiquette'>A pull request</a> is a method of submitting contributions to an open development project. You can create a pull request with your new add-ons/code for this plugin <a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Wordpress-Plugin'>here &#187;</a>", GOURL) ."</p>";
		$tmp .= "<br><br>";
		
		$tmp .= "<div style='float:right;margin:20px 20px 100px 0;width:570px'>";
		$tmp .= "<h3>".__('Buttons For Your Website -', GOURL)."</h3>";
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/gourl.png', __FILE__).'" border="0">';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/gourlpayments.png', __FILE__).'" border="0"><br/>';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/bitcoin_accepted.png', __FILE__).'" border="0">';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/bitcoin_donate.png', __FILE__).'" border="0"><br/>';
		foreach($this->coin_names as $k => $v)  $tmp .= '<img width="70" hspace="10" vspace="10" alt="'.$v.'" src="'.plugins_url('/images/'.$v.'2.png', __FILE__).'" border="0"> ';
		$tmp .= "<br><br><br>";
		$tmp .= "<img width='570' src='".plugins_url('/images/coins.png', __FILE__)."' border='0'>";
		$tmp .= "</div>";
		
		$tmp .= "<div style='margin:50px 0'>";
		$tmp .= "<h3>".__('Our Project Donation Addresses -', GOURL)."</h3>";
		$tmp .= "<p>Bitcoin: &#160; <a href='bitcoin:".$btc."?label=Donation'>".$btc."</a></p>";
		$tmp .= "<p>Litecoin: &#160; <a href='litecoin:".$ltc."?label=Donation'>".$ltc."</a></p>";
		$tmp .= "<p>Speedcoin: &#160; <a href='speedcoin:".$spd."?label=Donation'>".$spd."</a></p>";
		$tmp .= "<p>Dogecoin: &#160; <a href='dogecoin:".$doge."?label=Donation'>".$doge."</a></p>";
		$tmp .= "<p>Paycoin: &#160; <a href='paycoin:".$pay."?label=Donation'>".$pay."</a></p>";
		$tmp .= "<p>Dash: &#160; <a href='dash:".$dash."?label=Donation'>".$dash."</a></p>";
		$tmp .= "<p>Reddcoin: &#160; <a href='reddcoin:".$rdd."?label=Donation'>".$rdd."</a></p>";
		$tmp .= "<p>Potcoin: &#160; <a href='potcoin:".$pot."?label=Donation'>".$pot."</a></p>";
		$tmp .= "<p>Feathercoin: &#160; <a href='feathercoin:".$ftc."?label=Donation'>".$ftc."</a></p>";
		$tmp .= "<p>Vertcoin: &#160; <a href='vertcoin:".$vtc."?label=Donation'>".$vtc."</a></p>";
		$tmp .= "<p>Vericoin: &#160; <a href='vericoin:".$vrc."?label=Donation'>".$vrc."</a></p>";
		$tmp .= "<p>Peercoin: &#160; <a href='peercoin:".$ppc."?label=Donation'>".$ppc."</a></p>";
		$tmp .= "</div>";
		$tmp .= "<br><br><br><br><br><br><br>";
		
		
		
		
		
		$tmp .= "</div>";
		$tmp .= "</div>";
		$tmp .= "</div>";
		
		echo $tmp;
		
		return true;
	} 
	
	
	
	
	
	
	
	
	// list -
	// function get
	// function post
	// function check
	// function save
	// function adminpage
	// function shortcode
	
	
	/**************** A. GENERAL OPTIONS ************************************/
	
	
	/*
	 *  9. Get values from the options table
	*/
	private function get_settings()
	{

		$arr = array("box_width"=>530, "box_height"=>230, "box_border"=>"", "box_style"=>"", "message_border"=>"", "message_style"=>"", "login_type"=>"", "rec_per_page"=>20, "popup_message"=>__('It is a Paid Download ! Please pay below', GOURL), "file_columns"=>"", "chart_reverse"=>"");
		foreach($arr as $k => $v) $this->options[$k] = "";

		foreach($this->custom_images as $k => $v)
		{
			$this->options[$k] = 0;
			$this->options[$k."2"] = "";
			$this->options[$k."url"] = "";
		}
				
		foreach($this->coin_names as $k => $v)
		{
			$this->options[$v."public_key"] = "";
			$this->options[$v."private_key"] = "";
		}
			
		foreach ($this->options as $key => $value) 
		{
			$this->options[$key] = get_option(GOURL.$key);
		}
		
		// default
		foreach($arr as $k => $v) 
		{
			if (!$this->options[$k]) $this->options[$k] = $v;
		}

		foreach($this->custom_images as $k => $v)
		{
			if (!$this->options[$k."url"]) $this->options[$k] = 0;
		}
		
		return true;
	}
	
	
	
	/*
	 *  10.
	*/
	private function post_settings()
	{
		foreach ($this->options as $key => $value)
		{
			$this->options[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options[$key])) $this->options[$key] = trim($this->options[$key]);
		}
	
		return true;
	}

	
	
	/*
	 *  11.
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
		
		if ($f)  $this->errors[] = sprintf(__('You need at least one payment method. Please enter your GoUrl Public/Private Keys. <a href="%s#i3">Instruction here &#187;</a> ', GOURL), GOURL_ADMIN.GOURL);

		if (!is_numeric($this->options["box_width"]) || round($this->options["box_width"]) != $this->options["box_width"] || $this->options["box_width"] < 480 || $this->options["box_width"] > 700) $this->errors[] = __('Invalid Payment Box Width. Allowed 480..700px', GOURL);
		if (!is_numeric($this->options["box_height"]) || round($this->options["box_height"]) != $this->options["box_height"] || $this->options["box_height"] < 200 || $this->options["box_height"] > 400) $this->errors[] = __('Invalid Payment Box Height. Allowed 200..400px', GOURL);

		if (!is_numeric($this->options["rec_per_page"]) || round($this->options["rec_per_page"]) != $this->options["rec_per_page"] || $this->options["rec_per_page"] < 5 || $this->options["rec_per_page"] > 200) $this->errors[] = __('Invalid Records Per Page value. Allowed 5..200', GOURL);

		if (mb_strlen($this->options["popup_message"]) < 15 || mb_strlen($this->options["popup_message"]) > 400) $this->errors[] = __('Invalid Popup Message text size. Allowed 15 - 400 characters text length', GOURL);
		
		if ($this->options["box_style"] && (in_array($this->options["box_style"][0], array("'", "\"")) || $this->options["box_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["box_style"]))) $this->errors[] = __('Invalid Payment Box Style', GOURL);
		if ($this->options["message_style"] && (in_array($this->options["message_style"][0], array("'", "\"")) || $this->options["message_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["message_style"]))) $this->errors[] = __('Invalid Payment Messages Style', GOURL);
		

		// upload files
		if ($_FILES && $_POST && $this->page == GOURL.'settings')
		{
			foreach($this->custom_images as $k => $v)
			{
				$file = (isset($_FILES[GOURL.$k."2"]["name"]) && $_FILES[GOURL.$k."2"]["name"]) ? $_FILES[GOURL.$k."2"] : "";
				if ($file) 
				{
					if ($this->options[$k."url"] && file_exists(GOURL_DIR."box/".$this->options[$k.'url'])) unlink(GOURL_DIR."box/".$this->options[$k.'url']);
					$this->options[$k."url"] = $this->upload_file($file, "box");
					
				}
			}
			if ($this->record_errors) $this->errors = array_merge($this->errors, $this->record_errors); 
		}
		
		return true;
	}
	
	
	
	
	/*
	 *  12.
	*/
	private function save_settings()
	{
		foreach ($this->options as $key => $value)
		{
 			update_option(GOURL.$key, $value);
		}
	
		return true;
	}
	
	
	
	
	
	/*
	 *  13.
	*/
	public function page_settings()
	{
	
		if ($this->errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';

		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL));
			
			
		if (!$this->payments)
		{
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= sprintf( __('Simple register on <a target="_blank" href="https://gourl.io/info/memberarea/My_Account.html">GoUrl.io</a> and get your Free Public/Private Payment Box keys. &#160; <a href="%s#i3">Read more &#187;</a>', GOURL), GOURL_ADMIN.GOURL);
			$tmp .= "</div>";
		}
		
		$tmp .= $message;
	
		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."settings'>";
	
		$tmp .= "<div class='postbox'>";
		$tmp .= "<h3 class='hndle'>".__('General Settings', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_settings" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($this->payments) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'#i3" class="'.GOURL.'button button-secondary">'.__('Instruction', GOURL).'</a>'.$this->space();
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'settings">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div>';
		$tmp .= __( 'If you use multiple stores/sites online, please create separate <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0">GoUrl Payment Box</a> (with unique payment box public/private keys) for each of your stores/websites.<br>Do not use the same GoUrl Payment Box with the same public/private keys on your different websites/stores.', GOURL ) . "<br><br>";
		
	
		$tmp .= "<table class='".GOURL."table ".GOURL."settings'>";
	
		$tmp .= '<tr><th>'.__('Your Callback Url', GOURL).':</th>';
		$tmp .= '<td><b>'.trim(get_site_url(), "/ ").'/?cryptobox.callback.php</b><br /><br /><em>'.__('IMPORTANT - Please place this Callback URL in field "Callback URL (optional)" for all your Payment Boxes on gourl.io', GOURL).'</em><br />';
		$tmp .= '<a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0"><img title="Payment Box Edit - GoUrl.io" src="'.plugins_url('/images/callback_field.png', __FILE__).'" border="0"></a>';
		$tmp .= '</td>';
		$tmp .= '</tr>';
		
		foreach ($this->coin_names as $k => $v)
		{
			$v2 = ucfirst($v);
	
			$tmp .= '<tr><th>'.$v2.' '.__('Payments', GOURL).':<br /><a target="_blank" href="'.$this->coin_www[$v].'"><img title="'.$v2.' Payment API" src="'.plugins_url('/images/'.$v.'.png', __FILE__).'" border="0"></a></th>';
			$tmp .= '<td>';
			$tmp .= '<div>'.$v2.' '.__('Box Public Key', GOURL).' -</div><input type="text" id="'.GOURL.$v.'public_key" name="'.GOURL.$v.'public_key" value="'.htmlspecialchars($this->options[$v.'public_key'], ENT_QUOTES).'" class="widefat">';
			$tmp .= '<div>'.$v2.' '.__('Box Private Key', GOURL).' -</div><input type="text" id="'.GOURL.$v.'private_key" name="'.GOURL.$v.'private_key" value="'.htmlspecialchars($this->options[$v.'private_key'], ENT_QUOTES).'" class="widefat">';
			$tmp .= '<em>'.sprintf(__('<b>That is not a %s wallet private key!</b> &#160; GoUrl %s Box Private/Public Keys are used for communicating between your server and GoUrl.io Payment Gateway server (similar like paypal id/keys).<br>If you want to start accepting payments in <a target="_blank" href="%s">%s (%s)</a>, please create a <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0/">'.$v2.' Payment Box</a> on GoUrl.io and then enter the received free %s  Box Public/Private Keys. Leave blank if you do not accept payments in %s', GOURL), $v2, $v2, $this->coin_www[$v], $v2, $k, $v2, $v2).'</em></td>';
			$tmp .= '</tr>';
		}
	
		$tmp .= '<tr><th colspan="2"><h3>'.__('Payment Box', GOURL).'</h3></th>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th><br />'.__('Payment Box Width', GOURL).':</th>';
		$tmp .= '<td><br /><input class="gourlnumeric" type="text" id="'.GOURL.'box_width" name="'.GOURL.'box_width" value="'.htmlspecialchars($this->options['box_width'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br /><em>'.sprintf(__('Cryptocoin Payment Box Width, default 530px. <a href="%s">See screenshot &#187;</a>', GOURL), plugins_url("/images/sizes.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Payment Box Height', GOURL).':</th>';
		$tmp .= '<td><input class="gourlnumeric" type="text" id="'.GOURL.'box_height" name="'.GOURL.'box_height" value="'.htmlspecialchars($this->options['box_height'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br /><em>'.__('Cryptocoin Payment Box Height, default 230px', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Payment Box Style', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="" '.$this->chk($this->options['box_border'], "").'> '.__('Box with Default Shadow', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="1" '.$this->chk($this->options['box_border'], 1).'> '.__('Box with light Border', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="2" '.$this->chk($this->options['box_border'], 2).'> '.__('Box without Border', GOURL);
		$tmp .= '</p>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'box_border" value="3" '.$this->chk($this->options['box_border'], 3).'> '.__('Custom Style', GOURL).' -</p>';
		$tmp .= '<textarea id="'.GOURL.'box_style" name="'.GOURL.'box_style" class="widefat" style="height: 60px;">'.htmlspecialchars($this->options['box_style'], ENT_QUOTES).'</textarea><br /><em>'.sprintf(__('Optional, Payment Box Visual CSS Style. <a href="%s">See screenshot &#187;</a><br />Example: border-radius:15px;border:1px solid #eee;padding:3px 6px;margin:10px', GOURL), plugins_url("/images/styles.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Payment Messages Style', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="" '.$this->chk($this->options['message_border'], "").'> '.__('Messages with Default Shadow', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="1" '.$this->chk($this->options['message_border'], 1).'> '.__('Messages with light Border', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="2" '.$this->chk($this->options['message_border'], 2).'> '.__('Messages without Border', GOURL);
		$tmp .= '</p>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'message_border" value="3" '.$this->chk($this->options['message_border'], 3).'> '.__('Custom Style', GOURL).' -</p>';
		$tmp .= '<textarea id="'.GOURL.'message_style" name="'.GOURL.'message_style" class="widefat" style="height: 50px;">'.htmlspecialchars($this->options['message_style'], ENT_QUOTES).'</textarea><br /><em>'.sprintf(__('Optional, Payment Notifications (when user click on payment button) Visual CSS Style. <a href="%s">See screenshot &#187;</a><br />Example: display:inline-block;max-width:580px;padding:15px 20px;box-shadow:0 0 3px #aaa;margin:7px;line-height:25px;', GOURL), plugins_url("/images/styles.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr id="images"><th colspan="2"><h3>'.__('Images for Payment Box', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('1. Pay-Per-Product', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'login_type" value="" '.$this->chk($this->options['login_type'], "").'> '.__('Display Website Login Form', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'login_type" value="1" '.$this->chk($this->options['login_type'], 1).'> '.__('Display Payment Login Image', GOURL).$this->space(4);
		$tmp .= '<br /><em>'.sprintf(__('Unregistered visitors will see that on your webpages with <a href="%s">Pay-Per-Product</a> items', GOURL), GOURL_ADMIN.GOURL."products").'</em>';
		$tmp .= '</p>';
		$tmp .= '</tr>';
		
		$i = 2;
		foreach($this->custom_images as $k => $v)
		{
			$tmp .= '<tr><th>'.$i.'. '.__($v, GOURL).':</th><td>';
			$tmp .= '<p><input type="radio" name="'.GOURL.$k.'" value="0" '.$this->chk($this->options[$k], 0).'> '.__('Default '.$v.' Image', GOURL).' -</p>';
			$tmp .= "<img src='".plugins_url('/images', __FILE__)."/".$k.".png' border='0'>";
			$tmp .= '<p><input type="radio" name="'.GOURL.$k.'" value="1" '.$this->chk($this->options[$k], 1).'> '.__('Custom Image', GOURL).' -</p>';
			if ($this->options[$k.'url'] && file_exists(GOURL_DIR."box/".$this->options[$k.'url'])) $tmp .= "<img src='".GOURL_DIR2."box/".$this->options[$k.'url']."' border='0'>"; else $this->options[$k.'url'] = "";
			$tmp .= "<input type='hidden' id='".GOURL.$k."url' name='".GOURL.$k."url' value='".htmlspecialchars($this->options[$k.'url'], ENT_QUOTES)."'>";
			if ($k == "img_plogin") 	$hint = "This image will be displayed if your site requires registration for the buyer before paying for a product/service. ";
			elseif ($k == "img_flogin") $hint = "This image will be displayed if only registered users can buy/download your paid files. ";
			else $hint = "";
			$tmp .= '<input type="file" accept="image/*" id="'.GOURL.$k.'2" name="'.GOURL.$k.'2" class="widefat"><br /><em>'.__($hint.'Allowed images: JPG, GIF, PNG.', GOURL).'</em>';
			$tmp .= '</td></tr>';
			$i++;
		}
		
		$tmp .= '<tr><th colspan="2"><h3>'.__('Other', GOURL).'</h3></th>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th><br />'.__('Records Per Page', GOURL).':</th>';
		$tmp .= '<td><br /><input class="gourlnumeric" type="text" id="'.GOURL.'rec_per_page" name="'.GOURL.'rec_per_page" value="'.htmlspecialchars($this->options['rec_per_page'], ENT_QUOTES).'" class="widefat"><label>'.__('records', GOURL).'</label><br /><em>'.__('Set number of records per page in tables "All Payments" and "All Files"', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th><br />'.__('Popup Message', GOURL).':</th>';
		$tmp .= '<td><br /><input type="text" id="'.GOURL.'popup_message" name="'.GOURL.'popup_message" value="'.htmlspecialchars($this->options['popup_message'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Pay-Per-Download: A pop-up message that a visitor will see when trying to download a paid file without payment<br/>Default text: It is a Paid Download ! Please pay below It', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Files Downloaded Info', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'file_columns" id="'.GOURL.'file_columns" value="1" '.$this->chk($this->options['file_columns'], 1).' class="widefat"><br /><em>'.__('<p>Pay-Per-Download: If box is checked, display on "All Payments" statistics page two additional columns "File Downloaded By User?" and "File Downloaded Time". Use it if you sell files online (Pay-Per-Download)', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Reverse Bitcoin Chart', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'chart_reverse" id="'.GOURL.'chart_reverse" value="1" '.$this->chk($this->options['chart_reverse'], 1).' class="widefat"><br /><em>'.__('<a href="'.GOURL_ADMIN.GOURL.'#chart">Bitcoin Chart</a>: Reverse the X axis of time', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '</table>';
	
		$tmp .= '</div></div>';
		$tmp .= '</form>';
	
		$tmp .= '</div>';

		echo $tmp;
			
		return true;
	}
		

	
	/*
	 *  14.
	*/
	private function payment_box_style()
	{
		$opt = $this->options["box_border"];
		
		if (!$opt) $tmp = "";
		elseif ($opt == 1) $tmp = "border-radius:15px;border:1px solid #eee;padding:3px 6px;margin:10px;";
		elseif ($opt == 2) $tmp = "padding:5px;margin:10px;";
		elseif ($opt == 3) $tmp = $this->options["box_style"];
	
		return $tmp;
	}
	

	
	/*
	 *  15.
	*/
	private function payment_message_style()
	{
		$opt = $this->options["message_border"];
	
		if (!$opt) $tmp = "";
		elseif ($opt == 1) $tmp = "display:inline-block;max-width:580px;padding:15px 20px;border:1px solid #eee;margin:7px;line-height:25px;";
		elseif ($opt == 2) $tmp = "display:inline-block;max-width:580px;padding:15px 20px;margin:7px;line-height:25px;";
		elseif ($opt == 3) $tmp = $this->options["message_style"];
	
		return $tmp;
	}
	
	
	
	
	
	/**************** COMMON FUNCTIONS **************************/
	
	/*
	 *  16.
	*/
	private function get_record($page)
	{
		global $wpdb;
	
		if 		($page == "file") 	 { $idx = "fileID"; $table = "crypto_files"; }
		elseif 	($page == "product") { $idx = "productID"; $table = "crypto_products"; }
		else 	return false;
		
		$this->record = array();
	
		if ($this->id)
		{
			$tmp = $wpdb->get_row("SELECT * FROM ".$table." WHERE ".$idx." = ".$this->id." LIMIT 1", ARRAY_A);
			if (!$tmp) { header('Location: '.GOURL_ADMIN.GOURL.$page); die(); }
		}
	
		// values - from db or default
		foreach ($this->record_fields as $key => $val) $this->record[$key] = ($this->id) ? $tmp[$key] : $val;
	
		return true;
	}
	
	
	
	/*
	 *  17. 
	*/
	private function post_record()
	{
		$this->record = array();
	
		foreach ($this->record_fields as $key => $val)
		{
			$this->record[$key] = (isset($_POST[GOURL.$key])) ? $_POST[GOURL.$key] : "";
			if (is_string($this->record[$key])) $this->record[$key] = trim(stripslashes($this->record[$key]));
		}
	
		return true;
	}
	
	
	
	
	
	
	/**************** B. PAY-PER-FILE ************************************/
	
	
	/*
	 *  18.
	*/
	private function check_download()
	{
		$this->record_errors = array();
	
		if ($this->record["fileID"] != $this->id) $this->record_errors[] = __('Invalid File ID, Please reload page', GOURL);
			
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
			
		$this->record["priceUSD"] = str_replace(",", "", $this->record["priceUSD"]);
		$this->record["priceCoin"] = str_replace(",", "", $this->record["priceCoin"]);
		if ($this->record["priceUSD"] == 0 && $this->record["priceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->record["priceUSD"] != 0 && $this->record["priceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->record["priceUSD"] != 0 && (!is_numeric($this->record["priceUSD"]) || round($this->record["priceUSD"], 2) != $this->record["priceUSD"] || $this->record["priceUSD"] < 0.01 || $this->record["priceUSD"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->record["priceUSD"]);
		if ($this->record["priceCoin"] != 0 && (!is_numeric($this->record["priceCoin"]) || round($this->record["priceCoin"], 4) != $this->record["priceCoin"] || $this->record["priceCoin"] < 0.0001 || $this->record["priceCoin"] > 50000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->record["priceCoin"], $this->record["priceLabel"], $this->record["priceLabel"]);
		
		if ($this->record["priceLabel"] && !isset($this->coin_names[$this->record["priceLabel"]])) $this->record_errors[] = sprintf(__('Price label "%s" - invalid value', GOURL), $this->record["priceLabel"]);
		
		if ($this->record["purchases"] && (!is_numeric($this->record["purchases"]) || round($this->record["purchases"]) != $this->record["purchases"] || $this->record["purchases"] < 0)) $this->record_errors[] = __('Purchase Limit - invalid value', GOURL);

		if (!$this->record["expiryPeriod"]) $this->record_errors[] = __('Field "Expiry Period" - cannot be empty', GOURL);
		elseif (!in_array($this->record["expiryPeriod"], $this->expiry_period))	$this->record_errors[] = __('Field "Expiry Period" - invalid value', GOURL);
		
		if (!in_array($this->record["userFormat"], $this->store_visitorid)) $this->record_errors[] = __('Store Visitor IDs - invalid value', GOURL);
			
		if (!isset($this->languages[$this->record["lang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

		if (!$this->record["defCoin"]) $this->record_errors[] = __('Field "PaymentBox Coin" - cannot be empty', GOURL);
		elseif (!isset($this->coin_names[$this->record["defCoin"]])) $this->record_errors[] = __('Field "PaymentBox Coin" - invalid value', GOURL);
		elseif (!isset($this->payments[$this->record["defCoin"]])) $this->record_errors[] = sprintf( __('Field "PaymentBox Coin" - payments in %s not available. Please re-save record', GOURL), $this->coin_names[$this->record["defCoin"]]);
		elseif ($this->record["priceCoin"] != 0 && $this->record["defCoin"] != $this->record["priceLabel"]) 
		{
			if (isset($this->payments[$this->record["priceLabel"]])) $this->record["defCoin"] = $this->record["priceLabel"];
			else $this->record_errors[] = sprintf(__('Field "PaymentBox Coin" - please select "%s" because you have entered price in %s', GOURL), $this->coin_names[$this->record["priceLabel"]], $this->coin_names[$this->record["priceLabel"]]);
		}
		
		if ($this->record["priceCoin"] != 0 && !$this->record["defShow"]) $this->record["defShow"] = 1;
		
		if (!is_numeric($this->record["imageWidth"]) || round($this->record["imageWidth"]) != $this->record["imageWidth"] || $this->record["imageWidth"] < 1 || $this->record["imageWidth"] > 2000) $this->record_errors[] = __('Invalid Image Width. Allowed 1..2,000px', GOURL);

	
		return true;
	}
	
	
	
	
	/*
	 *  19.
	*/
	private function save_download()
	{
		global $wpdb;
	
		$dt = gmdate('Y-m-d H:i:s');
	
		$fileSize = ($this->record['fileName']) ? filesize(GOURL_DIR."files/".$this->record['fileName']) : 0;
			
		if ($this->record['priceUSD'] <= 0)  $this->record['priceUSD'] = 0;
		if ($this->record['priceCoin'] <= 0 || $this->record['priceUSD'] > 0) { $this->record['priceCoin'] = 0; $this->record['priceLabel'] = ""; }
		
		if ($this->id)
		{
			$sql = "UPDATE crypto_files
					SET
						fileTitle 	= '".esc_sql($this->record['fileTitle'])."',
						active 		= '".$this->record['active']."',
						fileName 	= '".esc_sql($this->record['fileName'])."',
						fileText	= '".esc_sql($this->record['fileText'])."',
						fileSize 	= ".$fileSize.",
						priceUSD 	= ".$this->record['priceUSD'].",
						priceCoin 	= ".$this->record['priceCoin'].",
						priceLabel 	= '".$this->record['priceLabel']."',
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
			$sql = "INSERT INTO crypto_files (fileTitle, active, fileName, fileText, fileSize, priceUSD, priceCoin, priceLabel, purchases, userFormat, expiryPeriod, lang, defCoin, defShow, image, imageWidth, priceShow, paymentCnt, updatetime, createtime)
					VALUES (
							'".esc_sql($this->record['fileTitle'])."',
							1,
							'".esc_sql($this->record['fileName'])."',
							'".esc_sql($this->record['fileText'])."',
							".$fileSize.",
							".$this->record['priceUSD'].",
							".$this->record['priceCoin'].",
							'".$this->record['priceLabel']."',
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
				
		if ($wpdb->query($sql) === false) $this->record_errors[] = "Error in SQL : " . $sql;
		elseif (!$this->id) $this->id = $wpdb->insert_id;
	
		return true;
	}
	
	

	
	/*
	 *  20.
	*/
	public function page_newfile()
	{
	
		$preview = ($this->id && isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
	
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Record has been saved <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
	
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title($this->id?__('Edit File', GOURL):__('New File', GOURL), 2);
		$tmp .= $message;
	
		$short_code = '['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]';
	
		if ($preview)
		{
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";
			$tmp .= $this->shortcode_download(array("id"=>$this->id));
			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160;  '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}
	
		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."file&id=".$this->id."'>";
	
		$tmp .= "<div class='postbox'>";
		
		$tmp .= '<div class="alignright"><br />';
		if ($this->id && $this->record['paymentCnt']) $tmp .= "<a style='margin-top:-7px' href='".GOURL_ADMIN.GOURL."payments&s=file_".$this->id."' class='".GOURL."button button-secondary'>".sprintf(__('Sold %d copies', GOURL), $this->record['paymentCnt'])."</a>".$this->space();
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file">'.__('New File', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file&id='.$this->id.'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'files">'.__('All Paid Files', GOURL).'</a>';
		$tmp .= '</div>';
		
		$tmp .= "<h3 class='hndle'>".__(($this->id?'Edit file':'Upload New File, Music, Picture, Video'), GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_download" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id && !$preview) $tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview', GOURL)."</a>".$this->space(2);
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_download_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br /><br />';
	
	
		$tmp .= "<table class='".GOURL."table ".GOURL."file'>";
	
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('File ID', GOURL).':</th>';
			$tmp .= '<td><b>'.$this->record['fileID'].'</b></td>';
			$tmp .= '</tr>';
			$tmp .= '<tr><th>'.__('Shortcode', GOURL).':</th>';
			$tmp .= '<td><b>['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]</b><br /><em>'.sprintf(__('<p>Just <a target="_blank" href="%s">add this shortcode</a> to any your page or post (in html view) and cryptocoin payment box will be display', GOURL), plugins_url('/images/tagexample_download_full.png', __FILE__)).'</em></td>';
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
				if (!in_array($all_files[$i], array(".", "..", "index.htm", "index.html", "index.php", ".htaccess", "gourl_ipn.php")) && is_file(GOURL_DIR.'/files/'.$all_files[$i]))
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


	$tmp .= '<tr><th>'.__('Price', GOURL).':</th><td>';
	$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'priceUSD" id="'.GOURL.'priceUSD" value="'.htmlspecialchars($this->record['priceUSD'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
	$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
	$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'priceCoin" id="'.GOURL.'priceCoin" value="'.htmlspecialchars($this->record['priceCoin'], ENT_QUOTES).'">'.$this->space();
	$tmp .= '<select name="'.GOURL.'priceLabel" id="'.GOURL.'priceLabel">';
	foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['priceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
	$tmp .= '</select>';
	$tmp .= '<br /><em>'.__('Please specify file price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don\'t need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target="_blank" href="http://goo.gl/L8H9gG">Cryptsy "autosell" feature</a> (auto trade your cryptocoins to USD).', GOURL).'</em>';
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
			if (!stripos($v, "minute")) $tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['expiryPeriod']).'>'.$v.'</option>';

	$tmp .= '</select>';
	$tmp .= '<br /><em>'.__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown for this file (you can use it to take new payments from users periodically on daily/monthly basis).<br/>If Expiry Period > 2days, please use option - Store Visitor IDs: "Registered Users"; because "Cookie/Session" not safety for long expiry period', GOURL).'</em></td>';
	$tmp .= '</tr>';


	$tmp .= '<tr><th>'.__('Store Visitor IDs', GOURL).':</th>';
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
	$tmp .= '<td><input type="checkbox" name="'.GOURL.'defShow" id="'.GOURL.'defShow" value="1" '.$this->chk($this->record['defShow'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" for this file (no multiple coins). Please use price in USD if you want to accept multiple coins', GOURL).'</em></td>';
	$tmp .= '</tr>';


	$tmp .= '<tr><th>'.__('Description (Optional)', GOURL).':</th><td>';
	echo $tmp;
	wp_editor( $this->record['fileText'], GOURL.'fileText', array('textarea_name' => GOURL.'fileText', 'quicktags' => true, 'media_buttons' => false, 'textarea_rows' => 8, 'wpautop' => false));
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
		$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentCnt" id="'.GOURL.'paymentCnt" value="'.htmlspecialchars($this->record['paymentCnt'], ENT_QUOTES).'"><b>'.$this->record['paymentCnt'].' '.__('copies', GOURL).'</b></td>';
		$tmp .= '</tr>';

		if ($this->record['paymentCnt'])
		{
			$tmp .= '<tr><th>'.__('Latest Received Payment', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentTime" id="'.GOURL.'paymentTime" value="'.htmlspecialchars($this->record['paymentTime'], ENT_QUOTES).'"><b>'.date('d M Y, H:i:s a', strtotime($this->record['paymentTime'])).' GMT</b></td>';
			$tmp .= '</tr>';
		}

		if ($this->record['updatetime'] && $this->record['updatetime'] != $this->record['createtime'])
		{
			$tmp .= '<tr><th>'.__('Record Updated', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'updatetime" id="'.GOURL.'updatetime" value="'.htmlspecialchars($this->record['updatetime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['updatetime'])).' GMT</td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Record Created', GOURL).':</th>';
		$tmp .= '<td><input type="hidden" name="'.GOURL.'createtime" id="'.GOURL.'createtime" value="'.htmlspecialchars($this->record['createtime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['createtime'])).' GMT</td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__('Optional - add in file gourl_ipn.php code below. <a href="%s">Read more &#187;</a><br><i>case "file_%s": &#160; &#160; // order_ID = file_%s<br>// ...your_code...<br>break;</i></em></td>', GOURL), GOURL_ADMIN.GOURL."#i5", $this->id, $this->id);
		$tmp .= '</tr>';
	}
	
	
	$tmp .= '</table>';
	
	
	$tmp .= '</div></div>';
	$tmp .= '</form></div>';
	
	echo $tmp;
	
	return true;
}
	
	
	
	
	
	
	
	/*
	*  21.
	*/
	public function page_files()
	{
		global $wpdb;
	

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_files_intro", $intro);
		}
		else $intro = get_option(GOURL."page_files_intro");
		
		
		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = trim($_GET["s"]);
				
			if ($s == "sold") 			$search = " && paymentCnt > 0";
			elseif ($s == "active") 	$search = " && active != 0";
			elseif ($s == "inactive") 	$search = " && active = 0";
			elseif (strtolower($s) == "registered users") $search = " && userFormat = 'MANUAL'";
			elseif (in_array(strtolower($s), $this->coin_names)) $search = " && (priceLabel = '".array_search(strtolower($s), $this->coin_names)."' || defCoin = '".array_search(strtolower($s), $this->coin_names)."')";
			elseif (isset($this->coin_names[strtoupper($s)])) $search = " && (priceLabel = '".strtoupper($s)."' || defCoin = '".strtoupper($s)."')";
				
			if (!$search)
			{
				if (in_array(ucwords(strtolower($s)), $this->languages)) $s = array_search(ucwords(strtolower($s)), $this->languages);
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
				$s = esc_sql($s);
				$search = " && (fileTitle LIKE '%".$s."%' || fileName LIKE '%".$s."%' || fileText LIKE '%".$s."%' || priceUSD LIKE '%".$s."%' || priceCoin LIKE '%".$s."%' || priceLabel LIKE '%".$s."%' || userFormat LIKE '%".$s."%' || expiryPeriod LIKE '%".$s."%' || defCoin LIKE '%".$s."%' || image LIKE '%".$s."%' || imageWidth LIKE '%".$s."%' || paymentCnt LIKE '%".$s."%' || lang LIKE '%".$s."%' || DATE_FORMAT(createtime, '%d %M %Y') LIKE '%".$s."%')";
			}
		}
		
		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active != 0".$search, OBJECT);
		$active = (int)$res->cnt;
	
		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active = 0".$search, OBJECT);
		$inactive = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT sum(paymentCnt) as total from crypto_files WHERE paymentCnt > 0".$search, OBJECT);
		$sold = (int)$res->total;
		
	
		$wp_list_table = new  gourl_table_files($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();
		
		
		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Paid Files', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'file">' . __('Add New File', GOURL) . '</a>', 2);
		
		if (!$intro)
		{
			echo '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'files&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			echo "<div class='".GOURL."intro postbox'>";
			echo '<a style="float:right" target="_blank" href="http://gourl.io/lib/examples/pay-per-download-multi.php"><img width="110" hspace="10" title="Example - Pay Per Download" src="'.plugins_url('/images/pay-per-download.png', __FILE__).'" border="0"></a>';
			echo '<p>'.sprintf(__('Easily Sell Files, Videos, Music, Photos, Software (digital downloads) on your WordPress site/blog and accept <b>Bitcoin</b>, Litecoin, Paycoin, Dogecoin, Dash, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin, Peercoin payments online. No Chargebacks, Global, Secure. Anonymous Bitcoins & Cryptocurrency Payments. All in automatic mode. &#160; <a target="_blank" href="%s">Example</a><br>If your site requires registration - activate website registration (General Settings &#187; Membership - <a href="%s">Anyone can register</a>) and customize <a href="%s">login</a> image.', GOURL), "http://gourl.io/lib/examples/pay-per-download-multi.php", admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images") .'</p>';
			echo '<p>'.sprintf(__('Create <a href="%sfile">New Paid File Downloads</a> and place new generated <a href="%s">shortcode</a> on your public page/post. Done!', GOURL), GOURL_ADMIN.GOURL, plugins_url('/images/tagexample_download_full.png', __FILE__)).$this->space(1);
			echo sprintf(__('<a href="%s#i3">Read more</a>', GOURL), GOURL_ADMIN.GOURL).'</p>';
			echo  "</div>";
		}
	
		
		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		if ($intro) echo '<a href="'.GOURL_ADMIN.GOURL.'files&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a> &#160; &#160; ';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
	
		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . __($search?__('Found', GOURL):__('Total Files', GOURL)). ":</b> " . ($active+$inactive) . " " . __('files', GOURL) . $this->space(1) . "( ";
		echo "<b>" . __('Active', GOURL). ":</b> " . ($search?$active:"<a href='".GOURL_ADMIN.GOURL."files&s=active'>$active</a>"). " " . __('files', GOURL) . $this->space(2);
		echo "<b>" . __('Inactive', GOURL). ":</b> " . ($search?$inactive:"<a href='".GOURL_ADMIN.GOURL."files&s=inactive'>$inactive</a>") . " " . __('files', GOURL) . $this->space(1) . ")" . $this->space(4);
		echo "<b>" . __('Total Sold', GOURL). ":</b> " . ($search?$sold:"<a href='".GOURL_ADMIN.GOURL."files&s=sold'>$sold</a>") . " " . __('files', GOURL);
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."files'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";
	
		echo '<div class="'.GOURL.'widetable">';
		echo '<div style="min-width:1690px;width:100%;">';
	
		$wp_list_table->display();
	
		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
	
		return true;
	}
	
	
	
	/*
	 *  22.
	*/
	public function shortcode_download($arr)
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
		if (!$arr) return '<div>'.__('Invalid file id "'.$id.'" - ', GOURL).$short_code.'</div>';
	
	
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();
	
		$active 		= $arr["active"];
		$fileTitle 		= $arr["fileTitle"];
		$fileName 		= $arr["fileName"];
		$fileText 		= $arr["fileText"];
		$fileSize 		= $arr["fileSize"];
		
		$priceUSD 		= $arr["priceUSD"];
		$priceCoin 		= $arr["priceCoin"];
		$priceLabel 	= $arr["priceLabel"];
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $arr["defCoin"] = $priceLabel; $arr["defShow"] = 1; }
		
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
			$box_html = "<div align='center'><a href='".wp_login_url(get_permalink())."'><img title='".__('Please register or login to download this file', GOURL)."' alt='".__('Please register or login to download this file', GOURL)."' src='".$this->box_image("flogin")."' border='0'></a></div><br /><br />";
			$download_link = "onclick='alert(\"".__('Please register or login to download this file', GOURL)."\")' href='#a'";
		}
		else if (!$fileName || !file_exists($filePath) || !is_file($filePath))
		{
			$box_html = "<div align='center'><img alt='".__('File does not exist on the server', GOURL)."' src='".$this->box_image("nofile")."' border='0'></div><br /><br />";
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
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
				
				
				
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
					"amount"   	  => $priceCoin,		// file price in coin
					"amountUSD"   => $priceUSD,			// file price in USD
					"period"      => $expiryPeriod, 	// download link valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);
				
				
				
			// Initialise Payment Class
			$box = new Cryptobox ($options);
				
	
			// Coin name
			$coinName = $box->coin_name();
	
				
			// Paid or not
			$is_paid = $box->is_paid();
	
			
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
				$box_html = "<div align='center'><img alt='".__('Sold Out', GOURL)."' src='".$this->box_image("sold")."' border='0'></div><br /><br />";
					
			}
			elseif (!$is_paid && !$active)
			{
				// B. Box Not Active
				$box_html = "<div align='center'><img alt='".__('Cryptcoin Payments Disabled for this File', GOURL)."' src='".$this->box_image("fdisable")."' border='0'></div><br /><br />";
			}
			else
			{
				// Coins selection list (html code)
				$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";
	
	
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
	
		$tmp  = "<div class='gourlbox'".($languages_list?" style='min-width:".$box_width."px'":"").">";
		$tmp .= "<h1>".htmlspecialchars($fileTitle, ENT_QUOTES)."</h1>";
	
		// Display Price in USD
		if ($priceShow)
		{
			$tmp .= "<h3> &#160; ".__('File', GOURL).": &#160; <a class='gourlfilename' style='text-decoration:none;color:inherit;' ".$download_link.">".$fileName."</a>".$this->space(2)."<small style='white-space:nowrap'>".__('size', GOURL).": ".gourl_byte_format($fileSize)."</small></h3>";
			$tmp .= "<div class='gourlprice'>".__('Price', GOURL).": ".($priceUSD>0?"~".$priceUSD." ".__('USD', GOURL):gourl_number_format($priceCoin, 4)." ".$priceLabel)."</div>";
		}
	
		// Download Link
		$tmp .= "<div align='center'><a ".$download_link."><img class='gourlimg' width='".$imageWidth."' alt='".htmlspecialchars($fileTitle, ENT_QUOTES)."' src='".GOURL_DIR2."images/".$image."' border='0'></a></div>";
		if ($fileText) $tmp .= "<br /><div class='gourlfiledescription'>" . $fileText . "</div><br /><br />";
		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		$tmp .= "<div class='gourldownloadlink'><a ".$download_link.">".__('Download File', GOURL)."</a></div>";
	
		if ($is_paid) 			$tmp .= "<br /><br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br /><br />";
		else 					$tmp .= $coins_list;
	
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;
	
		// End
		$tmp .= "</div>";
	
		return $tmp;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**************** C. PAY-PER-VIEW ************************************/
	
	
	
	
	/*
	 *  23.
	*/
	private function get_view()
	{
		$this->options2 = array();
	
		foreach ($this->fields_view as $key => $value)
		{
			$this->options2[$key] = get_option(GOURL.$key);
			if (!$this->options2[$key])
			{
				if ($value) $this->options2[$key] = $value; // default
				elseif ($key == "ppvCoin" && $this->payments)
				{
					$values = array_keys($this->payments);
					$this->options2[$key] = array_shift($values);
				}
			}
	
		}
		if ($this->options2["ppvPrice"] <= 0 && $this->options2["ppvPriceCoin"] <= 0) $this->options2["ppvPrice"] = 1;
		if (!$this->options2["ppvExpiry"]) $this->options2["ppvExpiry"] = "1 MONTH";
		
		return true;
	}
	
	
	
	/*
	 *  24.
	*/
	private function post_view()
	{
		$this->options2 = array();
	
		foreach ($this->fields_view as $key => $value)
		{
			$this->options2[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options2[$key])) $this->options2[$key] = trim($this->options2[$key]);
		}
	
		return true;
	}
	
	
	
	/*
	 *  25.
	*/
	private function check_view()
	{
		$this->record_errors = array();
	
		$this->options2["ppvPrice"] = str_replace(",", "", $this->options2["ppvPrice"]);
		$this->options2["ppvPriceCoin"] = str_replace(",", "", $this->options2["ppvPriceCoin"]);
		if ($this->options2["ppvPrice"] == 0 && $this->options2["ppvPriceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->options2["ppvPrice"] != 0 && $this->options2["ppvPriceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->options2["ppvPrice"] != 0 && (!is_numeric($this->options2["ppvPrice"]) || round($this->options2["ppvPrice"], 2) != $this->options2["ppvPrice"] || $this->options2["ppvPrice"] < 0.01 || $this->options2["ppvPrice"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->options2["ppvPrice"]);
		if ($this->options2["ppvPriceCoin"] != 0 && (!is_numeric($this->options2["ppvPriceCoin"]) || round($this->options2["ppvPriceCoin"], 4) != $this->options2["ppvPriceCoin"] || $this->options2["ppvPriceCoin"] < 0.0001 || $this->options2["ppvPriceCoin"] > 50000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->options2["ppvPriceCoin"], $this->options2["ppvPriceLabel"], $this->options2["ppvPriceLabel"]);
		
		if (!in_array($this->options2["ppvExpiry"], $this->expiry_view))	$this->record_errors[] = __('Field "Expiry Period" - invalid value', GOURL);
		if ($this->lock_level_view && !in_array($this->options2["ppvLevel"], array_keys($this->lock_level_view)))	$this->record_errors[] = __('Lock Page Level - invalid value', GOURL);
		if (!isset($this->languages[$this->options2["ppvLang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);
	
		if (!$this->options2["ppvCoin"]) $this->record_errors[] = __('Field "PaymentBox Coin" - cannot be empty', GOURL);
		elseif (!isset($this->coin_names[$this->options2["ppvCoin"]])) $this->record_errors[] = __('Field "PaymentBox Coin" - invalid value', GOURL);
		elseif (!isset($this->payments[$this->options2["ppvCoin"]])) $this->record_errors[] = sprintf( __('Field "PaymentBox Coin" - payments in %s not available. Please click on "Save Settings" button', GOURL), $this->coin_names[$this->options2["ppvCoin"]]);
		elseif ($this->options2["ppvPriceCoin"] != 0 && $this->options2["ppvCoin"] != $this->options2["ppvPriceLabel"]) $this->record_errors[] = sprintf(__('Field "PaymentBox Coin" - please select "%s" because you have entered price in %s', GOURL), $this->coin_names[$this->options2["ppvPriceLabel"]], $this->coin_names[$this->options2["ppvPriceLabel"]]);
		
		if ($this->options2["ppvPriceCoin"] != 0 && !$this->options2["ppvOneCoin"]) $this->record_errors[] = sprintf(__('Field "Use Default Coin Only" - check this field because you have entered price in %s. Please use price in USD if you want to accept multiple coins', GOURL), $this->coin_names[$this->options2["ppvPriceLabel"]]);
		
		return true;
	}
	
	
	/*
	 *  26.
	*/
	private function save_view()
	{
		if ($this->options2['ppvPrice'] <= 0)  $this->options2['ppvPrice'] = 0;
		if ($this->options2['ppvPriceCoin'] <= 0 || $this->options2['ppvPrice'] > 0) { $this->options2['ppvPriceCoin'] = 0; $this->options2['ppvPriceLabel'] = ""; }
		
		foreach ($this->options2 as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}
	
		return true;
	}
	
	

	/*
	 *  27.
	*/
	public function page_view()
	{
		$example = 0;
		$preview = (isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_payperview_intro", $intro);
		}
		else $intro = get_option(GOURL."page_payperview_intro");
		
	
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Pay-Per-View Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
	
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL), 3);
	
	
		if ($preview)
		{
			$example = $_GET["example"];
			if ($example == 1 || $example == 2) $short_code = '['.GOURL_TAG_VIEW.' img="image'.$example.'.jpg"]';
			else $short_code = '['.GOURL_TAG_VIEW.' frame="https://www.youtube.com/embed/Eg58KaXjCFI" w="800" h="480"]';
				
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'><br /><br />";
			
			if ($example == 1 || $example == 2) $tmp .= $this->shortcode_view_init("image".$example.".jpg");
			else $tmp .= $this->shortcode_view_init("", "https://www.youtube.com/embed/Eg58KaXjCFI", 800, 480);
			
			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160; '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}
		elseif ($intro)
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'payperview&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a></div>';
		}
		else
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'payperview&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= "<div class='gourlimgright'>";
			$tmp .= "<div align='center'>";
			$tmp .= '<a target="_blank" href="http://gourl.io/lib/examples/pay-per-page-multi.php"><img title="Example - Pay Per View - Video/Page Access for Unregistered Visitors" src="'.plugins_url('/images/pay-per-page.png', __FILE__).'" border="0"></a>';
			$tmp .= "</div>";
			$tmp .= "</div>";
			$tmp .= __('<b>Pay-Per-View Summary</b> - <a target="_blank" href="http://gourl.io/lib/examples/pay-per-page-multi.php">Example</a>');
			$tmp .= "<br />";
			$tmp .= __('Your unregistered anonymous website visitors  will need to send you a set amount of cryptocoins for access to your website\'s specific pages & videos during a specific time. All will be in automatic mode - allowing you to receive payments, open webpage access to your visitors, when payment expired a new payment box will appear, payment notifications to your email, etc.', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= sprintf(__('Pay-Per-View supports <a href="%s">custom actions</a> (for example, show ads to free users on all website pages, <a href="%s">see code</a>)', GOURL), GOURL_ADMIN.GOURL."#i4", plugins_url('/images/dir/payperview_actions.txt', __FILE__)) . "<br>";
			$tmp .= sprintf(__('<a href="%s#i4">Read how it works</a> and differences between Pay-Per-View and Pay-Per-Membership.', GOURL), GOURL_ADMIN.GOURL).$this->space();
			$tmp .= "<br /><br />";
			$tmp .= "<b>".__('Pay-Per-View Pages -', GOURL)."</b>";
			$tmp .= "<br />";
			$tmp .= __('You can customize lock image / preview video for each page or no image/video preview at all.<br>Default image directory: <b class="gourlnowrap">'.GOURL_DIR2.'lockimg</b> or use full image path (http://...)', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= __('Shortcodes with preview image and preview video: ', GOURL);
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_VIEW.' img="image1.jpg"]</div>';
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_VIEW.' frame="..url.." w="640" h="480"]</div>';
			$tmp .= sprintf(__('Place one of that tags <a target="_blank" href="%s">anywhere</a> in the original text on your premium pages/posts or use <a href="%s">your custom code</a>', GOURL), plugins_url('/images/tagexample_payperview_full.png', __FILE__), plugins_url('/images/payperview_code.png', __FILE__));
			$tmp .= "<br /><br />";
			$tmp .= __('Ready to use shortcodes: ', GOURL);
			$tmp .= "<ol>";
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="image1.jpg"] &#160; - <small>'.__('lock page with default page lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="image2.jpg"] &#160; - <small>'.__('lock page with default video lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="my_image_etc.jpg"] &#160; - <small>'.sprintf(__('lock page with any custom lock image stored in directory %slockimg', GOURL), GOURL_DIR2).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="my_image_etc.jpg" w="400" h="200"] &#160; - <small>'.__('lock page with custom lock image and image width=400px height=200px', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="http://....."] &#160; - <small>'.__('lock page with any custom lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' frame="http://..." w="800" h="440"] &#160; - <small>'.__('lock page with any custom video preview, etc (iframe). Iframe width=800px, height=440px', GOURL).'</small></li>';
			$tmp .= "</ol>";
			$tmp .= "</div>";
		}
	
		$tmp .= $message;
	
	
	
	
		$tmp .= "<form id='".GOURL."form' name='".GOURL."form' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."payperview'>";
	
		$tmp .= "<div class='postbox'>";

		$tmp .= '<div class="alignright"><br />';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'payperview">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div>';
		
		$tmp .= "<h3 class='hndle'>".__('Paid Access to Selected Pages for Unregistered Visitors', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_view" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($example != 2 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=2&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 1', GOURL)."</a>";
		if ($example != 1 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=1&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 2', GOURL)."</a>";
		if ($example != 3 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=3&preview=true' class='".GOURL."button button-secondary'>".__('Video Preview 3', GOURL)."</a>";
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_payperview_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br /><br />';
	
	
		$tmp .= "<table class='".GOURL."table ".GOURL."payperview'>";
	
		$tmp .= '<tr><th>'.__('Pages Access Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'ppvPrice" id="'.GOURL.'ppvPrice" value="'.htmlspecialchars($this->options2['ppvPrice'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'ppvPriceCoin" id="'.GOURL.'ppvPriceCoin" value="'.htmlspecialchars($this->options2['ppvPriceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'ppvPriceLabel" id="'.GOURL.'ppvPriceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvPriceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Please specify pages access price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don\'t need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target="_blank" href="http://goo.gl/L8H9gG">Cryptsy "autosell" feature</a> (auto trade your cryptocoins to USD).', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvExpiry" id="'.GOURL.'ppvExpiry">';
	
		foreach($this->expiry_view as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->options2['ppvExpiry']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.sprintf(__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown (you can use it to take new payments from users periodically on daily basis).
								We use randomly generated strings as user identification and this is saved in user cookies. If user clears browser cookies, new payment box will be displayed. Therefore max expiry period is 2 DAYS. If you need more, please use <a href="%s">pay-per-membership</>', GOURL), GOURL_ADMIN.GOURL."paypermembership").'</em></td>';
		$tmp .= '</tr>';
	
	
		$tmp .= '<tr><th>'.__('Lock Page Level', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvLevel" id="'.GOURL.'ppvLevel">';
	
		foreach($this->lock_level_view as $k=>$v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvLevel']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.sprintf(__('Select Visitors/Users level who will see lock page/blog contents and need to make payment for unlock.
								Website Editors / Admins will have all the time full access to locked pages and see original page content.<br>
								If your site requires registration - activate website registration (General Settings &#187; Membership - <a href="%s">Anyone can register</a>) and customize <a href="%s">login</a> image 
				', GOURL), admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images").'</em></td>';
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
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br /><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
	
		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvOneCoin" id="'.GOURL.'ppvOneCoin" value="1" '.$this->chk($this->options2['ppvOneCoin'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" (no multiple coins)', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('PaymentBox Style:', GOURL).'</th>';
		$tmp .= '<td>'.sprintf(__( 'Payment Box <a target="_blank" href="%s">sizes</a> and border <a target="_blank" href="%s">shadow</a> you can change <a href="%s">here &#187;</a>', GOURL ), plugins_url("/images/sizes.png", __FILE__), plugins_url("/images/styles.png", __FILE__), GOURL_ADMIN.GOURL."settings#gourlvericoinprivate_key").'<br /><br /><br /><br /></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Text - Above Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextAbove'], GOURL.'ppvTextAbove', array('textarea_name' => GOURL.'ppvTextAbove', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Above</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
	
	
		$tmp .= '<tr><th>'.__('Text - Below Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextBelow'], GOURL.'ppvTextBelow', array('textarea_name' => GOURL.'ppvTextBelow', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Below</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
	
		$tmp .= '<tr><th>'.__('Hide Page Title ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvTitle2" id="'.GOURL.'ppvTitle2" value="1" '.$this->chk($this->options2['ppvTitle2'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see current page title', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Hide Menu Titles ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvTitle" id="'.GOURL.'ppvTitle" value="1" '.$this->chk($this->options2['ppvTitle'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see any link titles on premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Hide Comments Authors ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentAuthor" id="'.GOURL.'ppvCommentAuthor" value="1" '.$this->chk($this->options2['ppvCommentAuthor'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see authors of comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Hide Comments Body ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentBody" id="'.GOURL.'ppvCommentBody" value="1" '.$this->chk($this->options2['ppvCommentBody'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see comments body on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Disable Comments Reply ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentReply" id="'.GOURL.'ppvCommentReply" value="1" '.$this->chk($this->options2['ppvCommentReply'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users cannot reply/add comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__('Optional - add in file gourl_ipn.php code below. <a href="%s">Read more &#187;</a><br><i>case "payperview": &#160; &#160; // order_ID = payperview<br>// ...your_code...<br>break;</i></em></td>', GOURL), GOURL_ADMIN.GOURL."#i5");
		$tmp .= '</tr>';
		
	
		$tmp .= '</table>';
	
	
		$tmp .= '</div></div>';
		$tmp .= '</form></div>';
	
		echo $tmp;
	
		return true;
	}
	
	
	
	
	/*
	 *  Premium User or not
	*/
	public function is_premium_payperview_user ($full = true)
	{
		global $wpdb, $current_user;
		static $premium = "-1";
	
		if ($premium !== "-1") return $premium;

		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;

		$level = get_option(GOURL."ppvLevel");
		if (!$level || !in_array($level, array_keys($this->lock_level_view))) $level = 0;
		
		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator =  $_editor = $_author = $_contributor = false;
		if ($logged)
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
			$_author 		= in_array('author', 		$current_user->roles);
			$_contributor 	= in_array('contributor', 	$current_user->roles);
		}
		
		$free_user = false;
		if (!$logged) 															 			 $free_user = true;  	// Unregistered Visitors will see lock screen all time
		elseif ($level == 0 && !$logged) 													 $free_user = true; 	// Unregistered Visitors
		elseif ($level == 1 && !$_administrator && !$_editor && !$_author && !$_contributor) $free_user = true; 	// Unregistered Visitors + Registered Subscribers
		elseif ($level == 2 && !$_administrator && !$_editor && !$_author) 					 $free_user = true; 	// Unregistered Visitors + Registered Subscribers/Contributors
		elseif ($level == 3 && !$_administrator && !$_editor) 					 			 $free_user = true; 	// Unregistered Visitors + Registered Subscribers/Contributors/Authors

		
		if ($free_user && $full)
		{
			// Current Settings
			// --------------------------
			$this->get_view();
			
			$priceUSD 		= $this->options2["ppvPrice"];
			$priceCoin 		= $this->options2["ppvPriceCoin"];
			if ($priceUSD == 0 && $priceCoin == 0) $priceUSD = 1;
			if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
			
			$expiryPeriod	= $this->options2["ppvExpiry"];
			$lang 			= $this->options2["ppvLang"];
			$defCoin		= $this->coin_names[$this->options2["ppvCoin"]];
			$defShow		= $this->options2["ppvOneCoin"];
			
			$userFormat 	= "COOKIE";
			$userID 		= "";	// We use randomly generated strings as user identification and this is saved in user cookies
			$orderID 		= "payperview";
			$anchor 		= "gbx".$this->icrc32($orderID);
			$dt 			= gmdate('Y-m-d H:i:s');
			
			
			// GoUrl Payments
			// --------------------------
			$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
			$available_coins 		= array(); 		// List of coins that you accept for payments
			$cryptobox_private_keys = array();		// List Of your private keys
				
			foreach ($this->coin_names as $k => $v)
			{
				$public_key 	= $this->options[$v.'public_key'];
				$private_key 	= $this->options[$v.'private_key'];
			
				if ($public_key && !strpos($public_key, "PUB"))    { echo '<div>'.sprintf(__('Invalid %s Public Key %s - ', GOURL), $v, $public_key).$short_code.'</div>'; return false; }
				if ($private_key && !strpos($private_key, "PRV"))  { echo '<div>'.sprintf(__('Invalid %s Private Key - ', GOURL), $v).$short_code.'</div>'; return false; }
			
				if ($private_key) $cryptobox_private_keys[] = $private_key;
				if ($private_key && $public_key && (!$defShow || $v == $defCoin))
				{
					$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
					$available_coins[] = $v;
				}
			}
				
			if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
				
			if (!$available_coins) { echo '<div>'.__('No Available Payments - ', GOURL).$short_code.'</div>'; return false; }
				
			if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }
				
				
			/// GoUrl Payment Class
			// --------------------------
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
				
				
			// Current selected coin by user
			$coinName = cryptobox_selcoin($available_coins, $defCoin);
				
				
			// Current Coin public/private keys
			$public_key  = $all_keys[$coinName]["public_key"];
			$private_key = $all_keys[$coinName]["private_key"];
				
				
			// PAYMENT BOX CONFIG
			$options = array(
					"public_key"  => $public_key, 		// your box public key
					"private_key" => $private_key, 		// your box private key
					"orderID"     => $orderID, 			// hash as order id
					"userID"      => $userID, 			// unique identifier for each your user
					"userFormat"  => $userFormat, 		// save userID in
					"amount"   	  => $priceCoin,		// price in coins
					"amountUSD"   => $priceUSD,			// price in USD
					"period"      => $expiryPeriod, 	// download link valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);
			
			// Initialise Payment Class
			$box = new Cryptobox ($options);
			
			// Paid or not
			$premium = $box->is_paid();
			
			return $premium; 
		}
		
		if ($free_user) return false;
		else return true;
	}
	
	
	
	

	/*
	 *  28.
	*/
	public function shortcode_view($arr)
	{
		$image   = (isset($arr["img"])) 	? trim($arr["img"]) 	: "";
		$frame  = (isset($arr["frame"])) 	? trim($arr["frame"]) 	: "";
		$iwidth  = (isset($arr["w"])) 		? trim($arr["w"]) 		: "";
		$iheight = (isset($arr["h"])) 		? trim($arr["h"]) 		: "";
		return $this->shortcode_view_init($image, $frame, $iwidth, $iheight);
	}
	
	
	
	
	/*
	 *  29.
	*/
	private function shortcode_view_init($image = "", $frame = "", $iwidth = "", $iheight = "")
	{
		global $wpdb, $current_user;
		static $html = "-1";
	
	
		if ($html !== "-1") return $html;
	
		// empty by dafault
		$html = "";
	

		// another tag [gourl-membership] with hgh priority exists on page 
		if ($this->lock_type == GOURL_TAG_MEMBERSHIP) return ""; 
	
		// not available activated coins
		if (!$this->payments) return "";
		
		
		// preview admin mode
		$preview_mode	= (stripos($_SERVER["REQUEST_URI"], "wp-admin/admin.php?") && $this->page == "gourlpayperview") ? true : false;
		
		
		// if user already bought pay-per-view
		if (!$preview_mode && $this->is_premium_payperview_user( false )) return "";
		
				
		
	

		// shortcode options
		$orig = $image;
		if ($image && strpos($image, "/") === false) $image = GOURL_DIR2 . "lockimg/" . $image;
		if ($image && strpos($image, "//") === false && (!file_exists(ABSPATH.$image) || !is_file(ABSPATH.$image))) $image = "";
		if ($image && $frame) $frame = "";
		
		if ($frame && strpos($frame, "//") === false) $frame = "http://" . $frame;
	
		$short_code = '['.GOURL_TAG_VIEW.($image?' img="<b>'.$orig.'</b>':'').($frame?' frame="<b>'.$frame.'</b>':'').($iwidth?' w="<b>'.$iwidth.'</b>':'').($iheight?' h="<b>'.$iheight.'</b>':'').'"]';

		$iwidth = str_replace("px", "", $iwidth);
		if (!$iwidth || !is_numeric($iwidth) || $iwidth < 50) 	 $iwidth = "";
		$iheight = str_replace("px", "", $iheight);
		if (!$iheight || !is_numeric($iheight) || $iheight < 50) $iheight = "";
		
		if ($frame && !$iwidth)  $iwidth  = "640";
		if ($frame && !$iheight) $iheight = "480";
		
	
	
		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";
	
	
	
		// Current Settings
		// --------------------------
		$this->get_view();
	
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();
	
		$priceUSD 		= $this->options2["ppvPrice"];
		$priceCoin 		= $this->options2["ppvPriceCoin"];
		$priceLabel 	= $this->options2["ppvPriceLabel"];
		if ($priceUSD == 0 && $priceCoin == 0) $priceUSD = 1;
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $this->options2["ppvCoin"] = $priceLabel; $this->options2["ppvOneCoin"] = 1; }
		
		$expiryPeriod	= $this->options2["ppvExpiry"];
		$lang 			= $this->options2["ppvLang"];
		$defCoin		= $this->coin_names[$this->options2["ppvCoin"]];
		$defShow		= $this->options2["ppvOneCoin"];
	
		$textAbove		= $this->options2["ppvTextAbove"];
		$textBelow		= $this->options2["ppvTextBelow"];
		$hideCurTitle	= $this->options2["ppvTitle2"];
		$hideTitles		= $this->options2["ppvTitle"];
		$commentAuthor	= $this->options2["ppvCommentAuthor"];
		$commentBody	= $this->options2["ppvCommentBody"];
		$commentReply	= $this->options2["ppvCommentReply"];
	
	
		$userFormat 	= "COOKIE";
		$userID 		= "";	// We use randomly generated strings as user identification and this is saved in user cookies
		$orderID 		= "payperview";
		$anchor 		= "gbx".$this->icrc32($orderID);
		$dt 			= gmdate('Y-m-d H:i:s');
		
	
	
	
	
	
	
		// GoUrl Payments
		// --------------------------
			
		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys
			
		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];
	
			if ($public_key && !strpos($public_key, "PUB"))    { $html = '<div>'.sprintf(__('Invalid %s Public Key %s - ', GOURL), $v, $public_key).$short_code.'</div>'; return $html; } 
			if ($private_key && !strpos($private_key, "PRV"))  { $html = '<div>'.sprintf(__('Invalid %s Private Key - ', GOURL), $v).$short_code.'</div>'; return $html; }
	
			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$defShow || $v == $defCoin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}
			
		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
			
		if (!$available_coins) { $html = '<div>'.__('No Available Payments - ', GOURL).$short_code.'</div>'; return $html; } 
			
		if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }
			
			
			
		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
			
			
			
		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $defCoin);
			
			
		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];
			
			
		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 		// your box public key
				"private_key" => $private_key, 		// your box private key
				"orderID"     => $orderID, 			// hash as order id
				"userID"      => $userID, 			// unique identifier for each your user
				"userFormat"  => $userFormat, 		// save userID in
				"amount"   	  => $priceCoin,		// price in coins
				"amountUSD"   => $priceUSD,			// price in USD
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
		if ($is_paid && !$preview_mode) return "";
	
	
	
		// Payment Box HTML
		// ----------------------
	
		// Coins selection list (html code)
		$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 15px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";
	
	
		// Language selection list for payment box (html code)
		$languages_list = display_language_box($lang, $anchor);
	
	
		// C. Active Box
		$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
			
			
	
	
	
	
		// Html code
		// ---------------------
	
		$tmp  = "<br />";
		if (!$is_paid && $textAbove) $tmp .= "<div class='gourlviewtext'>".$textAbove."</div>".($image || $frame ? "<br /><br />" : ""); else $tmp .= "<br />";
		
		// Start
		$tmp .= "<div align='center'>";
		
		if (!$is_paid)
		{
			if ($image) 	$tmp .= "<a href='#".$anchor."'><img style='border:none;box-shadow:none;max-width:100%;".($iwidth?"width:".$iwidth."px;":"").($iheight?"height:".$iheight."px;":"")."' title='".__('Page Content Locked! Please pay below ', GOURL)."' alt='".__('Page Content Locked! Please pay below ', GOURL)."' border='0' src='".$image."'></a><br />";
			elseif ($frame) $tmp .= "<iframe style='max-width:100%' width='".$iwidth."' height='".$iheight."' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' allowfullscreen src='".htmlspecialchars($frame)."'></iframe><br />";
			
			$tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		}	
	
		if ($is_paid) 			$tmp .= "<br /><br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br /><br />";
		else 					$tmp .= "<br />".$coins_list;
	
		$tmp .= "<div class='gourlbox' style='min-width:".$box_width."px;'>";
	
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;
	
		$tmp .= "</div>";
		
		// End
		$tmp .= "</div>";
		
	
		if (!$is_paid && $textBelow) $tmp .= "<br /><br /><br /><div class='gourlviewtext'>".$textBelow."</div>";
	
	
	
		// Lock Page
		if (!$is_paid && !$preview_mode)
		{
			$tmp = GOURL_LOCK_START.$tmp.GOURL_LOCK_END;
				
			add_filter('the_content', 		'gourl_lock_filter', 11111);
			add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
			add_filter('the_content_feed', 	'gourl_lock_filter', 11111);


			if ($hideTitles && $hideCurTitle)
			{
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
			
				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
			}
			elseif ($hideTitles)
			{
				add_filter('the_title', 	'gourl_hide_menu_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_menu_titles', 11111);
			}
			elseif ($hideCurTitle)
			{
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				
				add_filter('the_title', 	'gourl_hide_page_title', 11111);
				add_filter('the_title_rss', 'gourl_hide_page_title', 11111);
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
	
	
		$html = $tmp;
	
		return $tmp;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**************** D. PAY-PER-MEMBERSHIP ************************************/
	
	
	/*
	 *  30.
	*/
	public function get_membership()
	{
		$this->options3 = array();
	
		foreach ($this->fields_membership as $key => $value)
		{
			$this->options3[$key] = get_option(GOURL.$key);
			if (!$this->options3[$key])
			{
				if ($value) $this->options3[$key] = $value; // default
				elseif ($key == "ppmCoin" && $this->payments)
				{
					$values = array_keys($this->payments);
					$this->options3[$key] = array_shift($values);
				}
			}
	
		
		}
		if ($this->options3["ppmPrice"] <= 0 && $this->options3["ppmPriceCoin"] <= 0) $this->options3["ppmPrice"] = 10;
		if (!$this->options3["ppmExpiry"]) $this->options3["ppmExpiry"] = "1 MONTH";
		
	
		return $this->options3;
	}
	
	
	
	/*
	 *  31.
	*/
	private function post_membership()
	{
		$this->options3 = array();
	
		foreach ($this->fields_membership as $key => $value)
		{
			$this->options3[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options3[$key])) $this->options3[$key] = trim($this->options3[$key]);
		}
	
		return true;
	}
	
	
	
	/*
	 *  32.
	*/
	private function check_membership()
	{
		$this->record_errors = array();
	
		$this->options3["ppmPrice"] = str_replace(",", "", $this->options3["ppmPrice"]);
		$this->options3["ppmPriceCoin"] = str_replace(",", "", $this->options3["ppmPriceCoin"]);
		if ($this->options3["ppmPrice"] == 0 && $this->options3["ppmPriceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->options3["ppmPrice"] != 0 && $this->options3["ppmPriceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->options3["ppmPrice"] != 0 && (!is_numeric($this->options3["ppmPrice"]) || round($this->options3["ppmPrice"], 2) != $this->options3["ppmPrice"] || $this->options3["ppmPrice"] < 0.01 || $this->options3["ppmPrice"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->options3["ppmPrice"]);
		if ($this->options3["ppmPriceCoin"] != 0 && (!is_numeric($this->options3["ppmPriceCoin"]) || round($this->options3["ppmPriceCoin"], 4) != $this->options3["ppmPriceCoin"] || $this->options3["ppmPriceCoin"] < 0.0001 || $this->options3["ppmPriceCoin"] > 50000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->options3["ppmPriceCoin"], $this->options3["ppmPriceLabel"], $this->options3["ppmPriceLabel"]);
		
		if (!in_array($this->options3["ppmExpiry"], $this->expiry_period))	$this->record_errors[] = __('Membership Period - invalid value', GOURL);
		if ($this->lock_level_membership && !in_array($this->options3["ppmLevel"], array_keys($this->lock_level_membership)))	$this->record_errors[] = __('Lock Page Level - invalid value', GOURL);
		if (!isset($this->languages[$this->options3["ppmLang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);
	
		if (!$this->options3["ppmCoin"]) $this->record_errors[] = __('Field "PaymentBox Coin" - cannot be empty', GOURL);
		elseif (!isset($this->coin_names[$this->options3["ppmCoin"]])) $this->record_errors[] = __('Field "PaymentBox Coin" - invalid value', GOURL);
		elseif (!isset($this->payments[$this->options3["ppmCoin"]])) $this->record_errors[] = sprintf( __('Field "PaymentBox Coin" - payments in %s not available. Please click on "Save Settings" button', GOURL), $this->coin_names[$this->options3["ppmCoin"]]);
		elseif ($this->options3["ppmPriceCoin"] != 0 && $this->options3["ppmCoin"] != $this->options3["ppmPriceLabel"]) $this->record_errors[] = sprintf(__('Field "PaymentBox Coin" - please select "%s" because you have entered price in %s', GOURL), $this->coin_names[$this->options3["ppmPriceLabel"]], $this->coin_names[$this->options3["ppmPriceLabel"]]);
		
		if ($this->options3["ppmPriceCoin"] != 0 && !$this->options3["ppmOneCoin"]) $this->record_errors[] = sprintf(__('Field "Use Default Coin Only" - check this field because you have entered price in %s. Please use price in USD if you want to accept multiple coins', GOURL), $this->coin_names[$this->options3["ppmPriceLabel"]]);
		
	
		return true;
	}
	
	
	/*
	 *  33.
	*/
	private function save_membership()
	{
		if ($this->options3['ppmPrice'] <= 0)  $this->options3['ppmPrice'] = 0;
		if ($this->options3['ppmPriceCoin'] <= 0 || $this->options3['ppmPrice'] > 0) { $this->options3['ppmPriceCoin'] = 0; $this->options3['ppmPriceLabel'] = ""; }
		
		foreach ($this->options3 as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}
	
		return true;
	}
	
	
	



	/*
	 *  34.
	*/
	public function page_membership()
	{
		global $current_user;
		
		$example = 0;
		$preview = (isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
		
		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_membership_intro", $intro);
		}
		else $intro = get_option(GOURL."page_membership_intro");
		
	
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Pay-Per-Membership Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
	
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL), 4);
	
	
		if ($preview)
		{
			if ($_GET["example"] == "4")
			{
				$tmp .= "<div class='postbox'>";
				$tmp .= "<h3 class='hndle'>".__('Unregistered visitors / non-logged users will see on your premium pages - login form with custom text', GOURL);
				$tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
				$tmp .= "</h3>";
				$tmp .= "<br><br><div class='inside' align='center'>";
				$tmp .= $this->options3['ppmTextAbove2'];
				$tmp .= "<br><br><br><img src='".plugins_url('/images/loginform.png', __FILE__)."' border='0'><br><br><br>";
				$tmp .= $this->options3['ppmTextBelow2'];
				$tmp .= "<br><br></div>";
				$tmp .= "</div>";
			}
			else
			{
				$example = $_GET["example"];
				if ($example == 1 || $example == 2) $short_code = '['.GOURL_TAG_MEMBERSHIP.' img="image'.$example.($example==2?'.jpg':'.png').'"]';
				else $short_code = '['.GOURL_TAG_MEMBERSHIP.' frame="https://www.youtube.com/embed/_YEyzvtMx3s" w="700" h="380"]';

				$tmp .= "<div class='postbox'>";
				$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
				$tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
				$tmp .= "</h3>";
				$tmp .= "<div class='inside'><br /><br />";
				
				if ($example == 1 || $example == 2) $tmp .= $this->shortcode_membership_init("image".$example.($example==2?'.jpg':'.png'));
				else $tmp .= $this->shortcode_membership_init("", "https://www.youtube.com/embed/_YEyzvtMx3s", 700, 380);
				
				$tmp .= "</div>";
				$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160; '.$short_code.'</small></div>';
				$tmp .= "</div>";
			}	
		}
		elseif ($intro)
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'paypermembership&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a></div>';
		}
		else
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'paypermembership&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= "<div class='gourlimgright'>";
			$tmp .= "<div align='center'>";
			$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership&gourlcryptocoin='.($this->options3['ppmCoin']?$this->coin_names[$this->options3['ppmCoin']]:"").'&gourlcryptolang='.$this->options3['ppmLang'].'&example=1&preview=true"><img title="Example - Bitcoin - Pay Per Membership" src="'.plugins_url('/images/pay-per-membership.png', __FILE__).'" border="0"></a>';
			$tmp .= "</div>";
			$tmp .= "</div>";
			$tmp .= sprintf(__('<b>Pay-Per-Membership</b> - Your <b>registered</b> website users will need to send you a set amount of cryptocoins for access to your website\'s specific pages & videos during a specific time. All will be in automatic mode. Pay-Per-Membership - is a better safety solution than pay-per-view because plugin uses registered userID not cookies. You need to have website registration <a href="%s">enabled</a>.', GOURL), admin_url('options-general.php'));
			$tmp .= "<br /><br />";
			$tmp .= sprintf(__('<b>Pay-Per-Membership</b> supports <a href="%s">custom actions</a> (for example, show ads to free users on all website pages, <a href="%s">see code</a>)<br>and it integrated with <a href="%s">bbPress Forum/Customer Support</a> ( use our <a href="%s">GoUrl bbPress Addon</a> ). You can mark some topics on your bbPress as Premium and can easily monetise it with Bitcoins/altcoins. &#160; <a href="%s">More info</a>', GOURL), GOURL_ADMIN.GOURL."#i4", plugins_url('/images/dir/membership_actions.txt', __FILE__), admin_url('plugin-install.php?tab=search&type=term&s=bbPress+forum+keeping+lean'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics'), GOURL_ADMIN.GOURL."#i4");
			$tmp .= "<br /><br />";
			$tmp .= sprintf(__('Pay-Per-Membership supports ONE paid membership level for website.<br>For few membership levels (ex. basic, pro, premium), alternatively you can use <a class="gourlnowrap" href="%s">Paid Memberships Pro</a> with our <a class="gourlnowrap" href="%s">GoUrl Gateweay PMP Addon</a>.', GOURL), admin_url('plugin-install.php?tab=search&type=term&s=paid+memberships+pro+easiest+level'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+paid+memberships+addon'));
			$tmp .= "<br /><br />";
			$tmp .= "<b>".__('Premium Pages -', GOURL)."</b>";
			$tmp .= "<br />";
			$tmp .= __('You can customize lock image / preview video for each premium page or no image/video preview at all.<br>Default image directory: <b class="gourlnowrap">'.GOURL_DIR2.'lockimg</b> or use full image path (http://...)', GOURL);
			$tmp .= "<br /><br />";
			$tmp .= __('Shortcodes with preview image and preview video for premium pages: ', GOURL);
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_MEMBERSHIP.' img="image1.png"]</div>';
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_MEMBERSHIP.' frame="..url.." w="700" h="380"]</div>';
			$tmp .= sprintf(__('Place one of that tags <a target="_blank" href="%s">anywhere</a> in the original text on your premium pages/posts or use <a href="%s">your custom code</a>', GOURL), plugins_url('/images/tagexample_membership_full.png', __FILE__), plugins_url('/images/paypermembership_code.png', __FILE__));
			$tmp .= "<br /><br />";
			$tmp .= __('Ready to use shortcodes: ', GOURL);
			$tmp .= "<ol>";
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="image1.png"] &#160; - <small>'.__('lock page with default page lock image; visible for unpaid logged-in users', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="image2.jpg"] &#160; - <small>'.__('lock page with default video lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="my_image_etc.jpg"] &#160; - <small>'.sprintf(__('lock page with any custom lock image stored in directory %slockimg', GOURL), GOURL_DIR2).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="my_image_etc.jpg" w="400" h="200"] &#160; - <small>'.__('lock page with custom lock image and image width=400px height=200px', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="http://....."] &#160; - <small>'.__('lock page with any custom lock image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' frame="http://..." w="750" h="410"] &#160; - <small>'.__('lock page with any custom video preview, etc (iframe). Iframe width=750px, height=410px', GOURL).'</small></li>';
				
			$tmp .= "</ol>";
			$tmp .= "</div>";
		}
	
		$tmp .= $message;
	
	
	
	
		$tmp .= "<form id='".GOURL."form' name='".GOURL."form' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."paypermembership'>";
	
		$tmp .= "<div class='postbox'>";
		
		$tmp .= '<div class="alignright"><br />';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_users">'.__('All Premium Users', GOURL).'</a>';
		$tmp .= '</div>';
		
		$tmp .= "<h3 class='hndle'>".__('Paid Access to Selected Pages for Registered Users', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_membership" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($example != 4 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&example=4&preview=true' class='".GOURL."button button-secondary'>".__('Screen for non-logged users', GOURL)."</a>";
		if ($example != 1 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=1&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 1', GOURL)."</a>";
		if ($example != 2 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=2&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 2', GOURL)."</a>";
		if ($example != 3 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=3&preview=true' class='".GOURL."button button-secondary'>".__('Video Preview 3', GOURL)."</a>";
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_membership_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br /><br />';
	
	
		$tmp .= "<table class='".GOURL."table ".GOURL."paypermembership'>";
	
		$tmp .= '<tr><th>'.__('Membership Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'ppmPrice" id="'.GOURL.'ppmPrice" value="'.htmlspecialchars($this->options3['ppmPrice'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'ppmPriceCoin" id="'.GOURL.'ppmPriceCoin" value="'.htmlspecialchars($this->options3['ppmPriceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'ppmPriceLabel" id="'.GOURL.'ppmPriceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmPriceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Please specify membership price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don\'t need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target="_blank" href="http://goo.gl/L8H9gG">Cryptsy "autosell" feature</a> (auto trade your cryptocoins to USD).', GOURL).'</em>';
		$tmp .= '</td></tr>';
			
		$tmp .= '<tr><th>'.__('Membership Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmExpiry" id="'.GOURL.'ppmExpiry">';
	
		foreach($this->expiry_period as $v)
			if (!stripos($v, "minute")) $tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->options3['ppmExpiry']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown.', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
	
		$tmp .= '<tr><th>'.__('Lock Page Level', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmLevel" id="'.GOURL.'ppmLevel">';
	
		foreach($this->lock_level_membership as $k=>$v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmLevel']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.sprintf(__('Select Users level who will see lock page/blog contents and need to make payment for unlock.
								 	Website Editors / Admins will have all the time full access to locked pages and see original page content.<br> 
									Please activate website registration ( General Settings &#187; Membership - <a href="%s">Anyone can register</a> )	
				', GOURL), admin_url('options-general.php')).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Add to User Profile', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmProfile" id="'.GOURL.'ppmProfile" value="1" '.$this->chk($this->options3['ppmProfile'], 1).' class="widefat"><br /><em>'.sprintf(__('<p>If box is checked, users will see own membership status on user profile page (<a href="%s">profile.php</a>)', GOURL), admin_url('profile.php')).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmLang" id="'.GOURL.'ppmLang">';
	
		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmLang']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
	
		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmCoin" id="'.GOURL.'ppmCoin">';
	
		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmCoin']).'>'.$v.'</option>';
	
		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br /><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmOneCoin" id="'.GOURL.'ppmOneCoin" value="1" '.$this->chk($this->options3['ppmOneCoin'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" (no multiple coins)', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('PaymentBox Style:', GOURL).'</th>';
		$tmp .= '<td>'.sprintf(__( 'Payment Box <a target="_blank" href="%s">sizes</a> and border <a target="_blank" href="%s">shadow</a> you can change <a href="%s">here &#187;</a>', GOURL ), plugins_url("/images/sizes.png", __FILE__), plugins_url("/images/styles.png", __FILE__), GOURL_ADMIN.GOURL."settings#gourlvericoinprivate_key").'<br /><br /><br /></td>';
		$tmp .= '</tr>';
		
	
		$tmp .= '<tr><th colspan="2"><br/>';
		$tmp .= '<h3>'.__('A. Unregistered Users will see Login Form with custom text/images -', GOURL).'</h3>';
		$tmp .= '<p>'.__('You can separate the content your logged-in users see from what your unregistered users see; things like a log-in form + custom text A for unregistered users &#160;or&#160; payment box + other custom text B for unpaid logged-in users.', GOURL).'</p>';
		$tmp .= '<p>'.__('IMPORTANT: Please check that Website Registration is enabled (option "Membership	- Anyone can register") under Wordpress Settings -> General in the admin panel', GOURL).'</p>';
		$tmp .= '</th>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Text - Above Login Form', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextAbove2'], GOURL.'ppmTextAbove2', array('textarea_name' => GOURL.'ppmTextAbove2', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Unregistered Users (original pages content will be hidden). This text will publish <b>Above</b> Login Form', GOURL).'</em>';
		$tmp .= '</td></tr>';
	
	
		$tmp .= '<tr><th>'.__('Text - Below Login Form', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextBelow2'], GOURL.'ppmTextBelow2', array('textarea_name' => GOURL.'ppmTextBelow2', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Unregistered Users (original pages content will be hidden). This text will publish <b>Below</b> Login Form', GOURL).'</em>';
		$tmp .= '</td></tr>';
	
		$tmp .= '<tr><th colspan="2"><br/>';
		$tmp .= '<h3>'.__('B. Unpaid logged-in users will see payment box with custom text -', GOURL).'</h3>';
		$tmp .= '</th>';
		$tmp .= '</tr>';
				
		$tmp .= '<tr><th>'.__('Text - Above Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextAbove'], GOURL.'ppmTextAbove', array('textarea_name' => GOURL.'ppmTextAbove', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Above</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		
		$tmp .= '<tr><th>'.__('Text - Below Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextBelow'], GOURL.'ppmTextBelow', array('textarea_name' => GOURL.'ppmTextBelow', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Your Custom Text and Image For Payment Request Lock Pages (original pages content will be hidden). This text will publish <b>Below</b> Payment Box', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th colspan="2"><br/><h3>'.__('General Content Restriction', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Page Title ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmTitle2" id="'.GOURL.'ppmTitle2" value="1" '.$this->chk($this->options3['ppmTitle2'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see current page title', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Hide Menu Titles ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmTitle" id="'.GOURL.'ppmTitle" value="1" '.$this->chk($this->options3['ppmTitle'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see any link titles on premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Hide Comments Authors ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentAuthor" id="'.GOURL.'ppmCommentAuthor" value="1" '.$this->chk($this->options3['ppmCommentAuthor'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see authors of comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Hide Comments Body ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentBody" id="'.GOURL.'ppmCommentBody" value="1" '.$this->chk($this->options3['ppmCommentBody'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users will not see comments body on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Disable Comments Reply ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentReply" id="'.GOURL.'ppmCommentReply" value="1" '.$this->chk($this->options3['ppmCommentReply'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, unpaid users cannot reply/add comments on bottom of premium pages', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__('Optional - add in file gourl_ipn.php code below. <a href="%s">Read more &#187;</a><br><i>case "membership": &#160; &#160; // order_ID = membership<br>// ...your_code...<br>break;</i></em></td>', GOURL), GOURL_ADMIN.GOURL."#i5");
		$tmp .= '</tr>';
		
	
		$tmp .= '</table>';
	
	
		$tmp .= '</div></div>';
		$tmp .= '</form></div>';
	
		echo $tmp;
	
		return true;
	}
	
	
	
	/*
	 *  Display or not membership upgrade payment box 
	*/
	public function is_premium_user ()
	{
		global $wpdb, $current_user;
		static $premium = "-1";
	
		if ($premium !== "-1") return $premium;
			
		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;
		
		$level = get_option(GOURL."ppmLevel");
		if (!$level || !in_array($level, array_keys($this->lock_level_membership))) $level = 0;
	
		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator =  $_editor = $_author = $_contributor = false;
		if ($logged)
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
			$_author 		= in_array('author', 		$current_user->roles);
			$_contributor 	= in_array('contributor', 	$current_user->roles);
		}
		
		$free_user = false;
		if 		(!$logged) 															 		 $free_user = true;  // Unregistered Visitors will see lock screen/login all time
		elseif  ($level == 0 && !$_administrator && !$_editor && !$_author && !$_contributor)$free_user = true; 	// Registered Subscribers will see lock screen
		elseif 	($level == 1 && !$_administrator && !$_editor && !$_author) 				 $free_user = true; 	// Registered Subscribers/Contributors will see lock screen
		elseif 	($level == 2 && !$_administrator && !$_editor) 					 			 $free_user = true; 	// Registered Subscribers/Contributors/Authors will see lock screen
	
		// if premium user already
		$dt = gmdate('Y-m-d H:i:s');
		if ($free_user && $logged && $wpdb->get_row("SELECT membID FROM crypto_membership WHERE userID = ".$current_user->ID." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0 LIMIT 1", OBJECT)) $free_user = false;
	
		
		$premium = ($free_user) ? false : true;
		
		return $premium;
	}
	

	
	/*
	 *  35.
	*/
	public function shortcode_membership($arr, $checkout = false)
	{
		$image   = (isset($arr["img"])) 	? trim($arr["img"]) 	: "";
		$frame  = (isset($arr["frame"]))	? trim($arr["frame"]) 	: "";
		$iwidth  = (isset($arr["w"])) 		? trim($arr["w"]) 		: "";
		$iheight = (isset($arr["h"])) 		? trim($arr["h"]) 		: "";
		return $this->shortcode_membership_init($image, $frame, $iwidth, $iheight, $checkout);
	}
	
	
	
	/*
	 * 
	*/
	public function shortcode_memcheckout($arr)
	{
		return $this->shortcode_membership($arr, true);
	}
	
	

	/*
	 *  36.
	*/
	private function shortcode_membership_init($image = "", $frame = "", $iwidth = "", $iheight = "", $checkout = false)
	{
		global $wpdb, $current_user;
		static $html = "-1";
	
		
		if ($html !== "-1") return $html;
		
		// empty by dafault
		$html = "";
		

		// not available activated coins
		if (!$this->payments) return "";
	
		
		// preview admin mode
		$preview_mode	= (stripos($_SERVER["REQUEST_URI"], "wp-admin/admin.php?") && $this->page == "gourlpaypermembership") ? true : false;
		

		// if premium user already or don't need upgade user membership 
		if (!$preview_mode && !$checkout && $this->is_premium_user()) return "";
		
		
		// user logged or not
		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;
		
		
		
		
		
		// shortcode options
		$orig = $image;
		if ($image && strpos($image, "/") === false) $image = GOURL_DIR2 . "lockimg/" . $image;
		if ($image && strpos($image, "//") === false && (!file_exists(ABSPATH.$image) || !is_file(ABSPATH.$image))) $image = "";
		if ($image && $frame) $frame = "";
		
		if ($frame && strpos($frame, "//") === false) $frame = "http://" . $frame;
		
		$short_code 	= '['.GOURL_TAG_MEMBERSHIP.($image?' img="<b>'.$orig.'</b>':'').($frame?' frame="<b>'.$frame.'</b>':'').($iwidth?' w="<b>'.$iwidth.'</b>':'').($iheight?' h="<b>'.$iheight.'</b>':'').'"]';
		
		$iwidth = str_replace("px", "", $iwidth);
		if (!$iwidth || !is_numeric($iwidth) || $iwidth < 50) 	 $iwidth = "";
		$iheight = str_replace("px", "", $iheight);
		if (!$iheight || !is_numeric($iheight) || $iheight < 50) $iheight = "";
		
		if ($frame && !$iwidth)  $iwidth  = "640";
		if ($frame && !$iheight) $iheight = "480";
		
		
	
	
		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";
		$box_html = "";
	
		
	
	
		// Current Settings
		// --------------------------
		$this->get_membership();
	
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();
	
		$priceUSD 		= $this->options3["ppmPrice"];
		$priceCoin 		= $this->options3["ppmPriceCoin"];
		$priceLabel 	= $this->options3["ppmPriceLabel"];
		if ($priceUSD == 0 && $priceCoin == 0) 	$priceUSD = 10;
		if ($priceUSD > 0 && $priceCoin > 0) 	$priceCoin = 0;
		if ($priceCoin > 0) { $this->options3["ppmCoin"] = $priceLabel; $this->options3["ppmOneCoin"] = 1; }
		
		$expiryPeriod	= $this->options3["ppmExpiry"];
		$lang 			= $this->options3["ppmLang"];
		$defCoin		= $this->coin_names[$this->options3["ppmCoin"]];
		$defShow		= $this->options3["ppmOneCoin"];
	
		$textAbove		= ($logged) ? $this->options3["ppmTextAbove"] : $this->options3["ppmTextAbove2"];
		$textBelow		= ($logged) ? $this->options3["ppmTextBelow"] : $this->options3["ppmTextBelow2"];
		$hideCurTitle	= $this->options3["ppmTitle2"];
		$hideTitles		= $this->options3["ppmTitle"];
		$commentAuthor	= $this->options3["ppmCommentAuthor"];
		$commentBody	= $this->options3["ppmCommentBody"];
		$commentReply	= $this->options3["ppmCommentReply"];
	
	
		$userFormat 	= "MANUAL";
		$userID 		= "user_".$current_user->ID;
		$orderID 		= "membership";
		$anchor 		= "gbx".$this->icrc32($orderID);
		$dt 			= gmdate('Y-m-d H:i:s');
	
	
	
	
	
	
	if (!$logged)
	{
		// Html code
		$tmp  = "<div align='center'>";
		
		if ($textAbove) $tmp .= "<div class='gourlmembershiptext2'>".$textAbove."</div>";
		
		$tmp .= $this->login_form();
		
		if ($textBelow) $tmp .= "<div class='gourlmembershiptext2'>".$textBelow."</div>";
		
		$tmp .= "</div>";
	}
	else	
	{	
		// if admin disabled valid user membership, display new payment form with new unique orderID for that user
		$prev_payments = $wpdb->get_row("SELECT count(membID) as cnt FROM crypto_membership WHERE userID = ".$current_user->ID." && disabled = 1 && startDate <= '$dt' && endDate >= '$dt' && paymentID > 0", OBJECT);
		if ($prev_payments && $prev_payments->cnt > 0)
		{
			$orderID 		= "membership".($prev_payments->cnt+1);
			$anchor 		= "gbx".$this->icrc32($orderID);
		}
		
		
		// GoUrl Payments
		// --------------------------
			
		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys
			
		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];
	
			if ($public_key && !strpos($public_key, "PUB"))    { $html = '<div>'.sprintf(__('Invalid %s Public Key %s - ', GOURL), $v, $public_key).$short_code.'</div>'; return $html; }
			if ($private_key && !strpos($private_key, "PRV"))  { $html = '<div>'.sprintf(__('Invalid %s Private Key - ', GOURL), $v).$short_code.'</div>'; return $html; }
	
			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$defShow || $v == $defCoin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}
			
		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
			
		if (!$available_coins) { $html = '<div>'.__('No Available Payments - ', GOURL).$short_code.'</div>'; return $html; } 
			
		if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }
			
			
			
		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
			
			
			
		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $defCoin);
			
			
		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];
			
			
		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 		// your box public key
				"private_key" => $private_key, 		// your box private key
				"orderID"     => $orderID, 			// hash as order id
				"userID"      => $userID, 			// unique identifier for each your user
				"userFormat"  => $userFormat, 		// save userID in
				"amount"   	  => $priceCoin,		// price in coins
				"amountUSD"   => $priceUSD,			// price in USD
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
		if ($is_paid && !$preview_mode && !$checkout) return "";
	
	
	
		// Payment Box HTML
		// ----------------------
	
		// Coins selection list (html code)
		$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 15px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";
	
	
		// Language selection list for payment box (html code)
		$languages_list = display_language_box($lang, $anchor);
	
	
		// C. Active Box
		$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
			

	
		// Html code
		// ---------------------
	
		$checkout_done = ($checkout && !current_user_can('manage_options') && $this->is_premium_user()) ? true : false;
		
		$tmp  = "";
		if (!$checkout_done)
		{
			$tmp  .= "<br />";
			if (!$is_paid && $textAbove) $tmp .= "<div class='gourlmembershiptext'>".$textAbove."</div>" . ($image || $frame ? "<br /><br />" : ""); else $tmp .= "<br />";
		}
	

		// Start
		$tmp .= "<div align='center'>";
		
		if ($checkout_done)
		{ 
			$tmp .= "<p><b>".__("Thank you.")."</b></p><p>".__("Your Premium membership is active.")."</p>";
		}
		elseif (!$is_paid)
		{
			if ($image) 
			{
				$imageWidthMax = "100%;";
				if ($this->right($image, "/", false) == "image1.png")
				{ 
					$tmp .= "<div align='center' style='width:555px;'><div class='".($priceUSD>0 || $expiryPeriod=="NO EXPIRY"?"gourlmembershipprice":"gourlmembershipprice2")."'>".($priceUSD>0?"$".$priceUSD:gourl_number_format($priceCoin, 4)." ".$priceLabel).($expiryPeriod!="NO EXPIRY"?($priceUSD>0?" <span>/":"<br><span>").$expiryPeriod."</span>":"")."</div></div>";
					if (is_user_logged_in() && $current_user->ID) $image = str_replace("image1.png", "image1b.png", $image);
					$imageWidthMax = "none;";
					$iwidth = 555;
				}
				$tmp .= "<a href='#".$anchor."'><img style='border:none;box-shadow:none;max-width:".$imageWidthMax.($iwidth?"width:".$iwidth."px;":"").($iheight?"height:".$iheight."px;":"")."' title='".__('Page Content Locked! Please pay below ', GOURL)."' alt='".__('Page Content Locked! Please pay below ', GOURL)."' border='0' src='".$image."'></a><br />";
			}
			elseif ($frame) $tmp .= "<iframe style='max-width:100%' width='".$iwidth."' height='".$iheight."' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' allowfullscreen src='".htmlspecialchars($frame)."'></iframe><br />";
			
			$tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		}
		elseif ($is_paid && $preview_mode) 	$tmp .= sprintf(__("<b>ADMIN NOTE:</b> Your test payment received successfully.<br>Please <a href='%s'>disable your test membership</a> and you will see payment box again", GOURL), GOURL_ADMIN.GOURL."paypermembership_users&s=user".$current_user->ID);
		
		if ($is_paid) 			$tmp .= "<br /><br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br /><br />";
		else 					$tmp .= "<br />".$coins_list;
		
		
		$tmp .= "<div class='gourlbox' style='min-width:".$box_width."px;'>";
		
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;
	
		$tmp .= "</div>";
		
		
		// End
		$tmp .= "</div>";
	
	
		if (!$is_paid && $textBelow && !$checkout_done) $tmp .= "<br /><br /><br />" . "<div class='gourlmembershiptext'>".$textBelow."</div>";
	}
	
	
	
	
		// Lock Page
		// -----------------------
		if (!$is_paid && !$preview_mode && !$checkout)
		{
			$tmp = GOURL_LOCK_START.$tmp.GOURL_LOCK_END;
	
			add_filter('the_content', 		'gourl_lock_filter', 11111);
			add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
			add_filter('the_content_feed', 	'gourl_lock_filter', 11111);

			
			if ($hideTitles && $hideCurTitle)
			{
				if (!$logged)
				{
					add_filter("wp_title", 		'gourl_hide_headtitle_unlogged', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle_unlogged', 11111);
				}
				else
				{
					add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				}
				
				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
			}
			elseif ($hideTitles)
			{
				add_filter('the_title', 	'gourl_hide_menu_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_menu_titles', 11111);
			}
			elseif ($hideCurTitle)
			{
				if (!$logged)
				{
					add_filter("wp_title", 		'gourl_hide_headtitle_unlogged', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle_unlogged', 11111);
				}
				else
				{
					add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				}
				
				add_filter('the_title', 	'gourl_hide_page_title', 11111);
				add_filter('the_title_rss', 'gourl_hide_page_title', 11111);
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
		
		$html = $tmp;
	
		return $tmp;
	}
	
	
	
	
	
	
	
	/*
	 *  37.
	*/
	public function page_membership_users()
	{
		global $wpdb;
	
		$dt = gmdate('Y-m-d H:i:s');
		
		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = trim($_GET["s"]);
			if ($s == "active") $search = " && startDate <= '$dt' && endDate >= '$dt' && disabled = 0";
			elseif ($s == "manual") $search = " && paymentID = 0";
			elseif ($s == "disabled") $search = " && disabled = 1";
			elseif (strpos($s, "user_") === 0 && is_numeric(substr($s, 5))) $search = " && userID = ".intval(substr($s, 5));
			elseif (strpos($s, "user") === 0 && is_numeric(substr($s, 4))) $search = " && userID = ".intval(substr($s, 4));
			elseif (strpos($s, "payment_") === 0) $search = " && paymentID = ".intval(substr($s, 8));
				
			if (!$search)
			{
				$s = esc_sql($s);
				$search = " && (userID LIKE '%".$s."%' || paymentID LIKE '%".$s."%' || DATE_FORMAT(startDate, '%d %M %Y') LIKE '%".$s."%' || DATE_FORMAT(endDate, '%d %M %Y') LIKE '%".$s."%')";
			}
		}
		
		$res = $wpdb->get_row("SELECT count(membID) as cnt from crypto_membership WHERE 1".$search, OBJECT);
		$total = (int)$res->cnt;
	
		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE startDate <= '$dt' && endDate >= '$dt' && disabled = 0".$search, OBJECT);
		$active = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE paymentID = 0".$search, OBJECT);
		$manual = (int)$res->cnt;
		
		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE disabled = 1".$search, OBJECT);
		$disabled = (int)$res->cnt;
	
	
		$wp_list_table = new  gourl_table_premiumusers($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();

		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Premium Users', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'paypermembership_user">' . __('Manually Add New User', GOURL) . '</a>'.$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'paypermembership">' . __('Options', GOURL) . '</a>', 4);

		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
	
		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . ($search?__('Found', GOURL):__('Total', GOURL)). ":</b> " . $total . " " . __('records', GOURL) . $this->space(3);
		echo "<b>" . __('Active Premium Users', GOURL). ":</b> ".$this->space().($search?$active:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=active'>$active</a>") . " " . __('users', GOURL) . $this->space(3);
		echo "<b>" . __('Manually Added', GOURL). ":</b> ".$this->space().($search?$manual:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=manual'>$manual</a>") . " " . __('users', GOURL) . $this->space(3);
		echo "<b>" . __('Manually Disabled', GOURL). ":</b> ".$this->space().($search?$disabled:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=disabled'>$disabled</a>") . " " . __('users', GOURL);
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."paypermembership_users'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";
	
		echo '<div class="'.GOURL.'userstable">';
	
		if ($this->updated)  echo '<div class="updated"><p>'.__('Table have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		
		$wp_list_table->display();
	
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
	
		return true;
	}
		
	
	
	
	
	
	/**************** E. PAY-PER-MEMBERSHIP - NEW PREMIUM USER ************************************/
	

	/*
	 *  38.
	*/
	public function page_membership_user()
	{
		global $wpdb;
	
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		else $message = "";
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		
		$tmp .= $this->page_title($this->id?__('Edit Premium User Membership', GOURL):__('New User Membership', GOURL), 4);
		$tmp .= "<div class='".GOURL."intro postbox'>";
		$tmp .=  __('Create Premium Membership manually if a user has sent the wrong amount of payment - therefore plugin cannot process payment and cannot create user premium membership in automatic mode', GOURL);
		$tmp .= "</div>";
		$tmp .= $message;
	
		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."paypermembership_user&id=".$this->id."'>";
	
		$tmp .= "<div class='postbox'>";
		
		$tmp .= '<div class="alignright"><br />';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_user&id='.$this->id.(isset($_GET['userID'])?"&userID=".$_GET['userID']:"").'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_users">'.__('All Premium Users', GOURL).'</a>';
		$tmp .= '</div>';
		
		$tmp .= "<h3 class='hndle'>".__('Manually create Premium Membership', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_membership_newuser" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_user">'.__('New Membership', GOURL).'</a>';
		$tmp .= '</div><br /><br />';

		
		$tmp .= "<table class='".GOURL."table ".GOURL."newmembership'>";
		
		$tmp .= '<tr><th>'.__('User', GOURL).':</th>';
		$tmp .= '<td>';
				
		// User Selected
		$f = true;
		$this->record["userID"] = 0;
		if (isset($_GET['userID']) && intval($_GET['userID'])) 
		{
			$obj =  get_userdata(intval($_GET['userID']));
			if ($obj->data->user_nicename)
			{ 
				$tmp .= "<b>".$obj->data->user_nicename . $this->space(3)."-".$this->space(2)."id ".$obj->ID."</b>";
				$tmp .= '<input type="hidden" name="'.GOURL.'userID" id="'.GOURL.'userID" value="'.$obj->ID.'" />';
				$this->record["userID"] = $obj->ID;
				$f = false;
			}
		}
		
		
		if ($f)
		{
			$arr = get_users();
			
			$tmp .= '<select name="'.GOURL.'userID" id="'.GOURL.'userID">';
			$tmp .= '<option value="0">'.__('Select User', GOURL).'</option>';
		
			$arr = array_slice($arr, 0, 5000);
			
			$arr2 = array(); 
			foreach($arr as $row) $arr2[$row->ID] = $row->data->user_login . $this->space(3) . "-" . $this->space(2) . "id ".$row->ID." ";
			$arr = $arr2;
			asort($arr);
					
			foreach($arr as $k => $v)
					$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['userID']).'>'.$v.'</option>';
		
			$tmp .= '</select>';
		}
		
		$tmp .= '<br /><em>'.sprintf(__('Select User. &#160; Current lock pages level: <a href="'.GOURL_ADMIN.GOURL.'paypermembership#'.GOURL.'form">%s</a>. Website Editors / Admins will have all the time full access to locked pages and see original page content.', GOURL), $this->lock_level_membership[intval(get_option(GOURL.'ppmLevel'))]).'</em>';		
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Premium Start Date', GOURL).':</th>';
		$tmp .= '<td><input type="date" id="'.GOURL.'startDate" name="'.GOURL.'startDate" value="'.htmlspecialchars($this->record['startDate'], ENT_QUOTES).'" />';
		$tmp .= '<br /><em>'.__('Premium Membership Start Date. Format: dd/mm/yyyy', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Premium End Date', GOURL).':</th>';
		$tmp .= '<td><input type="date" id="'.GOURL.'endDate" name="'.GOURL.'endDate" value="'.htmlspecialchars($this->record['endDate'], ENT_QUOTES).'" />';
		$tmp .= '<br /><em>'.__('Premium Membership End Date. Format: dd/mm/yyyy', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '</table>';
		
		
		$tmp .= '</div>';
		$tmp .= '</div>';
		$tmp .= '</div>';
	
		
		echo $tmp;
		
	}
	
	
	
	/*
	 *  39.
	*/
	private function check_membership_newuser()
	{
		$this->record_errors = array();
	
		if (!$this->record["userID"]) 		$this->record_errors[] = __('User - cannot be empty', GOURL);
		if (!$this->record["startDate"]) 	$this->record_errors[] = __('Start Date - cannot be empty', GOURL);

		if (!$this->record["endDate"]) 		$this->record_errors[] = __('End Date - cannot be empty', GOURL);
		elseif (strtotime($this->record["startDate"]) >= strtotime($this->record["endDate"])) $this->record_errors[] = __('End Date - invalid value', GOURL);
		elseif (strtotime($this->record["endDate"]) <= strtotime(gmdate("Y-m-d"))) $this->record_errors[] = __('End Date - invalid value', GOURL);
		
		return true;
	}
	
	
	
	/*
	 *  40.
	*/
	private function save_membership_newuser()
	{
		global $wpdb;

		$sql = "INSERT INTO crypto_membership (userID, paymentID, startDate, endDate, disabled, recordCreated)
				VALUES (
						'".esc_sql($this->record['userID'])."',
						0,
						'".esc_sql($this->record['startDate']." 00:00:00")."',
						'".esc_sql($this->record['endDate']." 23:59:00")."',
						0,
						'".esc_sql(gmdate('Y-m-d H:i:s'))."'		
					)";
		
		$wpdb->query($sql);
		
		return true;
	}
	
	
	
	
	
	/**************** E. PAY-PER-PRODUCT ************************************/
	
	
	
	/*
	 *  41.
	*/
	public function check_product()
	{
		$this->record_errors = array();

		if ($this->record["productID"] != $this->id) $this->record_errors[] = __('Invalid Product ID, Please reload page', GOURL);
			
		if (!$this->record["productTitle"]) 							$this->record_errors[] = __('Product Title - cannot be empty', GOURL);
		elseif (mb_strlen($this->record["productTitle"]) > 100) 		$this->record_errors[] = __('Product Title - Max size 100 symbols', GOURL);

		$this->record["priceUSD"] = str_replace(",", "", $this->record["priceUSD"]);
		$this->record["priceCoin"] = str_replace(",", "", $this->record["priceCoin"]);
		if ($this->record["priceUSD"] == 0 && $this->record["priceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->record["priceUSD"] != 0 && $this->record["priceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->record["priceUSD"] != 0 && (!is_numeric($this->record["priceUSD"]) || round($this->record["priceUSD"], 2) != $this->record["priceUSD"] || $this->record["priceUSD"] < 0.01 || $this->record["priceUSD"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->record["priceUSD"]);
		if ($this->record["priceCoin"] != 0 && (!is_numeric($this->record["priceCoin"]) || round($this->record["priceCoin"], 4) != $this->record["priceCoin"] || $this->record["priceCoin"] < 0.0001 || $this->record["priceCoin"] > 50000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->record["priceCoin"], $this->record["priceLabel"], $this->record["priceLabel"]);
							
		if ($this->record["priceLabel"] && !isset($this->coin_names[$this->record["priceLabel"]])) $this->record_errors[] = sprintf(__('Price label "%s" - invalid value', GOURL), $this->record["priceLabel"]);
		
		if ($this->record["purchases"] && (!is_numeric($this->record["purchases"]) || round($this->record["purchases"]) != $this->record["purchases"] || $this->record["purchases"] < 0)) $this->record_errors[] = __('Purchase Limit - invalid value', GOURL);
		
		if (!$this->record["expiryPeriod"]) $this->record_errors[] = __('Field "Expiry Period" - cannot be empty', GOURL);
		elseif (!in_array($this->record["expiryPeriod"], $this->expiry_period))	$this->record_errors[] = __('Field "Expiry Period" - invalid value', GOURL);
		
		if (!isset($this->languages[$this->record["lang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);
		
		if (!$this->record["defCoin"]) $this->record_errors[] = __('Field "PaymentBox Coin" - cannot be empty', GOURL);
		elseif (!isset($this->coin_names[$this->record["defCoin"]])) $this->record_errors[] = __('Field "PaymentBox Coin" - invalid value', GOURL);
		elseif (!isset($this->payments[$this->record["defCoin"]])) $this->record_errors[] = sprintf( __('Field "PaymentBox Coin" - payments in %s not available. Please re-save record', GOURL), $this->coin_names[$this->record["defCoin"]]);
		elseif ($this->record["priceCoin"] != 0 && $this->record["defCoin"] != $this->record["priceLabel"]) $this->record_errors[] = sprintf(__('Field "PaymentBox Coin" - please select "%s" because you have entered price in %s', GOURL), $this->coin_names[$this->record["priceLabel"]], $this->coin_names[$this->record["priceLabel"]]);

		if ($this->record["emailUser"])
		{
			if (!$this->record["emailUserFrom"]) 	$this->record_errors[] = __('Email to Buyer: From Email - cannot be empty', GOURL);
			if (!$this->record["emailUserTitle"]) 	$this->record_errors[] = __('Purchase Email Subject - cannot be empty', GOURL);
			if (!$this->record["emailUserBody"]) 	$this->record_errors[] = __('Purchase Receipt - cannot be empty', GOURL);
		}
		
		if ($this->record["emailAdmin"])
		{
			if (!$this->record["emailAdminFrom"]) 			$this->record_errors[] = __('Sale Notification From - cannot be empty', GOURL);
			if (!$this->record["emailAdminTitle"]) 			$this->record_errors[] = __('Sale Notification Subject - cannot be empty', GOURL);
			if (!$this->record["emailAdminBody"]) 			$this->record_errors[] = __('Sale Notification - cannot be empty', GOURL);
			if (!trim($this->record["emailAdminTo"])) 	$this->record_errors[] = __('Sale Notification To - cannot be empty', GOURL);
		}
		
		if ($this->record["emailUserFrom"] && !filter_var($this->record["emailUserFrom"], FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Email to Buyer: From Email - %s - invalid email format', GOURL), $this->record["emailUserFrom"]);
		if ($this->record["emailAdminFrom"] && !filter_var($this->record["emailAdminFrom"], FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Sale Notification From - %s - invalid email format', GOURL), $this->record["emailAdminFrom"]);
		if ($this->record["emailAdminTo"])
			foreach(explode("\n", $this->record["emailAdminTo"]) as $v)
				if (trim($v) && !filter_var(trim($v), FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Sale Notification To - %s - invalid email format', GOURL), trim($v)); 
		
		if ($this->record["priceCoin"] != 0 && !$this->record["defShow"] && !$this->record_errors) $this->record["defShow"] = 1;
		//if ($this->record["priceCoin"] != 0 && !$this->record["defShow"]) $this->record_errors[] = sprintf(__('Field "Use Default Coin Only" - check this field because you have entered price in %s. Please use price in USD if you want to accept multiple coins', GOURL), $this->coin_names[$this->record["priceLabel"]]);
		
		return true;
		
	}
	
	
	/*
	 *  42.
	*/
	public function save_product()
	{
		global $wpdb;
		
		$dt = gmdate('Y-m-d H:i:s');
		
		if ($this->record['priceUSD'] <= 0)  $this->record['priceUSD'] = 0;
		if ($this->record['priceCoin'] <= 0 || $this->record['priceUSD'] > 0) { $this->record['priceCoin'] = 0; $this->record['priceLabel'] = ""; }
		
		if ($this->id)
		{
			$sql = "UPDATE crypto_products
					SET
						productTitle 	= '".esc_sql($this->record['productTitle'])."',
						productText 	= '".esc_sql($this->record['productText'])."',
						finalText 		= '".esc_sql($this->record['finalText'])."',
						active 			= '".$this->record['active']."',
						priceUSD 		= ".$this->record['priceUSD'].",
						priceCoin 		= ".$this->record['priceCoin'].",
						priceLabel 		= '".$this->record['priceLabel']."',
						purchases 		= '".$this->record['purchases']."',
						expiryPeriod	= '".esc_sql($this->record['expiryPeriod'])."',
						lang 			= '".$this->record['lang']."',
						defCoin			= '".esc_sql($this->record['defCoin'])."',
						defShow 		= '".$this->record['defShow']."',
						emailUser		= '".$this->record['emailUser']."',
						emailUserFrom	= '".esc_sql($this->record['emailUserFrom'])."',				
						emailUserTitle	= '".esc_sql($this->record['emailUserTitle'])."',
						emailUserBody	= '".esc_sql($this->record['emailUserBody'])."',
						emailAdmin		= '".$this->record['emailAdmin']."',
						emailAdminFrom	= '".esc_sql($this->record['emailAdminFrom'])."',				
						emailAdminTitle	= '".esc_sql($this->record['emailAdminTitle'])."',
						emailAdminBody	= '".esc_sql($this->record['emailAdminBody'])."',
						emailAdminTo= '".esc_sql($this->record['emailAdminTo'])."',
						updatetime 		= '".$dt."'
					WHERE productID 	= ".$this->id."
					LIMIT 1";
		}
		else
		{
			$sql = "INSERT INTO crypto_products (productTitle, productText, finalText, active, priceUSD, priceCoin, priceLabel, purchases, expiryPeriod, lang, defCoin, defShow,
							emailUser, emailUserFrom, emailUserTitle, emailUserBody, emailAdmin, emailAdminFrom, emailAdminTitle, emailAdminBody, emailAdminTo, paymentCnt, updatetime, createtime)
					VALUES (
							'".esc_sql($this->record['productTitle'])."',
							'".esc_sql($this->record['productText'])."',
							'".esc_sql($this->record['finalText'])."',
							1,
							".$this->record['priceUSD'].",
							".$this->record['priceCoin'].",
							'".$this->record['priceLabel']."',
							'".$this->record['purchases']."',
							'".esc_sql($this->record['expiryPeriod'])."',
							'".$this->record['lang']."',
							'".esc_sql($this->record['defCoin'])."',
							'".$this->record['defShow']."',
							'".$this->record['emailUser']."',
							'".esc_sql($this->record['emailUserFrom'])."',				
							'".esc_sql($this->record['emailUserTitle'])."',
							'".esc_sql($this->record['emailUserBody'])."',
							'".$this->record['emailAdmin']."',
							'".esc_sql($this->record['emailAdminFrom'])."',				
							'".esc_sql($this->record['emailAdminTitle'])."',
							'".esc_sql($this->record['emailAdminBody'])."',
							'".esc_sql($this->record['emailAdminTo'])."',
							0,
							'".$dt."',
							'".$dt."'
						)";
		}
		
		if (!get_option('users_can_register')) update_option('users_can_register', 1);
		
		if ($wpdb->query($sql) === false) $this->record_errors[] = "Error in SQL : " . $sql;
		elseif (!$this->id) $this->id = $wpdb->insert_id;
		
		return true;
		
	}
	
	
	
	
	/*
	 *  43.
	*/
	public function page_newproduct()
	{
	
		$preview 		= ($this->id && isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
		$preview_final  = ($this->id && isset($_GET["previewfinal"]) && $_GET["previewfinal"] == "true") ? true : false;
		$preview_email  = ($this->id && isset($_GET["previewemail"]) && $_GET["previewemail"] == "true") ? true : false;
	
		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Record has been saved <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";
	
		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';
	
	
		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title($this->id?__('Edit Product', GOURL):__('New Product', GOURL), 5);
		$tmp .= $message;
	
		$short_code = '['.GOURL_TAG_PRODUCT.' id="'.$this->id.'"]';
		
		if ($preview || $preview_final || $preview_email)
		{
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code) . ($preview_email?$this->space(2)."-".$this->space().__('Emails', GOURL):"");
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";
			
			
			if ($preview_email)
			{
				$txt_from = array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{user_url}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{payment_url}", "{transaction_id}", "{transaction_time}");
				$txt_to = array("John Smith", "john2", 1, "john@example.com", admin_url("user-edit.php?user_id=1"), "0.335301 BTC", "~112.3 USD", 11, GOURL_ADMIN.GOURL."payments&s=payment_11", "2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1", "18 Dec 2014, 11:15:48 am");
				
				$tmp .= "<p>".__('Used template tags for preview:', GOURL)."<br/><i><b>{user_fullname}</b> - John Smith, <b>{user_username}</b> - john2, <b>{user_id}</b> - 1, <b>{user_email}</b> - john@example.com, <b>{user_url}</b> - ".admin_url("user-edit.php?user_id=1").", <b>{paid_amount}</b> - 0.335301 BTC, <b>{paid_amount_usd}</b> - ~112.3 USD, <b>{payment_id}</b> - 11, <b>{payment_url}</b> - ".GOURL_ADMIN.GOURL."payments&s=payment_11, <b>{transaction_id}</b> - 2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1, <b>{transaction_time}</b> - 18 Dec 2014, 11:15:48 am</i></p>";
				
				
				$subject = (mb_strpos($this->record['emailUserTitle'], "{")=== false) ? $this->record['emailUserTitle'] : str_replace($txt_from, $txt_to, $this->record['emailUserTitle']);
				$body = (mb_strpos($this->record['emailUserBody'], "{")=== false) ? $this->record['emailUserBody'] : str_replace($txt_from, $txt_to, $this->record['emailUserBody']);
				
				$tmp .= "<h3><br/>".__('Email to Buyer - Purchase Receipt', GOURL).$this->space(2).gourl_checked_image($this->record['emailUser']).$this->space()."<small class='".($this->record['emailUser']?"updated":"error")."'>".($this->record['emailUser']?__('Activated', GOURL):__('Not Active', GOURL))."</small></h3>";
				$tmp .= "<hr align='left' width='200'>";
				$tmp .= "<p><b>".__('From:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailUserFrom'], ENT_QUOTES)."</p>"; 
				$tmp .= "<p><b>".__('To:', GOURL)."</b>".$this->space().__('- user registered email -', GOURL)."</p>";
				$tmp .= "<p><b>".__('Subject:', GOURL)."</b>".$this->space().htmlspecialchars($subject, ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Body:', GOURL)."</b></p>".nl2br(htmlspecialchars($body, ENT_QUOTES));
				
				
				$tmp .= "<br /><br />";
				
				$subject = (mb_strpos($this->record['emailAdminTitle'], "{")=== false) ? $this->record['emailAdminTitle'] : str_replace($txt_from, $txt_to, $this->record['emailAdminTitle']);
				$body = (mb_strpos($this->record['emailAdminBody'], "{")=== false) ? $this->record['emailAdminBody'] : str_replace($txt_from, $txt_to, $this->record['emailAdminBody']);
				
				$tmp .= "<h3>".__('Email to Seller/Admin - Sale Notification', GOURL).$this->space(2).gourl_checked_image($this->record['emailAdmin']).$this->space()."<small class='".($this->record['emailAdmin']?"updated":"error")."'>".($this->record['emailAdmin']?__('Activated', GOURL):__('Not Active', GOURL))."</small></h3>";
				$tmp .= "<hr align='left' width='200'>";
				$tmp .= "<p><b>".__('From:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailAdminFrom'], ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('To:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailAdminTo'], ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Subject:', GOURL)."</b>".$this->space().htmlspecialchars($subject, ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Body:', GOURL)."</b></p>".nl2br(htmlspecialchars($body, ENT_QUOTES));
				
			}
			else
			{
				$tmp .= $this->shortcode_product(array("id"=>$this->id), $preview_final);
			}
			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160;  '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}
	
		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."product&id=".$this->id."'>";
	
		$tmp .= "<div class='postbox'>";
		
		$tmp .= '<div class="alignright"><br />';
		if ($this->id && $this->record['paymentCnt']) $tmp .= "<a style='margin-top:-7px' href='".GOURL_ADMIN.GOURL."payments&s=product_".$this->id."' class='".GOURL."button button-secondary'>".sprintf(__('Sold %d copies', GOURL), $this->record['paymentCnt'])."</a>".$this->space();
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'product">'.__('New product', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'product&id='.$this->id.'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'products">'.__('All Paid Products', GOURL).'</a>';
		$tmp .= '</div>';
		
		$tmp .= "<h3 class='hndle'>".__(($this->id?'Edit Product Payment Box':'Create New Product Payment Box'), GOURL)."</h3>";
		$tmp .= "<div class='inside'>";
	
		$tmp .= '<input type="hidden" name="ak_action" value="'.GOURL.'save_product" />';
	
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id && !$preview) 		$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview', GOURL)."</a>".$this->space(2);
		if ($this->id && !$preview_final) 	$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&previewfinal=true' class='".GOURL."button button-secondary'>".__('Completed Preview', GOURL)."</a>".$this->space(2);
		if ($this->id && !$preview_email) 	$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&previewemail=true' class='".GOURL."button button-secondary'>".__('Emails Preview', GOURL)."</a>".$this->space(2);
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_product_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br /><br />';
	
	
		$tmp .= "<table class='".GOURL."table ".GOURL."product'>";
	
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Product ID', GOURL).':</th>';
			$tmp .= '<td><b>'.$this->record['productID'].'</b></td>';
			$tmp .= '</tr>';
			$tmp .= '<tr><th>'.__('Shortcode', GOURL).':</th>';
			$tmp .= '<td><b>['.GOURL_TAG_PRODUCT.' id="'.$this->id.'"]</b><br /><em>'.sprintf(__('<p>Just <a target="_blank" href="%s">add this shortcode</a> to any your page or post (in html view) and cryptocoin payment box will be display', GOURL), plugins_url('/images/tagexample_product_full.png', __FILE__)).'</em></td>';
			$tmp .= '</tr>';
		}
	
		$tmp .= '<tr><th>'.__('Product Title', GOURL).':';
		$tmp .= '<input type="hidden" name="'.GOURL.'productID" id="'.GOURL.'productID" value="'.htmlspecialchars($this->record['productID'], ENT_QUOTES).'">';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'productTitle" id="'.GOURL.'productTitle" value="'.htmlspecialchars($this->record['productTitle'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Title for the product. Users will see this title', GOURL).'</em></td>';
		$tmp .= '</tr>';
	
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Active ?', GOURL).'</th>';
			$tmp .= '<td><input type="checkbox" name="'.GOURL.'active" id="'.GOURL.'active" value="1" '.$this->chk($this->record['active'], 1).' class="widefat"><br /><em>'.__('<p>If box is not checked, visitors cannot pay you for this product', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}
			
		$tmp .= '<tr><th>'.__('Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'priceUSD" id="'.GOURL.'priceUSD" value="'.htmlspecialchars($this->record['priceUSD'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'priceCoin" id="'.GOURL.'priceCoin" value="'.htmlspecialchars($this->record['priceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'priceLabel" id="'.GOURL.'priceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['priceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br /><em>'.__('Please specify product price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don\'t need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target="_blank" href="http://goo.gl/L8H9gG">Cryptsy "autosell" feature</a> (auto trade your cryptocoins to USD).', GOURL).'</em>';
		$tmp .= '</td></tr>';
		

		$tmp .= '<tr><th>'.__('Purchase Limit', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'purchases" id="'.GOURL.'purchases" value="'.htmlspecialchars($this->record['purchases'], ENT_QUOTES).'"><label>'.__('copies', GOURL).'</label><br /><em>'.__('The maximum number of times a product may be purchased. Leave blank or set to 0 for unlimited number of product purchases', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'expiryPeriod" id="'.GOURL.'expiryPeriod">';

		foreach($this->expiry_period as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['expiryPeriod']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br /><em>'.sprintf(__('Period after which the payment becomes obsolete and new Product Payment Box will be shown for this product (you can use it to take new payments from users periodically on daily/monthly basis)<br>For quickly repeated purchases/shopping cart, you can use <a href="%s">WooCommerce</a> with <a href="%s">GoUrl WooCommerce Addon</a> also', GOURL), "https://wordpress.org/plugins/woocommerce/", admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')).'</em></td>';
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
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'defShow" id="'.GOURL.'defShow" value="1" '.$this->chk($this->record['defShow'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, payment box will accept payments in one default coin "PaymentBox Coin" for this product (no multiple coins)', GOURL).'</em></td>';
		$tmp .= '</tr>';

		
		$tmp .= '<tr><th>'.__('A. Unpaid Product Description', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->record['productText'], GOURL.'productText', array('textarea_name' => GOURL.'productText', 'quicktags' => true, 'media_buttons' => true, 'textarea_rows' => 8, 'wpautop' => false));
		$tmp  = '<br /><em>'.__('Product Description. Users will see this product description when no payment has been received', GOURL).'</em>';
		$tmp .= '</td></tr>';
		
		$tmp .= '<tr><th>'.__('B. Paid Product Description', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->record['finalText'], GOURL.'finalText', array('textarea_name' => GOURL.'finalText', 'quicktags' => true, 'media_buttons' => true, 'textarea_rows' => 8, 'wpautop' => false));
		$tmp  = '<br /><em>'.sprintf(__('Users will see this product description when payment has been successfully received. If you leave field empty, it will display content from "A. Unpaid Product Description" field<br />Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {paid_amount} {paid_amount_usd} {payment_id} {transaction_id} {transaction_time}').'</em>';
		$tmp .= '</td></tr>';
		
		
		$tmp .= '<tr><th>'.__('Email to Buyer ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'emailUser" id="'.GOURL.'emailUser" value="1" '.$this->chk($this->record['emailUser'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, purchase receipt email will be sent to Buyer on user registered email', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('From Email', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailUserFrom" id="'.GOURL.'emailUserFrom" value="'.htmlspecialchars($this->record['emailUserFrom'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Email to Buyer: Email to send purchase receipts from. This will act as the "from" and "reply-to" address', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Purchase Email Subject', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailUserTitle" id="'.GOURL.'emailUserTitle" value="'.htmlspecialchars($this->record['emailUserTitle'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Email to Buyer: Enter the subject line for the purchase receipt email', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Purchase Receipt', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailUserBody" name="'.GOURL.'emailUserBody" class="widefat" style="height: 200px;">'.htmlspecialchars($this->record['emailUserBody'], ENT_QUOTES).'</textarea><br /><em>'.sprintf(__('Email to Buyer: Enter the email that is sent to users after completing a successful purchase. HTML is not accepted.<br />Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Email to Seller/Admin ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'emailAdmin" id="'.GOURL.'emailAdmin" value="1" '.$this->chk($this->record['emailAdmin'], 1).' class="widefat"><br /><em>'.__('<p>If box is checked, new sale notification email will be sent to Seller/Admin', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Sale Notification From', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailAdminFrom" id="'.GOURL.'emailAdminFrom" value="'.htmlspecialchars($this->record['emailAdminFrom'], ENT_QUOTES).'" class="widefat"><br /><em>'.__('Email to Seller: Email to send sale notification from. This will act as the "from" and "reply-to" address', GOURL).'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Sale Notification Subject', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailAdminTitle" id="'.GOURL.'emailAdminTitle" value="'.htmlspecialchars($this->record['emailAdminTitle'], ENT_QUOTES).'" class="widefat"><br /><em>'.sprintf(__('Email to Seller: Enter the subject line for the sale notification email<br />Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Sale Notification', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailAdminBody" name="'.GOURL.'emailAdminBody" class="widefat" style="height: 200px;">'.htmlspecialchars($this->record['emailAdminBody'], ENT_QUOTES).'</textarea><br /><em>'.sprintf(__('Email to Seller: Enter the sale notification email that is sent to seller/admin after user completing a successful purchase.<br />Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';
		
		$tmp .= '<tr><th>'.__('Sale Notification To', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailAdminTo" name="'.GOURL.'emailAdminTo" class="widefat" style="height: 120px;">'.htmlspecialchars($this->record['emailAdminTo'], ENT_QUOTES).'</textarea><br /><em>'.__('Email to Seller: Enter the email address(es) that should receive a notification anytime a sale is made, one per line').'</em></td>';
		$tmp .= '</tr>';
		
		
		
		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Total Sold', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentCnt" id="'.GOURL.'paymentCnt" value="'.htmlspecialchars($this->record['paymentCnt'], ENT_QUOTES).'"><b>'.$this->record['paymentCnt'].' '.__('copies', GOURL).'</b></td>';
			$tmp .= '</tr>';

			if ($this->record['paymentCnt'])
			{
				$tmp .= '<tr><th>'.__('Latest Received Payment', GOURL).':</th>';
				$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentTime" id="'.GOURL.'paymentTime" value="'.htmlspecialchars($this->record['paymentTime'], ENT_QUOTES).'"><b>'.date('d M Y, H:i:s a', strtotime($this->record['paymentTime'])).' GMT</b></td>';
				$tmp .= '</tr>';
			}

			if ($this->record['updatetime'] && $this->record['updatetime'] != $this->record['createtime'])
			{
				$tmp .= '<tr><th>'.__('Record Updated', GOURL).':</th>';
				$tmp .= '<td><input type="hidden" name="'.GOURL.'updatetime" id="'.GOURL.'updatetime" value="'.htmlspecialchars($this->record['updatetime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['updatetime'])).' GMT</td>';
				$tmp .= '</tr>';
			}

			$tmp .= '<tr><th>'.__('Record Created', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'createtime" id="'.GOURL.'createtime" value="'.htmlspecialchars($this->record['createtime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['createtime'])).' GMT</td>';
			$tmp .= '</tr>';
			
			$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
			$tmp .= '<td><em>'.sprintf(__('Optional - add in file gourl_ipn.php code below. <a href="%s">Read more &#187;</a><br><i>case "product_%s": &#160; &#160; // order_ID = product_%s<br>// ...your_code...<br>break;</i></em></td>', GOURL), GOURL_ADMIN.GOURL."#i5", $this->id, $this->id);
			$tmp .= '</tr>';
		}

		$tmp .= '</table>';


		$tmp .= '</div></div>';
		$tmp .= '</form></div>';

		echo $tmp;

		return true;
	}
	
	
	
	/*
	 *  44.
	*/
	public function page_products()
	{
		global $wpdb;
	
		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_products_intro", $intro);
		}
		else $intro = get_option(GOURL."page_products_intro");
		
		
		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = trim($_GET["s"]);
				
			if ($s == "sold") 			$search = " && paymentCnt > 0";
			elseif ($s == "active") 	$search = " && active != 0";
			elseif ($s == "inactive") 	$search = " && active = 0";
			elseif (in_array(strtolower($s), $this->coin_names)) $search = " && (priceLabel = '".array_search(strtolower($s), $this->coin_names)."' || defCoin = '".array_search(strtolower($s), $this->coin_names)."')";
			elseif (isset($this->coin_names[strtoupper($s)])) $search = " && (priceLabel = '".strtoupper($s)."' || defCoin = '".strtoupper($s)."')";
				
			if (!$search)
			{
				if (in_array(ucwords(strtolower($s)), $this->languages)) $s = array_search(ucwords(strtolower($s)), $this->languages);
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
				$s = esc_sql($s);
				$search = " && (productTitle LIKE '%".$s."%' || productText LIKE '%".$s."%' || finalText LIKE '%".$s."%' || priceUSD LIKE '%".$s."%' || priceCoin LIKE '%".$s."%' || priceLabel LIKE '%".$s."%' || expiryPeriod LIKE '%".$s."%' || defCoin LIKE '%".$s."%' || emailUserFrom LIKE '%".$s."%' || emailUserTitle LIKE '%".$s."%' || emailUserBody LIKE '%".$s."%' || emailAdminFrom LIKE '%".$s."%' || emailAdminTitle LIKE '%".$s."%' || emailAdminBody LIKE '%".$s."%' || emailAdminTo LIKE '%".$s."%' || paymentCnt LIKE '%".$s."%' || lang LIKE '%".$s."%' || DATE_FORMAT(createtime, '%d %M %Y') LIKE '%".$s."%')";
			}
		}
		
		$res = $wpdb->get_row("SELECT count(productID) as cnt from crypto_products WHERE active != 0".$search, OBJECT);
		$active = (int)$res->cnt;
	
		$res = $wpdb->get_row("SELECT count(productID) as cnt from crypto_products WHERE active = 0".$search, OBJECT);
		$inactive = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT sum(paymentCnt) as total from crypto_products WHERE paymentCnt > 0".$search, OBJECT);
		$sold = (int)$res->total;
		
	
		$wp_list_table = new  gourl_table_products($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();
	
		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Paid Products', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'product">' . __('Add New Product', GOURL) . '</a>', 5);
		
		if (!$intro)
		{
			echo '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'products&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			echo "<div class='".GOURL."intro postbox'>";
			echo '<a style="float:right" target="_blank" href="http://gourl.io/lib/examples/pay-per-product-multi.php"><img hspace="10" width="240" height="95" title="Example - Pay Per Product" src="'.plugins_url('/images/pay-per-product.png', __FILE__).'" border="0"></a>';
			echo '<p>'.__('Use "Pay-Per-product" - sell any of your products online to registered users. Email notifications to Buyer/Seller.', GOURL) . '</p>';
			echo '<p>'.sprintf(__('You will need to <a href="%sproduct">create a new product record</a> of what you are selling, you get custom WordPress shortcode, <a href="%s">place that shortcode</a> on any of your WordPress pages and user will see the payment box. ', GOURL), GOURL_ADMIN.GOURL, plugins_url('/images/tagexample_product_full.png', __FILE__)).'</p>';
			echo '<p>'.sprintf(__('Please activate website registration (General Settings &#187; Membership - <a href="%s">Anyone can register</a>). &#160; For unregistered visitors - you can customize <a href="%s">Login Image</a> or choose to display <a href="%s">Login Form</a> ', GOURL), admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images", GOURL_ADMIN.GOURL."settings#images").'</p>';
			echo '<p>'.sprintf(__('See also - <a href="%s#i3">Installation Instruction</a>', GOURL), GOURL_ADMIN.GOURL) . '</p>';
			echo '<p><b>-----------------<br>'.sprintf(__('Alternatively, you can use free <a href="%s">WooCommerce</a> plugin (advanced shopping plugin with \'GUEST CHECKOUT\' option) with our <a href="%s">Woocommerce Bitcoin/Altcoin Gateway</a> addon', GOURL), admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce+excelling+eCommerce+WooThemes+Beautifully'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')) . '</b></p>';
			echo  "</div>";
		}	
	
		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		if ($intro) echo '<a href="'.GOURL_ADMIN.GOURL.'products&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a> &#160; &#160; ';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
	
		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . __($search?__('Found', GOURL):__('Total products', GOURL)). ":</b> " . ($active+$inactive) . " " . __('products', GOURL) . $this->space(1) . "( ";
		echo "<b>" . __('Active', GOURL). ":</b> " . ($search?$active:"<a href='".GOURL_ADMIN.GOURL."products&s=active'>$active</a>"). " " . __('products', GOURL) . $this->space(2);
		echo "<b>" . __('Inactive', GOURL). ":</b> " . ($search?$inactive:"<a href='".GOURL_ADMIN.GOURL."products&s=inactive'>$inactive</a>") . " " . __('products', GOURL) . $this->space(1) . ")" . $this->space(4);
		echo "<b>" . __('Total Sold', GOURL). ":</b> " . ($search?$sold:"<a href='".GOURL_ADMIN.GOURL."products&s=sold'>$sold</a>") . " " . __('products', GOURL);
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."products'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";
	
		echo '<div class="'.GOURL.'widetable">';
		echo '<div class="'.GOURL.'producttable" style="min-width:1550px;width:100%;">';
	
		$wp_list_table->display();
	
		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
	
		return true;
				
	}
	
	

	/*
	 *  45.
	*/
	public function shortcode_product($arr, $preview_final = false)
	{
		global $wpdb, $current_user;
	
		// not available activated coins
		if (!$this->payments) return "";
	
		if (!isset($arr["id"]) || !intval($arr["id"])) return '<div>'.__('Invalid format. Use &#160; ['.GOURL_TAG_PRODUCT.' id="..id.."]', GOURL).'</div>';
	
		$id 			= intval($arr["id"]);
		$short_code 	= '['.GOURL_TAG_PRODUCT.' id="<b>'.$id.'</b>"]';
	
	
		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";
	
	
		// Current File Info
		// --------------------------
		$arr = $wpdb->get_row("SELECT * FROM crypto_products WHERE productID = ".$id." LIMIT 1", ARRAY_A);
		if (!$arr) return '<div>'.__('Invalid product id "'.$id.'" - ', GOURL).$short_code.'</div>';
	
	
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();
	
		$active 		= $arr["active"];
		$productTitle 	= $arr["productTitle"];
		$productText 	= $arr["productText"];
		$finalText 		= $arr["finalText"];
		
		$priceUSD 		= $arr["priceUSD"];
		$priceCoin 		= $arr["priceCoin"];
		$priceLabel 	= $arr["priceLabel"];
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $arr["defCoin"] = $priceLabel; $arr["defShow"] = 1; }
		
		$purchases 		= $arr["purchases"];
		$expiryPeriod	= $arr["expiryPeriod"];
		$lang 			= $arr["lang"];
		$defCoin		= $this->coin_names[$arr["defCoin"]];
		$defShow		= $arr["defShow"];
		
		$paymentCnt		= $arr["paymentCnt"];
		$paymentTime	= $arr["paymentTime"];
		$updatetime		= $arr["updatetime"];
		$createtime		= $arr["createtime"];
		$userID 		= "user_".$current_user->ID;
		$orderID 		= "product_".$id; // product_+productID as orderID
		$anchor 		= "gbx".$this->icrc32($id);
	
		if (strip_tags(mb_strlen($productText)) < 5) $productText = '';
		if (strip_tags(mb_strlen($finalText)) < 5) 	 $finalText = $productText;
	
		
	
		// Registered Users can Pay Only
		// --------------------------
	
		if (!is_user_logged_in() || !$current_user->ID)
		{
			$box_html = "<div align='center'>";
			if ($this->options['login_type'] != "1") $box_html .= $this->login_form();
			else $box_html .= "<br /><a href='".wp_login_url(get_permalink())."'><img title='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURL)."' alt='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURL)."' src='".$this->box_image("plogin")."' border='0'></a>";
			$box_html .= "</div><br /><br />";
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
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
	
	
	
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
					"userFormat"  => "MANUAL", 			// registered users only
					"amount"   	  => $priceCoin,		// product price in coin
					"amountUSD"   => $priceUSD,			// product price in USD
					"period"      => $expiryPeriod, 	// payment valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);
	
	
	
			// Initialise Payment Class
			$box = new Cryptobox ($options);
	
	
			// Coin name
			$coinName = $box->coin_name();
	
	
			// Paid or not
			$is_paid = $box->is_paid();
	

			
			// Payment Box HTML
			// ----------------------
			if (!$is_paid && $purchases > 0 && $paymentCnt >= $purchases)
			{
				// A. Sold
				$box_html = "<img alt='".__('Sold Out', GOURL)."' src='".$this->box_image("sold")."' border='0'><br /><br />";
					
			}
			elseif (!$is_paid && !$active)
			{
				// B. Box Not Active
				$box_html = "<img alt='".__('Cryptcoin Payment Box Disabled', GOURL)."' src='".$this->box_image("pdisable")."' border='0'><br /><br />";
			}
			elseif (!$is_paid && $preview_final)
			{
				// C. Preview Final Screen
				$box_html = "<img width='580' height='240' alt='".__('Cryptcoin Payment Box Preview', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_completed.png' border='0'><br /><br />";
			}
			else
			{
				// Coins selection list (html code)
				$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";
	
	
				// Language selection list for payment box (html code)
				$languages_list = display_language_box($lang, $anchor);
	
	
				// D. Active Box
				$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
			}
			
		}
	
		
		// Tags
		// ---------------------
		$adminIntro 	= "";
		if ($is_paid || (!$is_paid && $preview_final))
		{
			$productText = $finalText;
		
			if (mb_strpos($productText, "{") !== false)
			{
				if (!$is_paid && $preview_final)
				{
					$adminIntro = "<p>".__('Used template tags for preview:', GOURL)."<br/><i><b>{user_fullname}</b> - John Smith, <b>{user_username}</b> - john2, <b>{user_id}</b> - 7, <b>{user_email}</b> - john@example.com, <b>{paid_amount}</b> - 0.335301 BTC, <b>{paid_amount_usd}</b> - ~112.3 USD, <b>{payment_id}</b> - 11, <b>{transaction_id}</b> - 2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1, <b>{transaction_time}</b> - 18 Dec 2014, 11:15:48 am</i></p><br/><br/>";
					$txt_to 	= array("John Smith", "john2", 7, "john@example.com", "0.335301 BTC", "~112.3 USD", 11, "2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1", "18 Dec 2014, 11:15:48 am");
				}
				else 
				{
					$user_fullname 		= trim($current_user->user_firstname . " " . $current_user->user_lastname);
					$user_username 		= $current_user->user_login;
					$user_email 		= $current_user->user_email;
					$user_id			= $current_user->ID;
					if (!$user_fullname) $user_fullname =  $user_username;
			
					$details			= $box->payment_info();
					$paid_amount		= gourl_number_format($details->amount, 8) . " " . $details->coinLabel;
					$paid_amount_usd	= gourl_number_format($details->amountUSD, 2) . " USD";
					$payment_id			= $details->paymentID;
					$transaction_id		= $details->txID;
					$transaction_time	= date("d M Y, H:i:s a", strtotime($details->txDate));
			
					$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $paid_amount, $paid_amount_usd, $payment_id, $transaction_id, $transaction_time);
				}
				
				$txt_from 			= array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{transaction_id}", "{transaction_time}");
				$productText 		= str_replace($txt_from, $txt_to, $productText);
			}
		}
		
	
		// Html code
		// ---------------------
	
		$tmp  = "<div class='gourlbox'".($languages_list?" style='min-width:".$box_width."px'":"").">";
		if ($adminIntro) 	$tmp .= $adminIntro;
		if ($productTitle) 	$tmp .= "<h1>".htmlspecialchars($productTitle, ENT_QUOTES)."</h1>";
		if ($productText) 	$tmp .= "<div class='gourlproducttext'>".$productText."</div><br />";
	
		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
	
		if ($is_paid) 			$tmp .= "<br /><br />";
		elseif (!$coins_list) 	$tmp .= "<br />";
		else 					$tmp .= $coins_list;
	
		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;
	
		// End
		$tmp .= "</div>";
	
		return $tmp;
	}
	
	
	
	
	
	
	
	
	
	
	/**************** F. ALL PAYMENTS ************************************/
	
	
	/*
	 *  46.
	*/
	public function page_payments()
	{
		global $wpdb;
		
		$search = $sql_where = "";
		
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = mb_strtolower(trim($_GET["s"]));
			foreach ($this->addon as $v)
			{
				if 	($s == $v) $search = " && orderID like '".esc_sql($v).".%'";
				$sql_where .= " && orderID not like '".esc_sql($v).".%'";
			}
			if (!$search)
			{
				if 	   ($s == "recognised") 	$search = " && unrecognised = 0";
				elseif ($s == "unrecognised") 	$search = " && unrecognised != 0";
				elseif ($s == "products") 		$search = " && orderID LIKE 'product\_%'";
				elseif ($s == "files") 			$search = " && orderID LIKE 'file\_%'";
				elseif ($s == "membership") 	$search = " && orderID LIKE 'membership%'";
				elseif ($s == "payperview") 	$search = " && orderID = 'payperview'";
				elseif ($s == "guest") 			$search = " && userID NOT LIKE 'user%'";
				elseif ($s == "plugins") 		$search = " && orderID LIKE '%.%'".$sql_where;
				elseif (strpos($s, "user_") === 0 && is_numeric(substr($s, 5))) $search = " && (userID = 'user".intval(substr($s, 5))."' || userID = 'user_".intval(substr($s, 5))."')";
				elseif (strpos($s, "user") === 0 && is_numeric(substr($s, 4)))  $search = " && (userID = 'user".intval(substr($s, 4))."' || userID = 'user_".intval(substr($s, 4))."')";
				elseif (strpos($s, "file_") === 0 && is_numeric(substr($s, 5))) $search = " && orderID = 'file_".intval(substr($s, 5))."'";
				elseif (strpos($s, "payment_") === 0 && is_numeric(substr($s, 8))) $search = " && paymentID = ".intval(substr($s, 8));
				elseif (strpos($s, "order ") === 0 && is_numeric(substr($s, 6))) $search = " && orderID like '%".esc_sql(str_replace("order ", "", $s))."%'";
				elseif (in_array(strtolower($s), $this->coin_names)) $search = " && coinLabel = '".array_search(strtolower($s), $this->coin_names)."'";
				elseif (isset($this->coin_names[strtoupper($s)])) $search = " && coinLabel = '".strtoupper($s)."'";
			}	
				
			$s = trim($_GET["s"]);
			if (!$search)
			{
				include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
				
				$key = get_country_name($s, true); 
				if ($key) $s = $key;
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
				elseif (strtolower($s) == "wp ecommerce") $s = "wpecommerce";
				$s = esc_sql($s);
				$search = " && (orderID LIKE '%".$s."%' || userID LIKE '%".$s."%' || countryID LIKE '%".$s."%' || coinLabel LIKE '%".$s."%' || amount LIKE '%".$s."%' || amountUSD LIKE '%".$s."%' || addr LIKE '%".$s."%' || txID LIKE '%".$s."%' || DATE_FORMAT(txDate, '%d %M %Y') LIKE '%".$s."%')";
			}
		}	

		$res = $wpdb->get_row("SELECT sum(amountUSD) as total from crypto_payments WHERE 1".$search, OBJECT);
		$total = $res->total; 
		$total = number_format($total, 2);
		if (strpos($total, ".")) $num = rtrim(rtrim($total, "0"), ".");
		
		$res = $wpdb->get_row("SELECT DATE_FORMAT(txDate, '%d %M %Y, %H:%i %p') as latest from crypto_payments WHERE 1".$search." ORDER BY txDate DESC LIMIT 1", OBJECT);
		$latest = ($res) ? $res->latest . " " . __('GMT', GOURL) : "";
		
		
		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised = 0".$search, OBJECT);
		$recognised = (int)$res->cnt; 
		
		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised != 0".$search, OBJECT);
		$unrecognised = (int)$res->cnt; 
		
		
		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Received Payments', GOURL));
		
		
		if (isset($_GET["b"]) && is_numeric($_GET["b"])) 
		{	
			$c = $this->check_payment_confirmation($_GET["b"]);
		
			echo  "<div class='".($c?"updated":GOURL."intro")." postbox'>";
			if ($c) echo  sprintf(__('Payment #%s Confirmed', GOURL), intval($_GET["b"]));
			else echo  sprintf(__('Payment #%s - <b>NOT confirmed yet</b>', GOURL), intval($_GET["b"]));
			echo "</div>";
		}
		
		
		$wp_list_table = new  gourl_table_payments($search, $this->options['rec_per_page'], $this->options['file_columns']);
		$wp_list_table->prepare_items();
		
		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';
		
		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<span><b>" . ($search?__('Found', GOURL):__('Total Received', GOURL)). ":</b> " . number_format($recognised+$unrecognised) . " " . __('payments', GOURL) . $this->space(1) . "</span> <span><small>( ";
		echo "<b>" . __('Recognised', GOURL). ":</b> " . ($search?number_format($recognised):"<a href='".GOURL_ADMIN.GOURL."payments&s=recognised'>".number_format($recognised)."</a>") . " " . __('payments', GOURL) . $this->space(1);
		echo "<b>" . __('Unrecognised', GOURL). ":</b> " . ($search?number_format($unrecognised):"<a href='".GOURL_ADMIN.GOURL."payments&s=unrecognised'>".number_format($unrecognised)."</a>") . " " . __('payments', GOURL) . " )</small></span>" . $this->space(4);
		echo "<span><b>" . __('Total Sum', GOURL). ":</b> " . $total . " " . __('USD', GOURL) . "</span>" . $this->space(4);
		echo "<span><b>" . __('Latest Payment', GOURL). ":</b> " . $latest . "</span>";
		if ($search) echo "<br /><a href='".GOURL_ADMIN.GOURL."payments'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";
		
		echo '<div class="'.GOURL.'widetable">';
		echo '<div style="min-width:1640px;width:100%;"'.(!$this->options['file_columns']?' class="'.GOURL.'nofilecolumn"':'').'>';
		
		$wp_list_table->display();
		
		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br /><br />';
		
		return true;
	}
	

	
	/*
	 *  47.
	*/
	private function check_payment_confirmation($paymentID)
	{
		global $wpdb;
		
		$res = $wpdb->get_row("SELECT * from crypto_payments WHERE paymentID = ".intval($paymentID), OBJECT);
		
		if (!$res) return false;
		if ($res->txConfirmed) return true;
		
		$public_key 	= $this->options[$this->coin_names[$res->coinLabel].'public_key'];
		$private_key 	= $this->options[$this->coin_names[$res->coinLabel].'private_key'];

		if (!$public_key || !$private_key) return false;
		
		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", $private_key);
		
		$options = array(
				"public_key"  => $public_key,
				"private_key" => $private_key,
				"orderID"     => $res->orderID,
				"userID"      => $res->userID,
				"amount"   	  => $res->amount,
				"period"      => "NO EXPIRY"
				);
		
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
		
		$box = new Cryptobox ($options);
		
		$box->is_paid();
		
		return $box->is_confirmed();
	}
	
	
	
	
	
	
	
	
	/**************** G. FRONT ************************************/
		

	
	/*
	 *  48.
	*/
	public function  front_init()
	{
		ob_start();
		
		return true;
	}
	
	
	
	
	/*
	 *  49.
	*/
	public function front_html($text)
	{
		global $post;
	
		$m = $v = false;
	
		if (isset($post->post_content))
		{
			if (has_shortcode($post->post_content, GOURL_TAG_MEMBERSHIP)) 	$m = true;
			elseif (has_shortcode($post->post_content, GOURL_TAG_VIEW)) 	$v = true;
		}
			
		if ($m || $v)
		{
			$img 	 = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$frame   = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$iwidth  = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$iheight = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			
			preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER );
			foreach ($matches as $v)
				if (GOURL_TAG_MEMBERSHIP === $v[2] || GOURL_TAG_VIEW === $v[2])
				{
					preg_match('/(img(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $img[$v[2]] = trim($match["4"]);
					
					preg_match('/(frame(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $frame[$v[2]] = trim($match["4"]);
						
					preg_match('/(w(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $iwidth[$v[2]] = trim($match["4"]);
					
					preg_match('/(h(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $iheight[$v[2]] = trim($match["4"]);
				}
					
				if ($m) 
				{
					$this->lock_type = GOURL_TAG_MEMBERSHIP;
					$this->shortcode_membership_init($img[GOURL_TAG_MEMBERSHIP], $frame[GOURL_TAG_MEMBERSHIP], $iwidth[GOURL_TAG_MEMBERSHIP], $iheight[GOURL_TAG_MEMBERSHIP]);
				}
				elseif ($v) 	
				{
					$this->lock_type = GOURL_TAG_VIEW;
					$this->shortcode_view_init($img[GOURL_TAG_VIEW], $frame[GOURL_TAG_VIEW], $iwidth[GOURL_TAG_VIEW], $iheight[GOURL_TAG_VIEW]);
				}
		}
	
		return $text;
	}
	
	
	
	
	
	/*
	 *  50.
	*/
	public function front_header()
	{
		echo '<script src="'.plugins_url('/js/cryptobox.min.js?ver='.GOURL_VERSION, __FILE__).'" type="text/javascript"></script>
			  <link rel="stylesheet" type="text/css" href="'.plugins_url('/css/style.front.css?ver='.GOURL_VERSION, __FILE__).'" media="all" />';
	
		return true;
	}
	
	
	
	
	
	/*
	 * 51.  
	*/
	private function login_form()
	{
		global $user;
		
		$err = "";
		$tmp = '<a id="info" name="info"></a>';
		
		if (isset($_POST[GOURL.'login_submit'])) 
		{
			$creds = array();
			$creds['user_login'] = $_POST['login_name'];
			$creds['user_password'] =  $_POST['login_password'];
			if (!$creds['user_login'] && !$creds['user_password']) $creds['user_login'] = "no";
			$creds['remember'] = true;
			$user = wp_signon( $creds, false );
			if ( is_wp_error($user) ) {
				$err = $user->get_error_message();
			}
			if ( !is_wp_error($user) ) {
				wp_redirect(site_url($_SERVER['REQUEST_URI']));
			}
		}
		
		$tmp .= 
			'<div id="gourllogin">
				<div class="login">
					<div class="app-title"><h3>'.__('Login').'</h3>'.$err.'</div>
					<form method="post" action="'.$_SERVER['REQUEST_URI'].'#info">
						<div class="login-form">
							<div class="control-group" align="center">
								<input type="text" class="login-field" value="" placeholder="'.__('username').'" name="login_name" id="login_name">
								<label class="login-field-icon fui-user" for="login_name"></label>
							</div>
							<div class="control-group" align="center">
								<input type="password" class="login-field" value="" placeholder="'.__('password').'" name="login_password" id="login_password">
								<label class="login-field-icon fui-lock" for="login_password"></label>
							</div>
							<input class="btn btn-primary btn-large btn-block" type="submit" name="'.GOURL.'login_submit" value="'.__('Log in').'" />
								<a class="login-link" href="'.wp_lostpassword_url(site_url($_SERVER['REQUEST_URI'])).'">'.__( 'Lost your password?' ).'</a>
								'.wp_register('<div class="reg-link">'.__('Free').' ', '</div>', false).'		
						</div>
					</form>
				</div>
			</div>';
	
		return $tmp;
	}
	
	
	
	
	
	

	/**************** I. ADMIN ************************************/

	
	
	
	/*
	 *  52.
	*/
	public function admin_init()
	{
		global $wpdb;
		
		ob_start();
	
		// Actions POST
	
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
	
				case GOURL.'save_download':
						
					$this->post_record();
					$this->check_download();
						
					if (!$this->record_errors)
					{
						$this->save_download();
	
						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'file&id='.$this->id.'&updated=true');
							die();
						}
					}
						
					break;

				case GOURL.'save_product':
					
					$this->post_record();
					$this->check_product();
				
					if (!$this->record_errors)
					{
						$this->save_product();
				
						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'product&id='.$this->id.'&updated=true');
							die();
						}
					}
					
					break;
							
				case GOURL.'save_view':
				
					$this->post_view();
					$this->check_view();
									
					if (!$this->record_errors)
					{
						$this->save_view();
						header('Location: '.GOURL_ADMIN.GOURL.'payperview&updated=true');
						die();
					}
				
					break;

				case GOURL.'save_membership':
				
					$this->post_membership();
					$this->check_membership();
						
					if (!$this->record_errors)
					{
						$this->save_membership();
						header('Location: '.GOURL_ADMIN.GOURL.'paypermembership&updated=true');
						die();
					}
				
					break;
					
				case GOURL.'save_membership_newuser':
				
					$this->post_record();
					$this->check_membership_newuser();
						
					if (!$this->record_errors)
					{
						$this->save_membership_newuser();
						header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
						die();
					}
					
					
					
				default:
						
					break;
			}
		}
		
		
		// Actions GET

		if (!isset($_POST['ak_action']) && strpos($this->page, GOURL) === 0)
		{
			switch($this->page)
			{
				case GOURL.'premiumuser_delete':
				
					if ($this->id) $wpdb->query("delete from crypto_membership where membID = ".$this->id." && paymentID = 0 limit 1");
				
					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();
						
					break;
						
				
				case GOURL.'premiumuser_enable':
						
					if ($this->id) $wpdb->query("update crypto_membership set disabled = 0 where membID = ".$this->id." limit 1");
				
					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();
				
					break;
				
						
				case GOURL.'premiumuser_disable':
				
					if ($this->id) $wpdb->query("update crypto_membership set disabled = 1 where membID = ".$this->id." limit 1");
				
					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();
				
					break;
			}
		}
		
	
		return true;
	}
	
	
	
	
	
	
	
	/*
	 *  53.
	*/
	public function admin_header()
	{
		global $current_user;
		
		// File Preview Downloads
		
		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
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
			  <link rel="stylesheet" type="text/css" href="'.plugins_url('/css/style.front.css?ver='.GOURL_VERSION, __FILE__).'" media="all" />
			  <link href="//fonts.googleapis.com/css?family=Tenor+Sans" rel="stylesheet" type="text/css">';
	
		return true;
	}
	
	

	
	/*
	 * 
	*/
	public function admin_footer_text()
	{
		return sprintf( __( 'If you like <strong>GoUrl Bitcoin/Altcoins Gateway</strong> please leave us a <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating on <a href="%1$s" target="_blank">WordPress.org</a>. A huge thank you from GoUrl  in advance!', GOURL ), 'https://wordpress.org/support/view/plugin-reviews/gourl-bitcoin-payment-gateway-paid-downloads-membership?filter=5#postform');
	}
	
	
	
	
	/*
	 *  54.
	*/
	public function admin_warning()
	{
		echo '<div class="updated"><p>'.sprintf(__('<strong>%s Plugin is almost ready to use!</strong> All you need to do is to <a style="text-decoration:underline" href="admin.php?page=%ssettings">update your plugin settings</a>', GOURL), GOURL_NAME, GOURL).'</p></div>';
	
		return true;
	}
	
	
	
	/*
	 *  55.
	*/
	public function admin_warning_reactivate()
	{
		echo '<div class="error"><p>'.sprintf(__('<strong>Please deactivate %s Plugin,<br>manually set folder %s permission to 0777 and activate it again.</strong><br><br>if you have already done so before, please create three folders below manually and set folder permissions to 0777:<br />- %sfiles/<br />- %simages/<br />- %slockimg/', GOURL), GOURL_NAME, GOURL_DIR2, GOURL_DIR2, GOURL_DIR2, GOURL_DIR2).'</p></div>';
	
		return true;
	}
	
	
	
	
	/*
	 *  56.
	*/
	public function admin_menu()
	{
		global $submenu;
		
		add_menu_page(
				__("GoUrl Bitcoin", GOURL)		
				, __('GoUrl Bitcoin', GOURL)
				, GOURL_PERMISSION
				, GOURL
				, array(&$this, 'page_summary'),
				plugins_url('/images/btc_icon.png', __FILE__),
				'21.777'
		);

		add_submenu_page(
		GOURL
		, __('&#149; Summary', GOURL)
		, __('&#149; Summary', GOURL)
		, GOURL_PERMISSION
		, GOURL
		, array(&$this, 'page_summary')
		);
		
		add_submenu_page(
		GOURL
		, __('&#149; All Payments', GOURL)
		, __('&#149; All Payments', GOURL)
		, GOURL_PERMISSION
		, GOURL."payments"
				, array(&$this, 'page_payments')
		);
		
		add_submenu_page(
		GOURL
		, __('&#149; Pay-Per-Product', GOURL)
		, __('&#149; Pay-Per-Product', GOURL)
		, GOURL_PERMISSION
		, GOURL."products"
				, array(&$this, 'page_products')
		);
		
		
		add_submenu_page(
		GOURL
		, $this->space(2).__('Add New Product', GOURL)
		, $this->space(2).__('Add New Product', GOURL)
		, GOURL_PERMISSION
		, GOURL."product"
				, array(&$this, 'page_newproduct')
		);
		
		
		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-Download', GOURL)
				, __('&#149; Pay-Per-Download', GOURL)
				, GOURL_PERMISSION
				, GOURL."files"
				, array(&$this, 'page_files')
		);
		
		add_submenu_page(
		GOURL
				, $this->space(2).__('Add New File', GOURL)
				, $this->space(2).__('Add New File', GOURL)
				, GOURL_PERMISSION
				, GOURL."file"
				, array(&$this, 'page_newfile')
		);
		
		
		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-View', GOURL)
				, __('&#149; Pay-Per-View', GOURL)
				, GOURL_PERMISSION
				, GOURL."payperview"
				, array(&$this, 'page_view')
		);
	

		add_submenu_page(
		GOURL
		, __('&#149; Pay-Per-Membership', GOURL)
		, __('<span class="gourlnowrap">&#149; Pay-Per-Membership</span>', GOURL)
		, GOURL_PERMISSION
		, GOURL."paypermembership"
				, array(&$this, 'page_membership')
		);
		
		
		add_submenu_page(
		GOURL
		, $this->space(2).__('Premium Users', GOURL)
		, $this->space(2).__('Premium Users', GOURL)
		, GOURL_PERMISSION
		, GOURL."paypermembership_users"
				, array(&$this, 'page_membership_users')
		);

		add_submenu_page(
		GOURL
		, $this->space(2).__('________________', GOURL)
		, $this->space(2).__('________________', GOURL)
		, GOURL_PERMISSION
		, GOURL."paypermembership_user"
				, array(&$this, 'page_membership_user')
		);
		
		add_submenu_page(
		GOURL
				, __('Settings', GOURL)
				, __('Settings', GOURL)
				, GOURL_PERMISSION
				, GOURL."settings"
				, array(&$this, 'page_settings')
		);
		
		add_submenu_page(
		GOURL
				, __('Add-ons', GOURL)
				, __('Add-ons', GOURL)
				, GOURL_PERMISSION
				, GOURL."addons"
				, array(&$this, 'page_summary')
		);

		add_submenu_page(
		GOURL
				, __('Contacts', GOURL)
				, __('Contacts', GOURL)
				, GOURL_PERMISSION
				, GOURL."contact"
				, array(&$this, 'page_summary')
		);
		
		return true;
	}
	
		

	
	
	
	/**************** K. ADD-ON ************************************/
		

	
	
	
	/*
	 *  57.
	*/
	private function page_title($title, $type = 1) // 1 - Plugin Name, 2 - Pay-Per-Download,  3 - Pay-Per-View ,  4 - Pay-Per-Membership, 5 - Pay-Per-Product, 20 - Custom
	{
		if ($type == 2) 		$text = __("GoUrl Pay-Per-Download (Digital Paid Downloads)", GOURL);
		elseif ($type == 3) 	$text = __("GoUrl Premium Pay-Per-View (Paid Anonymous Page/Video Access)", GOURL);
		elseif ($type == 4) 	$text = __("GoUrl Premium Pay-Per-Membership", GOURL);
		elseif ($type == 5) 	$text = __("GoUrl Pay-Per-Product (Payment Boxes)", GOURL);
		else 					$text = __("GoUrl Official Bitcoin Payment Gateway for Wordpress", GOURL);
	
		$tmp = "<div class='".GOURL."logo'><a href='https://gourl.io/' target='_blank'><img title='".__('CRYPTO-CURRENCY PAYMENT GATEWAY', GOURL)."' src='".plugins_url('/images/gourl.png', __FILE__)."' border='0'></a></div>";
		if ($title) $tmp .= "<div id='icon-options-general' class='icon32'><br /></div><h2>".__(($text?$text.' - ':'').$title, GOURL)."</h2><br />";
		
		return $tmp;
	}
	
	
	
	/*
	 *  58.
	*/
	private function upload_file($file, $dir, $english = true)
	{
		$fileName 	= mb_strtolower($file["name"]);
		$ext 		= $this->right($fileName, ".", false);
		
		if ($fileName == $ext) $ext = "";
		$ext = trim($ext); 
		if (mb_strpos($ext, " ")) $ext = str_replace(" ", "_", $ext);
		
		if (!is_uploaded_file($file["tmp_name"])) $this->record_errors[] = sprintf(__('Cannot upload file "%s" on server. Alternatively, you can upload your file to "%s" using the FTP File Manager', GOURL), $file["name"], GOURL_DIR2.$dir);
		elseif (in_array($dir, array("images", "box")) && !in_array($ext, array("jpg", "jpeg", "png", "gif"))) $this->record_errors[] = sprintf(__('Invalid image file "%s", supported *.gif, *.jpg, *.png files only', GOURL), $file["name"]);
		else
		{
			if ($english) $fileName = preg_replace('/[^A-Za-z0-9\.\_\&]/', ' ', $fileName); // allowed english symbols only
			else $fileName = preg_replace('/[\(\)\?\!\;\,\>\<\'\"\%\&]/', ' ', $fileName);
			
			$fileName = trim(mb_strtolower(str_replace(" ", "_", preg_replace("{[ \t]+}", " ", trim($fileName)))), ".,!;_-");
			$fileName = str_replace("_.", ".", $fileName);
			$fileName = mb_substr($fileName, 0, 95);
			if (mb_strlen($fileName) < (mb_strlen($ext) + 3)) $fileName = date("Ymd")."_".strtotime("now").".".$ext;
			if (in_array($dir, array("images", "box")) && is_numeric($fileName[0])) $fileName = "i".$fileName;
				
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
	 *  59.
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
	 *  60.
	*/
	public function callback_parse_request( &$wp )
	{
		global $wp;
	
		if (in_array(strtolower($this->right($_SERVER["REQUEST_URI"], "/", false)), array("?cryptobox.callback.php", "index.php?cryptobox.callback.php")))
		{
			ob_clean();
			
			$cryptobox_private_keys = array();
			foreach($this->coin_names as $k => $v)
			{ 
				$val = get_option(GOURL.$v."private_key");
				if ($val) $cryptobox_private_keys[] = $val;
			}
			
			if ($cryptobox_private_keys) DEFINE("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.callback.php");
			
			ob_flush();
			
			die;
		}
	
		return true;
	}
	
	
	
	
	
	
	
		
	/********************************************************************/
	

	
	
	/*
	 *  61. Bitcoin Payments with Any Other Wordpress Plugins
	*/
	public function cryptopayments ($pluginName, $amount, $amountCurrency = "USD", $orderID, $period, $default_language = "en", $default_coin = "bitcoin", $affiliate_key = "", $userID = "auto", $icon_width = 60)
	{

		// Security Test
		// ---------------------
	
		if (!$pluginName) 																												return array("error" => __("Error. Please place in variable \$YourPluginName - your plugin name", GOURL));
		if (preg_replace('/[^a-z0-9\_\-]/', '', $pluginName) != $pluginName || strlen($pluginName) < 5 || strlen($pluginName) > 17) return array("error" => sprintf(__("Error. Invalid plugin name - %s. Size: 5-17 symbols. Allowed symbols: a..Z0..9_-", GOURL), $pluginName));
		if (stripos($pluginName, "product") === 0 || stripos($pluginName, "file") === 0 || stripos($pluginName, "pay") === 0 || stripos($pluginName, "membership") === 0 || stripos($pluginName, "user") === 0) return array("error" => __("Error. Please change plugin name. Plugin name can not begin with: 'file..', 'product..', 'pay..', 'membership..', 'user..'", GOURL));
		if (stripos($pluginName, "gourl") !== false && $pluginName != "gourlwoocommerce" && $affiliate_key != "gourl") return array("error" => __("Error. Please change plugin name. Plugin name can not use in name '..gourl..'", GOURL));
		$pluginName = strtolower(substr($pluginName, 0, 17));
		
		$amountCurrency = trim(strtoupper($amountCurrency));
		if ($amountCurrency == "USD" && (!is_numeric($amount) ||  $amount < 0.01 || $amount > 1000000))	return array("error" => sprintf(__("Error. Invalid amount value - %s. Min value for USD: 0.01", GOURL), $amount));
		if ($amountCurrency != "USD" && (!is_numeric($amount) ||  $amount < 0.0001 || $amount > 50000000))	return array("error" => sprintf(__("Error. Invalid amount value - %s. Min value: 0.0001", GOURL), $amount));
		if ($amountCurrency != "USD" && !isset($this->coin_names[$amountCurrency])) return array("error" => sprintf(__("Error. Invalid amountCurrency - %s. Allowed: USD, %s", GOURL), $amountCurrency, implode(", ", array_keys($this->coin_names))));

		if (!$orderID || preg_replace('/[^A-Za-z0-9\_\-]/', '', $orderID) != $orderID || strlen($orderID) > 32) return array("error" => sprintf(__("Error. Invalid Order ID - %s. Max size: 32 symbols. Allowed symbols: a..Z0..9_-", GOURL), $orderID));
		
		$period = trim(strtoupper(str_replace(" ", "", $period)));
		if (substr($period, -1) == "S") $period = substr($period, 0, -1);
		for ($i=1; $i<=90; $i++) { $arr[] = $i."MINUTE"; $arr[] = $i."HOUR"; $arr[] = $i."DAY"; $arr[] = $i."WEEK"; $arr[] = $i."MONTH"; }
		if ($period != "NOEXPIRY" && !in_array($period, $arr)) return array("error" => sprintf(__("Error. Invalid period value - %s. Allowed: NOEXPIRY, 1..90 HOUR, 1..90 DAY, 1..90 WEEK, 1..90 MONTH; example: 2 DAYS", GOURL), $period));
		$period = str_replace(array("MINUTE", "HOUR", "DAY", "WEEK", "MONTH"), array(" MINUTE", " HOUR", " DAY", " WEEK", " MONTH", GOURL), $period);
	
		if (!$default_language) $default_language = "en";
		if (!in_array($default_language, array_keys($this->languages))) return array("error" => sprintf(__("Error. Invalid language - %s. Allowed: ".implode(", ", array_keys($this->languages)), GOURL), $default_language));
		
		if (!$default_coin) $default_coin = "bitcoin";
		if (!in_array($default_coin, $this->coin_names)) return array("error" => sprintf(__("Error. Invalid Coin - %s. Allowed: %s", GOURL), $default_coin, implode(",", $this->coin_names)));

		if ($affiliate_key == "gourl") $affiliate_key = "";
		if ($affiliate_key && (strpos($affiliate_key, "DEV") !== 0 || preg_replace('/[^A-Za-z0-9]/', '', $affiliate_key) != $affiliate_key)) return array("error" => __("Error. Invalid affiliate_key, you can leave it empty", GOURL));
		
		if (!$userID || $userID == "auto") $userID = get_current_user_id();
		if ($userID && $userID != "guest" && (!is_numeric($userID) || preg_replace('/[^0-9]/', '', $userID) != $userID)) return array("error" => sprintf(__("Error. Invalid User ID - %s. Allowed numeric values or 'guest' value", GOURL), $userID));
		if (!$userID) return array("error" => __("Error. You need first to login or register on the website to make Bitcoin/Altcoin Payments", GOURL));
	
		if (!$this->payments) return array("error" => __("Error. Please try a different payment method. GoUrl Bitcoin Plugin not configured - need setup payment box keys on GoUrl Bitcoin Gateway Options page", GOURL));
		
		$icon_width = str_replace("px", "", $icon_width);
		if (!is_numeric($icon_width) || $icon_width < 30 || $icon_width > 250) $icon_width = 60;
		
		


		if ($amountCurrency == "USD") 	
		{	
			$amountUSD		= $amount;
			$amountCoin 	= 0;
			$default_show 	= false;
		}
		else
		{
			$amountUSD		= 0;
			$amountCoin 	= $amount;
			$default_coin 	= $this->coin_names[$amountCurrency];
			$default_show 	= true;
		}
		
		
		
	
		// GoUrl Payments
		// --------------------------
	
		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys
		
		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();
				
	
		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];
	
			if ($public_key && !strpos($public_key, "PUB"))    return array("error" => sprintf(__('Invalid %s Public Key - %s', GOURL), $v, $public_key));
			if ($private_key && !strpos($private_key, "PRV"))  return array("error" => sprintf(__('Invalid %s Private Key', GOURL), $v));
	
			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$default_show || $v == $default_coin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}
	
		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
	
		if (!$available_coins) return array("error" => sprintf(__("Error. Please enter Payment Private/Public Keys on GoUrl Options page for %s", GOURL), $default_coin));
	
		if (!in_array($default_coin, $available_coins)) { $vals = array_values($available_coins); $default_coin = array_shift($vals); }
	
	
	
		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
	
	
	
		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $default_coin);
	
	
		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];
	

		
		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 								// your box public key
				"private_key" => $private_key, 								// your box private key
				"webdev_key"  => $affiliate_key,							// your gourl.io affiliate key, optional
				"orderID"     => $pluginName.".".$orderID, 					// unique  order id
				"userID"      => ($userID == "guest" ? $pluginName.".".$userID : "user".$userID), // unique identifier for each your user
				"userFormat"  => "MANUAL", 									// save userID in
				"amount"   	  => $amountCoin,								// price in coins
				"amountUSD"   => $amountUSD,								// price in USD
				"period"      => $period, 									// payment valid period
				"language"	  => $default_language  						// text on EN - english, FR - french, etc
		);
	
	
		
		// Initialise Payment Class
		$box = new Cryptobox ($options);
	
	
		// Coin name
		$coinName = $box->coin_name();
	
	
		// Paid or not
		$is_paid = $box->is_paid();
	
		
		// page anchor
		$anchor = "go".$this->icrc32($pluginName.".".$orderID);
		
	
		// Coins selection list (html code)
		$coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $default_coin, $default_language, $icon_width, "margin:10px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";
	
	
		// Language selection list for payment box (html code)
		$languages_list = display_language_box($default_language, $anchor);
	
	
		// Payment Box
		$box_html = $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);
	
	
		$html = "";
		if (!$is_paid) $html .= "<a id='".$anchor."' name='".$anchor."'></a>";
	
		if ($is_paid) 			$html .= "<br />";
		else 					$html .= $coins_list;
	

		// Cryptocoin Payment Box
		if ($languages_list) 
		{
			$html .= "<table cellspacing='0' cellpadding='0' border='0' width='100%' style='border:0;box-shadow:none;margin:0;padding:0;background-color:transparent'>";
			$html .= "<tr style='background-color:transparent'><td style='border:0;margin:0;padding:0;background-color:transparent'><div style='margin:".($coins_list?25:50)."px 0 5px ".($this->options['box_width']/2-115)."px;min-width:100%;text-align:center;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(1).$languages_list."</div></td></tr>";
			$html .= "<tr style='background-color:transparent'><td style='border:0;margin:0;padding:0;background-color:transparent'>".$box_html."</td></tr>";
			$html .= "</table>";
		}
		else $html .= $box_html;
		
		
		// Result
		$obj = ($is_paid) ? $box->payment_info() : "";
		
		$arr = array   ("status"        	=> ($is_paid ? "payment_received" : "payment_not_received"),
						"error" 			=> "",
						"is_paid"			=> $is_paid,
				
						"paymentID"     	=> ($is_paid ? $obj->paymentID : 0),
						"paymentDate"		=> ($is_paid ? $obj->txDate : ""), // GMT
						"paymentLink"		=> ($is_paid ? GOURL_ADMIN.GOURL."payments&s=payment_".$obj->paymentID : ""), // page access for admin only
						"addr"       		=> ($is_paid ? $obj->addr : ""), 		// website admin cryptocoin wallet address
						"tx"            	=> ($is_paid ? $obj->txID : ""),			// transaction id, see also paymentDate
						"is_confirmed"     	=> ($is_paid ? $obj->txConfirmed : ""), 	// confirmed transaction or not, need wait 10+ min for confirmation

						"amount"			=> ($is_paid ? $obj->amount : ""), // paid coins amount (bitcoin, litecoin, etc)
						"amountusd"			=> $amountUSD,
						"coinlabel"			=> ($is_paid ? $obj->coinLabel : ""),
						"coinname"			=> ($is_paid ? strtolower($coinName) : ""),
				
						"boxID"     		=> ($is_paid ? $obj->boxID : 0),
						"boxtype"    		=> ($is_paid ? $obj->boxType : ""),
						"boxLink"    		=> ($is_paid ? "https://gourl.io/view/coin_boxes/".$obj->boxID."/statistics.html" : ""), // website owner have access only

						"orderID"       	=> $orderID,
						"userID"        	=> $userID,
						"usercountry"		=> ($is_paid ? $obj->countryID : ""),
						"userLink"        	=> ($userID=="guest"?"": admin_url("user-edit.php?user_id=".$userID)),
						
						"is_processed"		=> ($is_paid ? $obj->processed : ""),	// first time after payment received return TRUE, later return FALSE
						"processedDate"		=> ($is_paid && $obj->processed ? $obj->processedDate : ""),
						
						"callback_function"	=> $orderID."_gourlcallback", // information - your IPN callback function name 
						"available_payments"=> $this->payments, 				// information - activated payments on website (bitcoin, litecoin, etc)
						
						"html_payment_box"	=> $html // html payment box
				
						);
		
		if ($is_paid && !$obj->processed) $box->set_status_processed();
	
		return $arr;
	}
	
		
	
	/********************************************************************/
	
	
	
	
	/*
	 *  62.
	 */ 
	private function upgrade ()
	{
		global $wpdb;
	
		// TABLE 1 - crypto_files
		// ---------------------------
		if($wpdb->get_var("SHOW TABLES LIKE 'crypto_files'") != 'crypto_files')
		{
			$sql = "CREATE TABLE `crypto_files` (
			  `fileID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `fileTitle` varchar(100) NOT NULL DEFAULT '',
			  `active` tinyint(1) NOT NULL DEFAULT '1',
			  `fileName` varchar(100) NOT NULL DEFAULT '',
			  `fileSize` double(15,0) NOT NULL DEFAULT '0',
			  `fileText` text,
			  `priceUSD` double(10,2) NOT NULL DEFAULT '0.00',
			  `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000',
			  `priceLabel` varchar(6) NOT NULL DEFAULT '',
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
			  KEY `priceCoin` (`priceCoin`),
			  KEY `priceLabel` (`priceLabel`),
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
	
			$wpdb->query($sql);
		}
		// upgrade
		elseif ($wpdb->query("select priceCoin from crypto_files limit 1") === false)
		{
			$wpdb->query("alter table crypto_files add `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000' after priceUSD");
			$wpdb->query("alter table crypto_files add `priceLabel` varchar(6) NOT NULL DEFAULT '' after priceCoin");
			$wpdb->query("alter table crypto_files add key `priceCoin` (priceCoin)");
			$wpdb->query("alter table crypto_files add key `priceLabel` (priceLabel)");
		}
		elseif (true === version_compare(get_option(GOURL.'version'), '1.2.7', '<'))
		{
			$wpdb->query("ALTER TABLE `crypto_files` CHANGE `priceCoin` `priceCoin` DOUBLE(17,5) NOT NULL DEFAULT '0.00000'");
		}
		
	
		// TABLE 2 - crypto_payments
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_payments'") != 'crypto_payments')
		{
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
	
			$wpdb->query($sql);
		}
	
	
		// TABLE 3 - crypto_membership
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_membership'") != 'crypto_membership')
		{
			$sql = "CREATE TABLE `crypto_membership` (
			  `membID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `userID` bigint(20) unsigned NOT NULL DEFAULT '0',
			  `paymentID` int(11) unsigned NOT NULL DEFAULT '0',
			  `startDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `endDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `recordCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  PRIMARY KEY (`membID`),
			  KEY `userID` (`userID`),
			  KEY `paymentID` (`paymentID`),
			  KEY `startDate` (`startDate`),
			  KEY `endDate` (`endDate`),
			  KEY `disabled` (`disabled`),
			  KEY `recordCreated` (`recordCreated`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
			$wpdb->query($sql);
		}
	
	
		// TABLE 4 - crypto_products
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_products'") != 'crypto_products')
		{
			$sql = "CREATE TABLE `crypto_products` (
				  `productID` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `productTitle` varchar(100) NOT NULL DEFAULT '',
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  `priceUSD` double(10,2) NOT NULL DEFAULT '0.00',
				  `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000',
				  `priceLabel` varchar(6) NOT NULL DEFAULT '',
				  `purchases` mediumint(8) NOT NULL DEFAULT '0',
				  `expiryPeriod` varchar(15) NOT NULL DEFAULT '',
				  `lang` varchar(2) NOT NULL DEFAULT '',
				  `defCoin` varchar(5) NOT NULL DEFAULT '',
				  `defShow` tinyint(1) NOT NULL DEFAULT '1',
				  `productText` text,
				  `finalText` text,
				  `emailUser` tinyint(1) NOT NULL DEFAULT '1',
				  `emailUserFrom` varchar(50) NOT NULL DEFAULT '',
				  `emailUserTitle` varchar(100) NOT NULL DEFAULT '',
				  `emailUserBody` text,
				  `emailAdmin` tinyint(1) NOT NULL DEFAULT '1',
				  `emailAdminFrom` varchar(50) NOT NULL DEFAULT '',
				  `emailAdminTo` text,
				  `emailAdminTitle` varchar(100) NOT NULL DEFAULT '',
				  `emailAdminBody` text,
				  `paymentCnt` smallint(5) NOT NULL DEFAULT '0',
				  `paymentTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				  `updatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				  `createtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				  PRIMARY KEY (`productID`),
				  KEY `productTitle` (`productTitle`),
				  KEY `active` (`active`),
				  KEY `priceUSD` (`priceUSD`),
				  KEY `priceCoin` (`priceCoin`),
				  KEY `priceLabel` (`priceLabel`),
				  KEY `purchases` (`purchases`),
				  KEY `expiryPeriod` (`expiryPeriod`),
				  KEY `lang` (`lang`),
				  KEY `defCoin` (`defCoin`),
				  KEY `defShow` (`defShow`),
				  KEY `emailUser` (`emailUser`),
				  KEY `emailAdmin` (`emailAdmin`),
				  KEY `paymentCnt` (`paymentCnt`),
				  KEY `paymentTime` (`paymentTime`),
				  KEY `updatetime` (`updatetime`),
				  KEY `createtime` (`createtime`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
			$wpdb->query($sql);
		}
		elseif (true === version_compare(get_option(GOURL.'version'), '1.2.7', '<')) 
		{
			$wpdb->query("ALTER TABLE `crypto_products` CHANGE `priceCoin` `priceCoin` DOUBLE(17,5) NOT NULL DEFAULT '0.00000'");
		} 
		
	
		// current version
		update_option(GOURL.'version', GOURL_VERSION);
	
		// upload dir
		gourl_retest_dir();
	
		ob_flush();
	
		return true;
	}
	
	
	
	
	/*
	 *  63. Supported Functions
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
	public function left($str, $findme, $firstpos = true)
	{
		$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);
	
		if ($pos === false) return $str;
		else return mb_substr($str, 0, $pos);
	}
	public function right($str, $findme, $firstpos = true)
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








/*
 *  I. Uninstall Plugin
*/
function gourl_uninstall()
{
	update_option(GOURL.'version', '');
}




/*
 *  II.
*/
function gourl_retest_dir()
{
	
	$elevel = error_reporting();
	error_reporting(0);
	
	$dir = plugin_dir_path( __FILE__ )."images/dir/";
	
	if (!file_exists(GOURL_DIR."files")) wp_mkdir_p(GOURL_DIR."files");
	if (!file_exists(GOURL_DIR."files/.htaccess")) copy($dir."files/.htaccess", GOURL_DIR."files/.htaccess");
	if (!file_exists(GOURL_DIR."files/index.htm")) copy($dir."files/index.htm", GOURL_DIR."files/index.htm");
	if (!file_exists(GOURL_DIR."files/gourl_ipn.php") || filesize(GOURL_DIR."files/gourl_ipn.php") == "4104") { copy($dir."gourl_ipn.default.txt", GOURL_DIR."files/gourl_ipn.php"); chmod(GOURL_DIR."files/gourl_ipn.php", 0777); }
	
	if (!file_exists(GOURL_DIR."lockimg")) wp_mkdir_p(GOURL_DIR."lockimg");
	if (!file_exists(GOURL_DIR."lockimg/index.htm")) copy($dir."lockimg/index.htm", GOURL_DIR."lockimg/index.htm");
	if (!file_exists(GOURL_DIR."lockimg/image1.jpg")) copy($dir."lockimg/image1.jpg", GOURL_DIR."lockimg/image1.jpg");
	if (!file_exists(GOURL_DIR."lockimg/image1.png")) copy($dir."lockimg/image1.png", GOURL_DIR."lockimg/image1.png");
	if (!file_exists(GOURL_DIR."lockimg/image1b.png")) copy($dir."lockimg/image1b.png", GOURL_DIR."lockimg/image1b.png");
	if (!file_exists(GOURL_DIR."lockimg/image2.jpg")) copy($dir."lockimg/image2.jpg", GOURL_DIR."lockimg/image2.jpg");
	
	if (!file_exists(GOURL_DIR."box")) wp_mkdir_p(GOURL_DIR."box");
	
	if (!file_exists(GOURL_DIR."images"))
	{
		wp_mkdir_p(GOURL_DIR."images");
		
		$files = scandir($dir."images");
		foreach($files as $file)
			if (is_file($dir."images/".$file) && !in_array($file, array(".", "..")))
			copy($dir."images/".$file, GOURL_DIR."images/".$file);
	}
	
	error_reporting($elevel);

	return true;
}



/*
 *  III.
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

	$num = gourl_number_format($num, $precision);

	return $num.' '.$unit;
}



/*
 *  IV.
*/
function gourl_number_format ($num, $precision = 1)
{
	$num = number_format($num, $precision);
	if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");
	
	return $num;
}


/*
 *  V.
*/

function gourl_checked_image ($val)
{
	$val = ($val) ? "checked" : "unchecked";
	$tmp = "<img alt='".__(ucfirst($val), GOURL)."' src='".plugins_url('/images/'.$val.'.gif', __FILE__)."' border='0'>";
	return $tmp;
}


/*
 *  VI. User Details
*/
function gourl_userdetails($val, $br = true)
{
	$tmp = $val;
	
	if ($val)
	{
		if (strpos($val, "user_") === 0)    $userID = substr($val, 5);
		elseif (strpos($val, "user") === 0) $userID = substr($val, 4);
		else $userID = $val;	
		
		$userID = intval($userID);
		if ($userID)
		{	
			$obj =  get_userdata($userID);
			if ($obj && $obj->data->user_nicename) $tmp = "user".$userID." - <a href='".admin_url("user-edit.php?user_id=".$userID)."'>".$obj->data->user_nicename . ($br?"<br/>":", &#160; ") . $obj->data->user_email . "</a>";
			else $tmp = "user".$userID;
		}	
	}
	
	return $tmp;
}




/*
 *  VII. User Membership Edit Screen
*/
function gourl_edit_user_profile($user)
{
	global $wpdb;
	
	$tmp  = "";
	if ($user->ID)
	{

		$obj = $wpdb->get_results("SELECT txDate FROM crypto_payments WHERE userID = 'user".$user->ID."' || userID = 'user_".$user->ID."' ORDER BY txDate DESC LIMIT 1", OBJECT);
		
		$tmp .= "<table class='form-table'>";
		$tmp .= "<tr><th>".__('Bitcoin/altcoin Payments?', GOURL)."</th><td>";
		if ($obj) $tmp .= "<b><a href='".GOURL_ADMIN.GOURL."payments&s=user".$user->ID."'>".__('YES', GOURL)."</a></b> &#160; &#160; &#160; ".__('Latest payment', GOURL)." : &#160;" . date("d M Y, H:i A", strtotime($obj[0]->txDate)) . "&#160; ".__('GMT', GOURL);
		else $tmp .= "<b><a href='".GOURL_ADMIN.GOURL."payments&s=user".$user->ID."'>".__('NO', GOURL)."</a></b>";
		$tmp .= "</td></tr>";
		$tmp .= "</table>";
		
		if (get_option(GOURL."ppmProfile"))
		{
			$min = $max = "";
			$dt = gmdate('Y-m-d H:i:s');
			$obj = $wpdb->get_results("SELECT * FROM crypto_membership WHERE userID = ".$user->ID." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);
			
			if ($obj)
				foreach($obj as $row)
				{
					if (!$min || strtotime($row->startDate) < $min) $min = strtotime($row->startDate); 
					if (!$max || strtotime($row->endDate) > $max) $max = strtotime($row->endDate);
				}
	
			$tmp .= "<table class='form-table'>";
			$tmp .= "<tr><th>".__('Premium Membership', GOURL)."</th><td>";
			if ($obj) $tmp .= "<b><a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=user".$user->ID."'>".__('YES', GOURL)."</a></b> &#160; &#160; &#160; ".__('Period', GOURL)." : &#160; " .date("d M Y, H:i A", $min) . "&#160; - &#160;" . date("d M Y, H:i A", $max) . "&#160; ".__('GMT', GOURL);
			else $tmp .= "<b>".__('NO', GOURL)."</b>	 &#160; &#160; &#160; <a href='".GOURL_ADMIN.GOURL."paypermembership_user&userID=".$user->ID."'><small>".__('Manually Add Premium Membership', GOURL)."</small></a>";	
			$tmp .= "</td></tr>";
			$tmp .= "</table>";
		}
		
		echo $tmp;
	}
	
	return true;
}



/*
 *  VIII. User Profile Screen
*/
function gourl_show_user_profile($user)
{
	global $wpdb;

	$tmp  = "";
	if ($user->ID && get_option(GOURL."ppmProfile"))
	{

		$min = $max = "";
		$dt = gmdate('Y-m-d H:i:s');
		$obj = $wpdb->get_results("SELECT * FROM crypto_membership WHERE userID = ".$user->ID." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);
			
		if ($obj)
			foreach($obj as $row)
			{
				if (!$min || strtotime($row->startDate) < $min) $min = strtotime($row->startDate);
				if (!$max || strtotime($row->endDate) > $max) $max = strtotime($row->endDate);
			}

			$tmp .= "<table class='form-table'>";
			$tmp .= "<tr><th>".__('Premium Membership', GOURL)."</th><td>";
			if ($obj) $tmp .= "<b>".__('YES', GOURL)."</b> &#160; &#160; &#160; ".__('Period', GOURL)." : &#160; " . date("d M Y", $min) . "&#160; - &#160;" . date("d M Y", $max);
			else $tmp .= "<b>".__('NO', GOURL)."</b>";
			$tmp .= "</td></tr>";
			$tmp .= "</table>";

		echo $tmp;
	}

	return true;
}








/*
 *  IX. User-defined function for new payment
*/
function cryptobox_new_payment($paymentID, $arr, $box_status)
{
	$dt = gmdate('Y-m-d H:i:s');
	
	if (!isset($arr["status"]) || !in_array($arr["status"], array("payment_received", "payment_received_unrecognised")) || !in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) return false; 

	if ($box_status == "cryptobox_newrecord")
	{
	
		// Pay-Per-Download
		// ----------------------
		$fileID = ($arr["order"] && strpos($arr["order"], "file_") === 0) ? substr($arr["order"], 5) : 0;
		if ($fileID && is_numeric($fileID))
		{
			$sql = "UPDATE crypto_files SET paymentCnt = paymentCnt + 1, paymentTime = '".$dt."' WHERE fileID = '".$fileID."' LIMIT 1";
			run_sql($sql);
		}
		
		// Pay-Per-Product
		// ----------------------
		$productID = ($arr["order"] && strpos($arr["order"], "product_") === 0) ? substr($arr["order"], 8) : 0;
		if ($productID && is_numeric($productID))
		{
			$sql = "UPDATE crypto_products SET paymentCnt = paymentCnt + 1, paymentTime = '".$dt."' WHERE productID = '".$productID."' LIMIT 1";
			run_sql($sql);
			
			// Send email notifications
			gourl_email_notifications($productID, $paymentID, $arr, "product");
		}
		
		// Pay-Per-Membership
		// ----------------------
		if (strpos($arr["order"], "membership") === 0)
		{
			$userID = ($arr["user"] && strpos($arr["user"], "user_") === 0) ? intval(substr($arr["user"], 5)) : 0;
			
			$expiry = get_option(GOURL."ppmExpiry");
			if ($expiry == "NO EXPIRY") $endDate = "2030-01-01 00:00:00";
			else 
			{
				if (!$expiry) $expiry = "1 MONTH";
				$endDate = date('Y-m-d H:i:s', strtotime("+".$expiry." GMT"));
			}
			
			$sql = "INSERT INTO crypto_membership  (userID, paymentID, startDate, endDate, disabled, recordCreated)
											VALUES ($userID, $paymentID, '$dt', '$endDate', 0, '$dt')";
	
			run_sql($sql);
		}
	
	}
	
	
	
	// Custom Callback
	// ----------------------
	$func_callback = "";
	if (strpos($arr["user"], "user_") === 0) 	$user_id  = substr($arr["user"], 5);
	elseif (strpos($arr["user"], "user") === 0) $user_id  = substr($arr["user"], 4);
	else $user_id = $arr["user"];
	
	
	// A.Pay-Per-.. IPN notifications
	if (!strpos($arr["order"], ".") && 
		(strpos($arr["order"], "product_") === 0  || strpos($arr["order"], "file_") === 0 ||   
		strpos($arr["order"], "membership") === 0 || $arr["order"] == "payperview" || !$arr["order"]))
		{
			
			if (!defined('GOURL_IPN'))  DEFINE('GOURL_IPN', true);
			if (strpos($arr["order"], "membership") === 0) $arr["order"] = "membership";
			include_once(GOURL_DIR."files/gourl_ipn.php");
			
			$order_id		= $arr["order"];
			$func_callback 	= "gourl_successful_payment";
		}
	
	// B. Other Plugins IPN notifications
	if (strpos($arr["user"], "user_") !== 0 && strpos($arr["order"], "."))
	{
		$order_id 		= mb_substr($arr["order"], mb_strpos($arr["order"], ".") + 1);
		$func_callback 	= mb_substr($arr["order"], 0, mb_stripos($arr["order"], "."))."_gourlcallback";
		if (strpos($user_id, ".guest")) $user_id = "guest";
	}
	
	
	// Call IPN function
	if ($func_callback && function_exists($func_callback))
	{
		$payment_details = array (
						"status"        	=> $arr["status"],
						"error" 			=> $arr["err"],
						"is_paid"			=> 1,
							
						"paymentID"     	=> intval($paymentID),
						"paymentDate"		=> $arr["datetime"], 				// GMT
						"paymentLink"		=> GOURL_ADMIN.GOURL."payments&s=payment_".$paymentID,
						"addr"       		=> $arr["addr"], 					// website admin cryptocoin wallet address
						"tx"            	=> $arr["tx"],						// transaction id, see also paymentDate
						"is_confirmed"     	=> intval($arr["confirmed"]), 		// confirmed transaction or not, need wait 10+ min for confirmation
							
						"amount"			=> $arr["amount"], 					// paid coins amount (bitcoin, litecoin, etc)
						"amountusd"			=> $arr["amountusd"],
						"coinlabel"			=> $arr["coinlabel"],
						"coinname"			=> strtolower($arr["coinname"]),
							
						"boxID"     		=> $arr["box"],
						"boxtype"    		=> $arr["boxtype"],
						"boxLink"    		=> "https://gourl.io/view/coin_boxes/".$arr["box"]."/statistics.html", // website owner have access only
							
						"orderID"       	=> $order_id,
						"userID"        	=> $user_id,
						"usercountry"		=> $arr["usercountry"],
						"userLink"        	=> (strpos($arr["user"], "user")===0 ? admin_url("user-edit.php?user_id=".$user_id) : "")
				);
	
		$func_callback($user_id, $order_id, $payment_details, $box_status);
	}
	
	
	
	return true;
}




	
/*
 *  X.
*/
function gourl_lock_filter($content)
{

	$content = mb_substr($content, mb_strpos($content, GOURL_LOCK_START));
	$content = mb_substr($content, 0, mb_strpos($content, GOURL_LOCK_END));

	return $content;
}



/*
 *  XI.
*/
function gourl_lock_comments($content)
{
	$content = "<br/>* * * * * * * * * * * * * * * * * * * * * * *<br/> * * * * * * * * * * * * * * * * * * * * * * *";

	return $content;
}



/*
 *  XII. Content Restriction
*/

function gourl_hide_all_titles($title)
{
	$title = (in_the_loop()) ? "" : "* * * * * * * * &#160; * * * * * *";

	return $title;
}

function gourl_hide_menu_titles($title)
{
	if (!in_the_loop()) $title = "* * * * * * * * &#160; * * * * * *";

	return $title;
}
function gourl_hide_page_title($title)
{
	if (in_the_loop()) $title = "";

	return $title;
}

function gourl_hide_headtitle($title)
{
	return get_bloginfo('name');
}

function gourl_hide_headtitle_unlogged($title)
{
	return __("Please Login", GOURL) . " | " . get_bloginfo('name');
}



/*
 *  XIII.
*/
function gourl_return_false()
{

	return false;
}



/*
 *  XIV.
*/
function gourl_disable_feed() 
{
	wp_die(sprintf(__('<h1>Feed not available, please visit our <a href="%s">Home Page</a> !</h1>'), get_bloginfo('url')));
}


/*
 *  XV.
*/

function gourl_email_notifications($productID, $paymentID, $details, $type)
{
	global $wpdb;
	
	$payment_id 		= $paymentID;
	$transaction_id 	= $details["tx"];
	$transaction_time 	= date("d M Y, H:i:s a", $details["timestamp"]);
	$payment_url		= GOURL_ADMIN.GOURL."payments&s=payment_".$paymentID; // visible for admin only
	$payment_url		= "<a href='".$payment_url."'>".$payment_url."</a>";
	$paid_amount 		= gourl_number_format($details["amount"], 8) . " " . $details["coinlabel"];
	$paid_amount_usd 	= "~".gourl_number_format($details["amountusd"], 2) . " USD";
	
	$user_id 			= 0;
	$user_fullname 		= "User";
	$user_username 		= "";
	$user_email 		= "";
	$user_url 			= "";
	
	if (!$productID || !$paymentID || !$transaction_id || !$type) return false;

	
	$coin_chain     	= gourlclass::coin_chain();
	if ($transaction_id && isset($coin_chain[$details["coinname"]])) $transaction_id = "<a href='".$coin_chain[$details["coinname"]].(stripos($coin_chain[$details["coinname"]],'cryptoid.info')?'tx.dws?':'tx/').$transaction_id."' target='_blank'>".$transaction_id."</a>"; 
	
	$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $user_url, $paid_amount, $paid_amount_usd, $payment_id, $payment_url, $transaction_id, $transaction_time);
	$txt_from 			= array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{user_url}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{payment_url}", "{transaction_id}", "{transaction_time}");
	
	
	if ($type == "product")
	{
		$res = $wpdb->get_row("SELECT * from crypto_products where productID =".intval($productID), OBJECT);
		if ($res)
		{
			$user_id = 0;
			if (strpos($details["user"], "user_") === 0 && is_numeric(substr($details["user"], 5))) 	$user_id = intval(substr($details["user"], 5));
			elseif (strpos($details["user"], "user") === 0 && is_numeric(substr($details["user"], 4))) 	$user_id = intval(substr($details["user"], 4));
				
			// send email to user
			if ($user_id)
			{ 
				$user_info = get_userdata($user_id);
				if ($user_info)
				{
					$user_fullname  = trim($user_info->first_name." ".$user_info->last_name);
					$user_username 	= $user_info->user_login;
					$user_email 	= $user_info->user_email;
					$user_url 		= admin_url("user-edit.php?user_id=".$user_id);
					$user_url 		= "<a href='".$user_url."'>".$user_url."</a>";
					
					if (!$user_fullname) $user_fullname =  $user_username;
					
					$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $user_url, $paid_amount, $paid_amount_usd, $payment_id, $payment_url, $transaction_id, $transaction_time);
					$emailUserFrom	= $res->emailUserFrom;
					$emailToUser 	= $user_email;
					$emailUserTitle = htmlspecialchars($res->emailUserTitle, ENT_NOQUOTES);
					$emailUserTitle = (mb_strpos($emailUserTitle, "{")=== false) ? $emailUserTitle : str_replace($txt_from, $txt_to, $emailUserTitle);
					$emailUserBody 	= htmlspecialchars($res->emailUserBody, ENT_QUOTES);
					$emailUserBody 	= (mb_strpos($emailUserBody, "{")=== false) ? $emailUserBody : str_replace($txt_from, $txt_to, $emailUserBody);
					
					$headers	= array();
					$headers[] 	= 'From: '.$emailUserFrom.' <'.$emailUserFrom.'>';
					$headers[] 	= 'Content-type: text/html';
					if ($res->emailUser) wp_mail($emailToUser, $emailUserTitle, nl2br($emailUserBody), $headers);
				}
			}
		

			// send email to seller/admin
			$emailAdminFrom	 = $res->emailAdminFrom;
			$emailToAdmin 	 = trim($res->emailAdminTo);
			$emailAdminTitle = htmlspecialchars($res->emailAdminTitle, ENT_NOQUOTES);
			$emailAdminTitle = (mb_strpos($emailAdminTitle, "{")=== false) ? $emailAdminTitle : str_replace($txt_from, $txt_to,  $emailAdminTitle);
			$emailAdminBody  = htmlspecialchars($res->emailAdminBody, ENT_QUOTES);
			$emailAdminBody  = (mb_strpos($emailAdminBody, "{")=== false) ? $emailAdminBody : str_replace($txt_from, $txt_to,  $emailAdminBody);
			
			$headers	= array();
			$headers[] 	= 'From: '.$emailAdminFrom.' <'.$emailAdminFrom.'>';
			$headers[] 	= 'Content-type: text/html';
			
			$emails = explode("\n", $emailToAdmin); 
			if (count($emails) > 1)
			{
				$emailToAdmin 	= array_shift($emails);
				foreach($emails as $v) $headers[] = 'Cc: '.$v.' <'.$v.'>';
			}
			
			if ($res->emailAdmin) wp_mail($emailToAdmin, $emailAdminTitle, nl2br($emailAdminBody), $headers);
		}	
	} // end product	
	
	return true;
}




/*
 * XVI.
*/
function gourl_convert_currency($from_Currency, $to_Currency, $amount)
{
	if ($from_Currency == "TRL") $from_Currency = "TRY"; // fix for Turkish Lyra
	if ($from_Currency == "ZWD") $from_Currency = "ZWL"; // fix for Zimbabwe Dollar
	
	$amount = urlencode($amount);
	$from_Currency = urlencode($from_Currency);
	$to_Currency = urlencode($to_Currency);

	$url = "https://www.google.com/finance/converter?a=".$amount."&from=".$from_Currency."&to=".$to_Currency;

	$ch = curl_init();
	$timeout = 20;
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)");
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
	$rawdata = curl_exec($ch);
	curl_close($ch);
	$data = explode('bld>', $rawdata);
	$data = explode($to_Currency, $data[1]);

	return round($data[0], 2);
}



/********************************************************************/








// XVI. TABLE1 - "All Paid Files"  WP_Table Class
// ----------------------------------------

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class gourl_table_files extends WP_List_Table 
{
	private $coin_names = array();
	private $languages	= array();

	private $search 		= '';
	private $rec_per_page	= 20;
	
	function __construct($search = '', $rec_per_page = 20)
	{

		$this->coin_names 	= gourlclass::coin_names();
		$this->languages	= gourlclass::languages(); 
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
				$tmp = gourl_checked_image($item->$column_name);
				break;
				
			case 'fileSize':
				$tmp = gourl_byte_format($item->$column_name);
				break;
				
			case 'priceUSD':
				if ($item->$column_name > 0)
				{
					$num = gourl_number_format($item->$column_name, 2);
					$tmp = $num . ' ' . __('USD', GOURL);
				}
				break;
				
			case 'priceCoin':
				if ($item->$column_name > 0 && $item->priceUSD <= 0)
				{
					$num = gourl_number_format($item->$column_name, 4);
					$tmp = $num . ' ' . $item->priceLabel;
				}
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
					$tmp = "<a href='".GOURL_ADMIN.GOURL."files&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
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
				'priceUSD'  	=> __('Price USD', GOURL),
				'priceCoin'  	=> __('Price in Coins', GOURL),
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
				'userFormat'  	=> __('Store Visitor IDs', GOURL)
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
				'priceCoin'  	=> array('priceCoin', false),
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
// end class gourl_table_files









/********************************************************************/








// XVII. TABLE2 - "All Paid Products"  WP_Table Class
// ----------------------------------------

class gourl_table_products extends WP_List_Table
{
	private $coin_names = array();
	private $languages	= array();

	private $search 		= '';
	private $rec_per_page	= 20;
	
	function __construct($search = '', $rec_per_page = 20)
	{
		$this->coin_names 	= gourlclass::coin_names();
		$this->languages	= gourlclass::languages(); 
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
			case 'emailUser':
			case 'emailAdmin':
				$tmp = gourl_checked_image($item->$column_name);
				break;

			case 'priceUSD':
				if ($item->$column_name > 0)
				{
					$num = gourl_number_format($item->$column_name, 2);
					$tmp = $num . ' ' . __('USD', GOURL);
				}
				break;
				
			case 'priceCoin':
				if ($item->$column_name > 0 && $item->priceUSD <= 0)
				{
					$num = gourl_number_format($item->$column_name, 4);
					$tmp = $num . ' ' . $item->priceLabel;
				}
				break;
				
			case 'paymentCnt':
				$tmp = ($item->$column_name > 0) ? '<a href="'.GOURL_ADMIN.GOURL.'payments&s=product_'.$item->productID.'">'.$item->$column_name.'</a>' : '-';
				break;

			case 'defCoin':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".GOURL_ADMIN.GOURL."products&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;

			case 'lang':
				$tmp = $this->languages[$item->$column_name];
				break;

			case 'purchases':
				$tmp = ($item->$column_name == 0) ?  __('unlimited', GOURL) : $item->$column_name . ' ' . __('copies', GOURL);
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
				'productID'  	=> __('ID', GOURL),
				'active'  		=> __('Acti-ve?', GOURL),
				'productTitle' 	=> __('Title', GOURL),
				'priceUSD'  	=> __('Price in USD', GOURL),
				'priceCoin'  	=> __('Price in Coins', GOURL),
				'paymentCnt'  	=> __('Total Sold', GOURL),
				'paymentTime'  	=> __('Latest Received Payment, GMT', GOURL),
				'updatetime'  	=> __('Record Updated, GMT', GOURL),
				'createtime'  	=> __('Record Created, GMT', GOURL),
				'expiryPeriod'  => __('Payment Expiry Period', GOURL),
				'defCoin'  		=> __('Def Payment Box Coin', GOURL),
				'defShow'  		=> __('Def Coin only?', GOURL),
				'lang'  		=> __('Def Box Language', GOURL),
				'purchases'  	=> __('Purchase Limit', GOURL),
				'emailUser'  	=> __('Email to Buyer?', GOURL),
				'emailAdmin'  	=> __('Email to Seller?', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'productID'  		=> array('productID', false),
				'active'  		=> array('active', true),
				'productTitle' 	=> array('productTitle', false),
				'priceUSD'  	=> array('priceUSD', false),
				'priceCoin'  	=> array('priceCoin', false),
				'paymentCnt'  	=> array('paymentCnt', true),
				'paymentTime'  	=> array('paymentTime', true),
				'updatetime'  	=> array('updatetime', true),
				'createtime'  	=> array('createtime', true),
				'expiryPeriod'  => array('expiryPeriod', false),
				'defCoin'  		=> array('defCoin', false),
				'defShow'  		=> array('defShow', true),
				'lang'  		=> array('lang', false),
				'purchases'  	=> array('purchases', false),
				'emailUser'  	=> array('emailUser', true),
				'emailAdmin'  	=> array('emailAdmin', true)
		);

		return $sortable_columns;
	}


	function column_productTitle($item)
	{
		$actions = array(
				'edit'      => sprintf('<a href="'.GOURL_ADMIN.GOURL.'product&id='.$item->productID.'">'.__('Edit', GOURL).'</a>',$_REQUEST['page'],'edit',$item->productID),
				'delete'    => sprintf('<a href="'.GOURL_ADMIN.GOURL.'product&id='.$item->productID.'&gourlcryptocoin='.$this->coin_names[$item->defCoin].'&gourlcryptolang='.$item->lang.'&preview=true">'.__('Preview', GOURL).'</a>',$_REQUEST['page'],'preview',$item->productID),
		);
	
		return sprintf('%1$s %2$s', $item->productTitle, $this->row_actions($actions) );
	}


	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_products WHERE 1 ".$this->search;

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
// end class gourl_table_products











/********************************************************************/








// XVIII. TABLE3 - "All Payments"  WP_Table Class
// ----------------------------------------
class gourl_table_payments extends WP_List_Table
{
	private $coin_names = array();
	private $coin_chain = array();
	
	private $search 		= '';
	private $rec_per_page	= 20;
	private $file_columns 	= false;
	
	function __construct($search = '', $rec_per_page = 20, $file_columns = false)
	{

		$this->coin_names 	= gourlclass::coin_names();
		$this->coin_chain	= gourlclass::coin_chain();
		
		$this->search = $search;
		$this->file_columns = $file_columns;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;
		
		
		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false
				)
			);
		
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
		
	}

	function column_default( $item, $column_name )
	{
		global $gourl; 
		
		$tmp = "";
		switch( $column_name )
		{
			case 'unrecognised':
			case 'txConfirmed':
			case 'processed':
				if (!($column_name == "processed" && strpos($item->orderID, "file_") !== 0))
				{
					$title = "";
					if ($column_name=='processed') $title = "title='". (($item->$column_name) ? __('User already downloaded this file from your website ', GOURL) : __('User not downloaded this file yet', GOURL))."'";					
					$tmp = gourl_checked_image($item->$column_name);
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
					$url = "";
					if (strpos($item->$column_name, "product_") === 0) 			$url = GOURL_ADMIN.GOURL."product&id=".substr($item->$column_name, 8)."&gourlcryptocoin=".$this->coin_names[$item->coinLabel]."&preview=true";
					elseif (strpos($item->$column_name, "file_") === 0) 		$url = GOURL_ADMIN.GOURL."file&id=".substr($item->$column_name, 5)."&gourlcryptocoin=".$this->coin_names[$item->coinLabel]."&preview=true";
					elseif ($item->$column_name == "payperview") 				$url = GOURL_ADMIN.GOURL."payperview";
					elseif (strpos($item->$column_name, "membership") === 0)	$url = GOURL_ADMIN.GOURL."paypermembership";
					elseif (strpos($item->$column_name, "gourlwoocommerce") === 0) 	$item->$column_name = __('woocommerce', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".str_replace("gourlwoocommerce.order", "", $item->$column_name)."&action=edit")."'>".__('order', GOURL)." ".str_replace("gourlwoocommerce.order", "", $item->$column_name)."</a>"; 
					elseif (strpos($item->$column_name, "gourlwpecommerce") === 0) 	$item->$column_name = __('wp ecommerce', GOURL).", <a class='gourlnowrap' href='".admin_url("index.php?page=wpsc-purchase-logs&c=item_details&id=".str_replace("gourlwpecommerce.order", "", $item->$column_name)."&action=edit")."'>".__('order', GOURL)." ".str_replace("gourlwpecommerce.order", "", $item->$column_name)."</a>"; 
					elseif (strpos($item->$column_name, "gourljigoshop") === 0) 	$item->$column_name = __('jigoshop', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".$gourl->left($gourl->right($item->$column_name, ".order"), "_")."&action=edit")."'>".__('order', GOURL)." ".str_replace("_", " (", str_replace("gourljigoshop.order", "", $item->$column_name)).")"."</a>"; 
					elseif (strpos($item->$column_name, "gourlappthemes") === 0)
					{
						$escrow = (strpos($item->$column_name, "gourlappthemes.escrow") === 0) ? true : false;
						$item->$column_name = __('appthemes', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".str_replace(array( "gourlappthemes.order", "gourlappthemes.escrow"), array("", ""), $item->$column_name)."&action=edit")."'>".($escrow?__('escrow', GOURL):__('order', GOURL))." ".str_replace(array( "gourlappthemes.order", "gourlappthemes.escrow"), array("", ""), $item->$column_name)."</a>";
					} 
					elseif (strpos($item->$column_name, "gourlmarketpress") === 0) 	$item->$column_name = __('marketpress', GOURL).", <a class='gourlnowrap' href='".admin_url("edit.php?post_type=product&page=marketpress-orders&s=".str_replace("gourlmarketpress.", "", $item->$column_name))."'>".__('order', GOURL)." ".str_replace("gourlmarketpress.", "", $item->$column_name)."</a>"; 
					elseif (strpos($item->$column_name, "gourlpmpro") === 0) 		$item->$column_name = __('pmpro', GOURL).", <a class='gourlnowrap' href='".admin_url("admin.php?page=pmpro-orders&order=".$gourl->left($gourl->right($item->$column_name, ".order"), "_"))."'>".__('order', GOURL)." ".str_replace("gourlpmpro.order", "", $item->$column_name)."</a>"; 
					elseif (strpos($item->$column_name, "gourlgive") === 0) 		$item->$column_name = __('give', GOURL).", <a class='gourlnowrap' href='".admin_url("edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=".$gourl->left($gourl->right($item->$column_name, ".donation"), "_"))."'>".__('donation', GOURL)." ".str_replace("gourlgive.donation", "", $item->$column_name)."</a>"; 
					else	$item->$column_name = str_replace(".", ", ", str_replace("gourl", "", $item->$column_name)); 
					
					$tmp = ($url) ? "<a href='".$url."'>".$item->$column_name."</a>" : $item->$column_name; 
				}
				break;
				

			case 'userID':
				if ($item->$column_name)
				{
					$tmp = (strpos($item->$column_name, "user") === 0) ? gourl_userdetails($item->$column_name) : __('Guest', GOURL);
				}
				elseif ($item->unrecognised) $tmp = "? <small>".__('wrong paid amount', GOURL)."</small>";
				
				break;

				
			case 'amountUSD':
				$num = gourl_number_format($item->$column_name, 8);
				$tmp = $num . ' ' . __('USD', GOURL);
				break;
				
			
			case 'amount':
				$num = gourl_number_format($item->$column_name, 8);
				$tmp = $num . ' ' . $item->coinLabel;
				break;
			
			
			case 'coinLabel':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".GOURL_ADMIN.GOURL."payments&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;
			
			
			case 'countryID':
				if ($item->$column_name)
				{
					$tmp = "<a title='".__('Show Only Visitors from this Country', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=".$item->$column_name."'><img width='16' border='0' style='margin-right:7px' alt='".$item->$column_name."' src='".plugins_url('/images/flags/'.$item->$column_name.'.png', __FILE__)."' border='0'></a>" . get_country_name($item->$column_name);
				}
				break;
			
			
			case 'txID':
				if ($item->$column_name) $tmp = "<a title='".__('Transaction Details', GOURL)." - ".$item->$column_name."' href='".$this->coin_chain[$this->coin_names[$item->coinLabel]].(stripos($this->coin_chain[$this->coin_names[$item->coinLabel]],'cryptoid.info')?'tx.dws?':'tx/').$item->$column_name."' target='_blank'>".$item->$column_name."</a>";
				break;

			
			case 'addr':
				if ($item->$column_name) $tmp = "<a title='".__('Wallet Details', GOURL)." - ".$item->$column_name."' href='".$this->coin_chain[$this->coin_names[$item->coinLabel]].(stripos($this->coin_chain[$this->coin_names[$item->coinLabel]],'cryptoid.info')?'address.dws?':'address/').$item->$column_name."' target='_blank'>".$item->$column_name."</a>";
				break;
				
			
			case 'txDate':
			case 'txCheckDate':
			case 'recordCreated':
			case 'processedDate':
				if (!($column_name == "processedDate" && strpos($item->orderID, "file_") !== 0))
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
					'userID'			=> __('User ID', GOURL),
					'countryID'			=> __('User Country', GOURL),
					'txConfirmed'		=> __('Confirmed Payment?', GOURL),
					'txDate'			=> __('Payment Date, GMT', GOURL),
					'processed'			=> __('User Downl. File?', GOURL),
					'processedDate'		=> __('File Downloaded Time, GMT', GOURL),
					'txID'				=> __('Transaction ID', GOURL),
					'addr'				=> __('Your GoUrl Wallet Address', GOURL)
		);
		
		if (!$this->file_columns)
		{
			unset($columns['processed']);
			unset($columns['processedDate']);
		}
		
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



	function column_txConfirmed($item)
	{
		$tmp = gourl_checked_image($item->txConfirmed);
	
		if ($item->txConfirmed || !$item->userID) return $tmp;
	
		$actions = array(
				'edit' => sprintf('<a title="'.__('Re-check Payment Status', GOURL).'" href="'.GOURL_ADMIN.GOURL.'payments&b='.$item->paymentID.'">'.__('Check', GOURL).'</a>',$_REQUEST['page'],'edit',$item->paymentID)
		);
	
		return sprintf('%1$s %2$s', $tmp, $this->row_actions($actions) );
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
// end class gourl_table_payments





/********************************************************************/








// XVIV. TABLE4 - "All Premium Users"  WP_Table Class
// ----------------------------------------
class gourl_table_premiumusers extends WP_List_Table
{
	private $search 		= '';
	private $rec_per_page	= 20;
	
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
			case 'disabled':
				$tmp = gourl_checked_image($item->$column_name);
				break;

			case 'userID':
				if ($item->$column_name)
				{
					$tmp = (strpos($item->$column_name, "user") === 0) ? gourl_userdetails($item->$column_name, false) : __('Guest', GOURL);
				}
				elseif ($item->unrecognised) $tmp = "? <small>".__('wrong paid amount', GOURL)."</small>";
				
				break;
								
			case 'paymentID':
				if ($item->$column_name)
				{
					$tmp = "<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$item->$column_name."'>".$item->$column_name."</a>";
				}
				else $tmp = __('manually', GOURL);
				break;

			case 'startDate':
			case 'endDate':
			case 'recordCreated':
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
				'membID'  		=> __('ID', GOURL),
				'userID'  		=> __('User', GOURL),
				'paymentID'  	=> __('Payment ID', GOURL),
				'startDate' 	=> __('Premium Membership Start, GMT', GOURL),
				'endDate'  		=> __('Premium Membership End, GMT', GOURL),
				'disabled'  	=> __('Premium Memb. Disabled?', GOURL),
				'recordCreated'	=> __('Record Created, GMT', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'membID'  		=> array('membID', false),
				'userID'  		=> array('userID', false),
				'paymentID'  	=> array('paymentID', false),
				'startDate' 	=> array('startDate', true),
				'endDate'  		=> array('endDate', true),
				'disabled'  	=> array('disabled', false),
				'recordCreated' => array('recordCreated', true)
		);

		return $sortable_columns;
	}


	function column_userID($item)
	{
		$tmp = gourl_userdetails($item->userID, false);
		
		$enabled = ($item->disabled) ? false : true;
		
		$actions = array(
			'edit'  	=> '<a onclick="if (confirm(\''.($enabled?__('Are you sure you want to DISABLE Premium Membership?', GOURL):__('Are you sure you want to ENABLE Premium Membership?', GOURL)).'\')) location.href=\''.GOURL_ADMIN.GOURL.($enabled?'premiumuser_disable':'premiumuser_enable').'&id='.$item->membID.'\'; else return false;" href="#a">'.($enabled?__('Disable', GOURL):__('Enable', GOURL)).'</a>',
			'delete'	=> '<a onclick="if (confirm(\''.__('Are you sure you want to DELETE this record?', GOURL).'\')) location.href=\''.GOURL_ADMIN.GOURL.'premiumuser_delete&id='.$item->membID.'\'; else return false;" href="#a">'.__('Delete', GOURL).'</a>',
			'download'	=> '<a href="'.admin_url('user-edit.php?user_id='.$item->userID).'">'.__('Profile', GOURL).'</a>'
		);
		
		if ($item->paymentID > 0) unset($actions['delete']);

		return sprintf('%1$s %2$s', $tmp, $this->row_actions($actions) );
	}





	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_membership WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : '';
		if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY recordCreated DESC';


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
// end class gourl_table_premiumusers


/*
 *  XX.
*/
function gourl_action_links($links, $file)
{
	static $this_plugin;

	if (false === isset($this_plugin) || true === empty($this_plugin)) {
		$this_plugin = GOURL_BASENAME;
	}

	if ($file == $this_plugin) {
		$payments_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments').'">'.__( 'All Payments', GOURL ).'</a>';
		$unrecognised_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=unrecognised').'">'.__( 'Unrecognised', GOURL ).'</a>';
		$settings_link = '<a href="'.admin_url('admin.php?page='.GOURL).'">'.__( 'Summary', GOURL ).'</a>';
		array_unshift($links, $unrecognised_link);
		array_unshift($links, $payments_link);
		array_unshift($links, $settings_link);
	}
	return $links;
}



/*
 *  XXI. 
*/
if (!function_exists('has_shortcode') && version_compare(get_bloginfo('version'), "3.6") < 0)
{
	function has_shortcode( $content, $tag ) {
		if ( false === strpos( $content, '[' ) ) {
			return false;
		}
	
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) )
			return false;

		foreach ( $matches as $shortcode ) {
			if ( $tag === $shortcode[2] ) {
				return true;
			} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
				return true;
			}
		}
	
		return false;
	}
}
