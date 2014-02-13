<?php
/*
Plugin Name: Sell Downloads
Plugin URI: http://wordpress.dwbooster.com/content-tools/sell-downloads
Version: 1.0.1
Author: <a href="http://www.codepeople.net">CodePeople</a>
Description: Sell Downloads is an online store for selling downloadable files: audio, video, documents, pictures all that may be published in Internet. Sell Downloads uses PayPal as payment gateway, making the sales process easy and secure.
 */

 if(!function_exists('sd_get_site_url')){
    function sd_get_site_url(){
        $url_parts = parse_url(get_site_url());
        return rtrim( 
                        ((!empty($url_parts["scheme"])) ? $url_parts["scheme"] : "http")."://".
                        $_SERVER["HTTP_HOST"].
                        ((!empty($url_parts["path"])) ? $url_parts["path"] : ""),
                        "/"
                    )."/";
    }
 }

 
 // CONSTANTS
 define( 'SD_FILE_PATH', dirname( __FILE__ ) );
 define( 'SD_URL', plugins_url( '', __FILE__ ) );
 define( 'SD_H_URL', sd_get_site_url() );
 define( 'SD_DOWNLOAD', dirname( __FILE__ ).'/sd-downloads' );
 define( 'SD_OLD_DOWNLOAD_LINK', 3); // Number of days considered old download links
 define( 'SD_CORE_IMAGES_URL',  SD_URL . '/sd-core/images' );
 define( 'SD_CORE_IMAGES_PATH', SD_FILE_PATH . '/sd-core/images' );
 define( 'SD_TEXT_DOMAIN', 'SD_TEXT_DOMAIN' );
 define( 'SD_MAIN_PAGE', false ); // The location to the sell downloads main page
 
 // PAYPAL CONSTANTS
 define( 'SD_PAYPAL_EMAIL', '' );
 define( 'SD_PAYPAL_ENABLED', true );
 define( 'SD_PAYPAL_CURRENCY', 'USD' );
 define( 'SD_PAYPAL_CURRENCY_SYMBOL', '$' );
 define( 'SD_PAYPAL_LANGUAGE', 'en_US' );
 define( 'SD_PAYPAL_BUTTON', 'button_d.gif' );
 define( 'SD_PAYPAL_ADD_CART_BUTTON', 'shopping_cart/button_e.gif' );
 define( 'SD_PAYPAL_VIEW_CART_BUTTON', 'shopping_cart/button_f.gif' );
 
 // NOTIFICATION CONSTANTS
 define( 'SD_NOTIFICATION_FROM_EMAIL', 'put_your@email_here.com' );
 define( 'SD_NOTIFICATION_TO_EMAIL', 'put_your@email_here.com' );
 define( 'SD_NOTIFICATION_TO_PAYER_SUBJECT', 'Thank you for your purchase...' );
 define( 'SD_NOTIFICATION_TO_SELLER_SUBJECT','New product purchased...' ); 
 define( 'SD_NOTIFICATION_TO_PAYER_MESSAGE', "We have received your purchase notification with the following information:\n\n%INFORMATION%\n\nThank you.\n\nBest regards." ); 
 define( 'SD_NOTIFICATION_TO_SELLER_MESSAGE', "New purchase made with the following information:\n\n%INFORMATION%\n\nBest regards." );

 // DISPLAY CONSTANTS
 define('SD_ITEMS_PAGE', 10);
 define('SD_ITEMS_PAGE_SELECTOR', true);
 define('SD_FILTER_BY_TYPE', true);
 define('SD_ORDER_BY_POPULARITY', true);
 define('SD_ORDER_BY_PRICE', true);			
 define('SD_ONLINE_DEMO', false);
 define('SD_SAFE_DOWNLOAD', false);
 
 // TABLE NAMES
 define( 'SDDB_POST_DATA', 'msdb_post_data');
 define( 'SDDB_POST_COLLECTION', 'msdb_post_collection');
 define( 'SDDB_PURCHASE', 'msdb_purchase');
 define( 'SDDB_SHOPPING_CART', 'msdb_shopping_cart');
 
 include "sd-core/sd-functions.php";
 include "sd-core/sd-product.php";
 include "sd-core/tpleng.class.php";
 
 if ( !class_exists( 'SellDownloads' ) ) {
 	 /**
	 * Main SellDownloads Class
	 *
	 * Contains the main functions for Sell Downloads, stores variables, and handles error messages
	 *
	 * @class SellDownloads
	 * @version	1.0.1
	 * @since 1.4
	 * @package	SellDownloads
	 * @author CodePeople
	 */
		
	class SellDownloads{
		
		var $sell_downloads_slug = 'sell-downloads-menu';
		
		/**
		* SellDownloads constructor
		*
		* @access public
		* @return void	
		*/
		function __construct(){
			add_action('init', array(&$this, 'init'), 0);
			add_action('admin_init', array(&$this, 'admin_init'), 0);
			
			// Set the menu link
			add_action('admin_menu', array(&$this, 'menu_links'), 10);
		} // End __constructor

/** INITIALIZE PLUGIN FOR PUBLIC WORDPRESS AND ADMIN SECTION **/
		
		/**
		* Init SellDownloads when WordPress Initialize
		*
		* @access public
		* @return void
		*/
		function init(){
            global $wpdb;
			// I18n
			load_plugin_textdomain(SD_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
			
			$this->init_taxonomies(); // Init SellDownloads taxonomies
			$this->init_post_types(); // Init SellDownloads custom post types
			
			if ( ! is_admin()){
                add_filter('get_pages', array( &$this, '_sd_exclude_pages') ); // for download-page
                
				// Check parameter
                if(isset($_REQUEST['sd_action'])){
                    switch($_REQUEST['sd_action']){
                        case 'buynow':
                            include_once('sd-core/sd-submit.php');
                        break;
                        case 'ipn':
                            include_once('sd-core/sd-ipn.php');
                        break;
                        case 'demo':
                            if(isset($_REQUEST['file'])){
                                $f_url = $_REQUEST['file'];
                                $f_content = @file_get_contents($f_url);
                                if($f_content !== false){
                                    $f_name = substr($f_url, strrpos($f_url, '/')+1);
                                    header('Content-Disposition: attachment; filename="'.$f_name.'"');
                                    print $f_content;
                                }else{
                                    print '<script>document.location = "'.$f_url.'";</script>';
                                }    
                                exit;
                            }
                        break;
						case 'f-download':
							sd_download_file();
							exit;
						break;
                    }
                }
                
				// Set custom post_types on search result
				add_shortcode('sell_downloads', array(&$this, 'load_store'));
				add_filter( 'the_content', array( &$this, '_sd_the_content' ) );
				add_filter( 'the_excerpt', array( &$this, '_sd_the_excerpt' ) ); // For search results
                $this->load_templates(); // Load the sell downloads template for songs and collections display
				
				// Load public resources
				add_action( 'wp_enqueue_scripts', array(&$this, 'public_resources'), 10 );
			}
			// Init action
			do_action( 'selldownloads_init' );
		} // End init
		
        function _sd_create_pages( $slug, $title ){
            //if( session_id() == "" || !isset( $_SESSION ) ) session_start();
			if( isset( $_SESSION[ $slug ] ) ) return $_SESSION[ $slug ];
            
            $page = get_page_by_path( $slug ); 
			if( is_null( $page ) ){
				if( is_admin() ){
					if( false != ($id = wp_insert_post(
								array(
									'comment_status' => 'closed',
									'post_name' => $slug,
									'post_title' => __( $title, SD_TEXT_DOMAIN ),
									'post_status' => 'publish',
									'post_type' => 'page'
								)
							)
						)    
					){
						$_SESSION[ $slug ] =  get_permalink($id);
					}
				}    
			}else{
				if( is_admin() && $page->post_status != 'publish' )
				{
					$page->post_status = 'publish';
					wp_update_post( $page );
				}
				$_SESSION[ $slug ] =  get_permalink($page->ID);
			}	
			
            $_SESSION[ $slug ] = ( isset( $_SESSION[ $slug ] ) ) ? $_SESSION[ $slug ] : SD_H_URL;
            return $_SESSION[ $slug ];
        }
        
        function _sd_exclude_pages( $pages ){
            
            $exclude = array();
            $length = count( $pages );
            
            $p = get_page_by_path( 'sd-download-page' );
            if( !is_null( $p ) ) $exclude[] = $p->ID;
            
            for ( $i=0; $i<$length; $i++ ) {
                $page = & $pages[$i];
                
                if ( in_array( $page->ID, $exclude ) ) {
                    // Finally, delete something(s)
                    unset( $pages[$i] );
                }
            }
            
            return $pages;
        }
        
		function _sd_the_excerpt( $the_excerpt ){
			global $post;    
			if( is_search() && isset( $post) && $post->post_type == 'sd_product' ){
				$tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');
				$obj = new SDProduct( $post->ID );
				return $obj->display_content( 'multiple', $tpl, 'return');
			}
				
			return $the_excerpt;
		}
		
		
        function _sd_the_content( $the_content  ){
            global $post;    
            
            $slug = $post->post_name;

            if( $slug == "sd-download-page" ){
                $new_content = sd_generate_downloads( $the_content );
                if( $new_content != $the_content ) $the_content .= $new_content;
			}
            return $the_content;
        }    
		
		/**
		* Init SellDownloads when the WordPress is open for admin
		*
		* @access public
		* @return void
		*/
		function admin_init(){
			// Init the metaboxs for song and collection
			add_meta_box('sd_product_metabox', __("Product's data", SD_TEXT_DOMAIN), array(&$this, 'metabox_form'), 'sd_product', 'normal', 'high');
            add_meta_box('sd_product_metabox_discount', __("Programming Discounts", SD_TEXT_DOMAIN), array(&$this, 'metabox_discount'), 'sd_product', 'normal', 'high');
			add_action('save_post', array(&$this, 'save_data'));
			
			if (current_user_can('delete_posts')) add_action('delete_post', array(&$this, 'delete_post'));
			
			// Load admin resources
			add_action('admin_enqueue_scripts', array(&$this, 'admin_resources'), 10);
			
			// Set a new media button for sell downloads insertion
			add_action('media_buttons', array(&$this, 'set_sell_downloads_button'), 100);
			
			$plugin = plugin_basename(__FILE__);
			add_filter('plugin_action_links_'.$plugin, array(&$this, 'customizationLink'));
            $this->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); // for download-page
            
			if( isset( $_REQUEST[ 'sd_action' ] ) && $_REQUEST[ 'sd_action' ] == 'paypal-data' ){
				if( isset( $_REQUEST[ 'data' ] ) && isset( $_REQUEST[ 'from' ] ) && isset( $_REQUEST[ 'to' ] ) ){
					global $wpdb;
					$where = 'DATEDIFF(date, "'.$_REQUEST[ 'from' ].'")>=0 AND DATEDIFF(date, "'.$_REQUEST[ 'to' ].'")<=0';
					switch( $_REQUEST[ 'data' ] ){
						case 'residence_country':
							print sd_getFromPayPalData( array( 'residence_country' => 'residence_country'), 'COUNT(*) AS count', '', $where, array( 'residence_country' ), array( 'count' => 'DESC' ) );
						break;	
						case 'mc_currency':
							print sd_getFromPayPalData( array( 'mc_currency' => 'mc_currency'), 'SUM(amount) AS sum', '', $where, array( 'mc_currency' ), array( 'sum' => 'DESC' ) );
						break;	
						case 'product_name':
							$json =  sd_getFromPayPalData( array( 'mc_currency' => 'mc_currency'), 'SUM(amount) AS sum, post_title', $wpdb->posts.' AS posts', $where.' AND product_id = posts.ID', array( 'product_id', 'mc_currency' ) );
							$obj = json_decode( $json );
							foreach( $obj as $key => $value){
								$obj[ $key ]->post_title .= ' ['.$value->mc_currency.']';
							}
							print json_encode( $obj );
						break;
					}
				}
				exit;
			}
			
			// Init action
			do_action( 'selldownloads_admin_init' );
		} // End init
		
		function customizationLink($links){
			$settings_link = '<a href="http://wordpress.dwbooster.com/contact-us" target="_blank">'.__('Request custom changes').'</a>'; 
			array_unshift($links, $settings_link); 
			$settings_link = '<a href="admin.php?page=sell-downloads-menu-settings">'.__('Settings').'</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		} // End customizationLink
		
/** MANAGE DATABASES FOR ADITIONAL POST DATA **/
		
		/*
		*  Create database tables
		*
		*  @access public
		*  @return void
		*/
		function register($networkwide){
			global $wpdb;
			
			if (function_exists('is_multisite') && is_multisite()) {
				if ($networkwide) {
					$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						$this->_create_db_structure();
					}
					switch_to_blog($old_blog);
					return;
				}
			}
			$this->_create_db_structure();
            // Plugin options
            update_option('sd_social_buttons', true);
		}  // End register
		
		/*
		* Create the Sell Downloads tables
		*
		* @access private
		* @return void
		*/
		private function _create_db_structure(){
			global $wpdb;
			
            /* 
                The name of columns are treated as below to make table of Sell Downloads compatible with the tables of Sell Downloads and Sell Videos
                - id is the primary key, and the same value as the ID column of wp_posts table
                - time, may be used in video and audio files
                - plays, number of times the file has been visited
                - purchases, number of times the file has been purchase
                - file, location of file to purchase.
                - demo, location of demo file to downloaded for free
                - protect, (not used)
                - info, the URL of webpage with additional information of file
                - cover, location of image that represent the file
                - price, price of file
                - year, may be used for books, audio files, videos, etc.
                - as single, (not used)
            */
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.SDDB_POST_DATA." (
				id mediumint(9) NOT NULL,
				time VARCHAR(25) NULL,
				plays mediumint(9) NOT NULL DEFAULT 0,
				purchases mediumint(9) NOT NULL DEFAULT 0,
				file VARCHAR(255) NULL,
				demo VARCHAR(255) NULL,
				protect TINYINT(1) NOT NULL DEFAULT 0,
				info VARCHAR(255) NULL,
				cover VARCHAR(255) NULL,
				price FLOAT NULL,
				year VARCHAR(25),
				as_single TINYINT(1) NOT NULL DEFAULT 0,
				UNIQUE KEY id (id)
			 );";             
			$wpdb->query($sql); 
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.SDDB_PURCHASE." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) NOT NULL,
				purchase_id varchar(50) NOT NULL,
				date DATETIME NOT NULL,
				email VARCHAR(255) NOT NULL,
				amount FLOAT NOT NULL DEFAULT 0,
				paypal_data TEXT,
				UNIQUE KEY id (id)
			 );";             
			$wpdb->query($sql); 
            
            $sql = "ALTER TABLE ".$wpdb->prefix.SDDB_PURCHASE." DROP INDEX purchase_id";
			$wpdb->query($sql);
            
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.SDDB_SHOPPING_CART." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) NOT NULL,
				purchase_id varchar(50) NOT NULL,
				date DATETIME NOT NULL,
				PRIMARY KEY id (id),
				UNIQUE (purchase_id, product_id)
			 );";             
			$wpdb->query($sql); 
			
		} // End _create_db_structure 
		
/** REGISTER POST TYPES AND TAXONOMIES **/
		
		/**
		* Init SellDownloads post types
		*
		* @access public
		* @return void
		*/
		function init_post_types(){
            global $wpdb;
            if(post_type_exists('sd_product')) return;
			
			// Post Types
			// Create song post type
			register_post_type( 'sd_product', 
				array(
					'description'		   => __('This is where you can add products to your store.', SD_TEXT_DOMAIN),		
					'capability_type'      => 'post',
					'supports'             => array( 'title', 'editor' ),
					'exclude_from_search'  => false,
					'public'               => true,
					'show_ui'              => true,
					'show_in_nav_menus'    => true,
					'show_in_menu'    	   => $this->sell_downloads_slug,
					'labels'               => array(
						'name'               => __( 'Products', SD_TEXT_DOMAIN),
						'singular_name'      => __( 'Product', SD_TEXT_DOMAIN),
						'add_new'            => __( 'Add New', SD_TEXT_DOMAIN),
						'add_new_item'       => __( 'Add New Product', SD_TEXT_DOMAIN),
						'edit_item'          => __( 'Edit Product', SD_TEXT_DOMAIN),
						'new_item'           => __( 'New Product', SD_TEXT_DOMAIN),
						'view_item'          => __( 'View Product', SD_TEXT_DOMAIN),
						'search_items'       => __( 'Search Products', SD_TEXT_DOMAIN),
						'not_found'          => __( 'No products found', SD_TEXT_DOMAIN),
						'not_found_in_trash' => __( 'No products found in Trash', SD_TEXT_DOMAIN),
						'menu_name'          => __( 'Products for Sale', SD_TEXT_DOMAIN),
						'parent_item_colon'  => '',
					),
					'query_var'            => true,
					'has_archive'		   => true,	
					//'register_meta_box_cb' => 'wpsc_meta_boxes',
					'rewrite'              => false
				)
			);			
			
            register_post_type( 'sd_download', 
				array(
					'capability_type'      => 'page',
					'exclude_from_search'  => true,
					'public'               => true,
					'show_ui'              => false,
					'show_in_nav_menus'    => false,
					'show_in_menu'         => false,
					'query_var'            => true,
					'has_archive'		   => false,	
					'rewrite'              => false,
                    'supports'             => array('title', 'editor')
				)
			);
            
            if(!$wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts as posts WHERE post_type="sd_download"')){
                $my_post = array(
                  'post_title'    => 'download',
                  'post_type'     => 'sd_download',
                  'post_content'  => 'download',
                  'post_status'   => 'publish',
                  'post_author'   => 1
                );
                
                wp_insert_post($my_post);
            }
            
			add_filter('manage_sd_product_posts_columns' , 'SDProduct::columns');
			add_action('manage_sd_product_posts_custom_column', 'SDProduct::columns_data', 2 );
		}// End init_post_types
		
		/**
		* Init SellDownloads taxonomies
		*
		* @access public
		* @return void
		*/
		function init_taxonomies(){
			
			// Register artist taxonomy
			register_taxonomy(
				'sd_type',
				array(
					'sd_product'
				),
				array(
					'hierarchical'	=> false,
					'label' 	   	=> __('File Type', SD_TEXT_DOMAIN),
					'labels' 		=> array(
						'name' 				=> __( 'File Types', SD_TEXT_DOMAIN),
	                    'singular_name' 	=> __( 'File Type', SD_TEXT_DOMAIN),
						'search_items' 		=> __( 'Search File Types', SD_TEXT_DOMAIN),
	                    'all_items' 		=> __( 'All File Types', SD_TEXT_DOMAIN),
						'edit_item' 		=> __( 'Edit File Type', SD_TEXT_DOMAIN),
	                    'update_item' 		=> __( 'Update File Type', SD_TEXT_DOMAIN),
	                    'add_new_item' 		=> __( 'Add New File Type', SD_TEXT_DOMAIN),
						'new_item_name' 	=> __( 'New File Type', SD_TEXT_DOMAIN),
						'menu_name'			=> __( 'File Types', SD_TEXT_DOMAIN)
	                ),
					'public' => true,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true
				)
			);
			
			
			add_action( 'admin_menu' , array(&$this, 'remove_meta_box') );
			do_action( 'sell_downloads_register_taxonomy' );
		} // End init_taxonomies
		
		/**
		*	Remove the taxonomies metabox
		*
		* @access public
		* @return void
		*/
		function remove_meta_box(){
			remove_meta_box( 'tagsdiv-sd_type', 'sd_product', 'side' );
			remove_meta_box( 'tagsdiv-ms_album', 'sd_product', 'side' );
		} // End remove_meta_box

/** METABOXS FOR ENTERING POST_TYPE ADDITIONAL DATA **/		
		
		/**
		* Save data of store products
		*
		* @access public
		* @return void
		*/
		function save_data(){
			global $post;
            if(isset($post) && $post->post_type == 'sd_product')    SDProduct::save_data();
		} // End save_data
		
		/**
		* Print metabox for products
		*
		* @access public
		* @return void
		*/
		function metabox_form($obj){
			global $post;
            SDProduct::print_metabox();
		} // End metabox_form
        
		function metabox_discount($obj){
            SDProduct::print_discount_metabox();
		} // End metabox_form
        
/** SETTINGS PAGE FOR SELL DOWNLOADS CONFIGURATION AND SUBMENUS**/		
		
		// highlight the proper top level menu for taxonomies submenus
		function tax_menu_correction($parent_file) {
			global $current_screen;
			$taxonomy = $current_screen->taxonomy;
			if ($taxonomy == 'sd_type')
				$parent_file = $this->sell_downloads_slug;
			return $parent_file;
		} // End tax_menu_correction
		
		/*
		* Create the link for sell downloads menu, submenus and settings page
		*
		*/
		function menu_links(){
			if(is_admin()){
				add_options_page('Sell Downloads', 'Sell Downloads', 'manage_options', $this->sell_downloads_slug.'-settings1', array(&$this, 'settings_page'));
				
				add_menu_page('Sell Downloads', 'Sell Downloads', 'edit_pages', $this->sell_downloads_slug, null, SD_CORE_IMAGES_URL."/sell-downloads-menu-icon.png", 3.5);
				
				//Submenu for taxonomies
				add_submenu_page($this->sell_downloads_slug, __( 'File Types', SD_TEXT_DOMAIN), __( 'Set File Types', SD_TEXT_DOMAIN), 'edit_pages', 'edit-tags.php?taxonomy=sd_type');
				
				add_action('parent_file', array(&$this, 'tax_menu_correction'));
				
				// Settings Submenu
				add_submenu_page($this->sell_downloads_slug, 'Sell Downloads Settings', 'Sell Downloads Settings', 'edit_pages', $this->sell_downloads_slug.'-settings', array(&$this, 'settings_page'));
				
				// Sales report submenu
				add_submenu_page($this->sell_downloads_slug, 'Sell Downloads Sales Report', 'Sales Report', 'edit_pages', $this->sell_downloads_slug.'-reports', array(&$this, 'settings_page'));
				
			}	
		} // End menu_links
		
		/*
		*	Create tabs for setting page and payment stats
		*/
		function settings_tabs($current = 'reports'){
			$tabs = array( 'settings' => 'Sell Downloads Settings', 'product' => 'Sell Downloads Products','reports' => 'Sales Report');
			echo '<h2 class="nav-tab-wrapper">';
			foreach( $tabs as $tab => $name ){
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				if($tab == 'product')
					echo "<a class='nav-tab$class' href='edit.php?post_type=sd_$tab'>$name</a>";
				else
					echo "<a class='nav-tab$class' href='admin.php?page={$this->sell_downloads_slug}-$tab&tab=$tab'>$name</a>";

			}
			echo '</h2>';
		} // End settings_tabs 	
		
		/**
		* Get the list of possible paypal butt
		*/
		function _paypal_buttons(){
			$b = get_option('sd_paypal_button', SD_PAYPAL_BUTTON);
			$p = SD_FILE_PATH.'/paypal_buttons';
			$d = dir($p);
			$str = "";
			while (false !== ($entry = $d->read())) {
				if($entry != "." && $entry != ".." && is_file("$p/$entry"))
					$str .= "<input type='radio' name='sd_paypal_button' value='$entry' ".(($b == $entry) ? "checked" : "")." />&nbsp;<img src='".SD_URL."/paypal_buttons/$entry'/>&nbsp;&nbsp;";
			}
			$d->close();
			return $str;
		} // End _paypal_buttons
		
		/*
		* Set the sell downloads settings
		*/
		function settings_page(){
			global $wpdb;
			if ( isset($_POST['sd_settings']) && wp_verify_nonce( $_POST['sd_settings'], plugin_basename( __FILE__ ) ) ){
                update_option('sd_main_page', $_POST['sd_main_page']);
				update_option('sd_online_demo', ((isset($_POST['sd_online_demo'])) ? 1 : 0));
				update_option('sd_filter_by_type', ((isset($_POST['sd_filter_by_type'])) ? 1 : 0));
				update_option('sd_items_page_selector', ((isset($_POST['sd_items_page_selector'])) ? 1 : 0));
				update_option('sd_items_page', $_POST['sd_items_page']);
				update_option('sd_paypal_email', $_POST['sd_paypal_email']);
				update_option('sd_paypal_button', $_POST['sd_paypal_button']);
				update_option('sd_paypal_enabled', ((isset($_POST['sd_paypal_enabled'])) ? 1 : 0));
				update_option('sd_notification_from_email', $_POST['sd_notification_from_email']);
				update_option('sd_notification_to_email', $_POST['sd_notification_to_email']);
				update_option('sd_notification_to_payer_subject', $_POST['sd_notification_to_payer_subject']);
				update_option('sd_notification_to_payer_message', $_POST['sd_notification_to_payer_message']);
				update_option('sd_notification_to_seller_subject', $_POST['sd_notification_to_seller_subject']);
				update_option('sd_notification_to_seller_message', $_POST['sd_notification_to_seller_message']);
				update_option('sd_old_download_link', $_POST['sd_old_download_link']);				
                update_option('sd_social_buttons', ((isset($_POST['sd_social_buttons'])) ? true : false));
                update_option('sd_paypal_currency', $_POST['sd_paypal_currency']);
				update_option('sd_paypal_currency_symbol', $_POST['sd_paypal_currency_symbol']);
				update_option('sd_paypal_language', $_POST['sd_paypal_language']);
				update_option('sd_safe_download', ((isset($_POST['sd_safe_download'])) ? true : false));
				
?>				
				<div class="updated" style="margin:5px 0;"><strong><?php _e("Settings Updated", SD_TEXT_DOMAIN); ?></strong></div>
<?php				
			}
			
			$current_tab = (isset($_REQUEST['tab'])) ? $_REQUEST['tab'] : (($_REQUEST['page'] == 'sell-downloads-menu-reports') ? 'reports' : 'settings');
			
			$this->settings_tabs( 
				$current_tab
			);
?>
			<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
				For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a><br />
				If you want test the premium version of Sell Downloads go to the following links:<br/> <a href="http://demos.net-factor.com/sell-downloads/wp-login.php" target="_blank">Administration area: Click to access the administration area demo</a><br/> 
				<a href="http://demos.net-factor.com/sell-downloads/" target="_blank">Public page: Click to access the Store Page</a>
			</p>
<?php			
			switch($current_tab){
				case 'settings':
?>
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<input type="hidden" name="tab" value="settings" />
					<!-- STORE CONFIG -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Sell Downloads page config', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<table class="form-table">
								<tr valign="top">
									<th><?php _e('URL of store page', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="sd_main_page" size="40" value="<?php echo esc_attr(get_option('sd_main_page', SD_MAIN_PAGE)); ?>" />
										<br />
										<em><?php _e('Set the URL of page where the Sell Downloads was inserted', SD_TEXT_DOMAIN); ?></em>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow display online demos', SD_TEXT_DOMAIN); ?></th>
                                    
									<td><input type="checkbox" name="sd_online_demo" value="1" <?php if (get_option('sd_online_demo', SD_ONLINE_DEMO)) echo 'checked'; ?> /><br />
									<?php _e( 'The demo files will be displayed with plugins on browser, if they are enabled', SD_TEXT_DOMAIN); ?>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow to filter by type', SD_TEXT_DOMAIN); ?></th>
                                    
									<td><input type="checkbox" name="sd_filter_by_type" size="40" value="1" <?php if (get_option('sd_filter_by_type', SD_FILTER_BY_TYPE)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow multiple pages', SD_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="sd_items_page_selector" size="40" value="1" <?php if (get_option('sd_items_page_selector', SD_ITEMS_PAGE_SELECTOR)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Items per page', SD_TEXT_DOMAIN); ?></th>
									<td><input type="text" name="sd_items_page" value="<?php echo esc_attr(get_option('sd_items_page', SD_ITEMS_PAGE)); ?>" /></td>
								</tr>
                                <tr valign="top">
									<th><?php _e('Share in social networks', SD_TEXT_DOMAIN); ?></th>
									<td>
										<input type="checkbox" name="sd_social_buttons" <?php echo ((get_option('sd_social_buttons')) ? 'CHECKED' : ''); ?> /><br />
										<em><?php _e('The option enables the buttons for share the products in social networks', SD_TEXT_DOMAIN); ?></em>
										
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<!-- PAYPAL BOX -->
					<p class="sd_more_info" style="display:block;">The Sell Downloads uses PayPal only as payment gateway, but depending of your PayPal account, it is possible to charge the purchase directly from the Credit Cards of customers.</p>
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Paypal Payment Configuration', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">

						<table class="form-table">
							<tr valign="top">        
							<th scope="row"><?php _e('Enable Paypal Payments?', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="sd_paypal_enabled" size="40" value="1" <?php if (get_option('sd_paypal_enabled', SD_PAYPAL_ENABLED)) echo 'checked'; ?> /></td>
							</tr>    
						
							<tr valign="top">        
							<th scope="row"><?php _e('Paypal email', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_paypal_email" size="40" value="<?php echo esc_attr(get_option('sd_paypal_email', SD_PAYPAL_EMAIL)); ?>" />
                            <span class="sd_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="sd_display_more_info( this );">[ + more information]</a></span>
                            <div class="sd_more_info">
                                <p>If let empty the email associated to PayPal, the Sell Downloads assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
                                <a href="javascript:void(0)" onclick="sd_hide_more_info( this );">[ + less information]</a>
                            </div>
                            </td>
							</tr>
							 
							<tr valign="top">
							<th scope="row"><?php _e('Currency', SD_TEXT_DOMAIN); ?></th>
							<td>
                            <select name="sd_paypal_currency">
                            <?php
                                $currency_list = array( "USD","EUR","GBP","CAD","AUD","RUB","BRL","CZK","DKK","HKD","HUF","ILS","JPY","MYR","MXN","NOK","NZD","PHP","PLN","SGD","SEK","CHF","TWD","THB","TRY" );
                                $currency_selected = get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY);
                                foreach($currency_list as $currency_item)
                                    echo '<option value="'.$currency_item.'" '.(($currency_item == $currency_selected) ? 'SELECTED' : '').'>'.$currency_item.'</option>';
                            ?>
                            </select>
                            <span style="color:#FF0000;">Additional currencies are available in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank" >Press Here</a>
                            </td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Currency Symbol', SD_TEXT_DOMAIN); ?></th>
							<td>
                                <input type="text" name="sd_paypal_currency_symbol" value="<?php echo esc_attr(get_option('sd_paypal_currency_symbol', SD_PAYPAL_CURRENCY_SYMBOL)); ?>" />
                            </td>
							</tr>
							
							<tr valign="top">
                            <th scope="row"><?php _e('Paypal language', SD_TEXT_DOMAIN); ?></th>
							<td>
                            <?php
							$languages_list = array(
                                "en_AL" => "Albania - U.K. English",
                                "en_DZ" => "Algeria - U.K. English",
                                "en_AD" => "Andorra - U.K. English",
                                "en_AO" => "Angola - U.K. English",
                                "en_AI" => "Anguilla - U.K. English",
                                "en_AG" => "Antigua and Barbuda - U.K. English",
                                "en_AR" => "Argentina - U.K. English",
                                "en_AM" => "Armenia - U.K. English",
                                "en_AW" => "Aruba - U.K. English",
                                "en_AU" => "Australia - Australian English",
                                "de_AT" => "Austria - German",
                                "en_AT" => "Austria - U.S. English",
                                "en_AZ" => "Azerbaijan Republic - U.K. English",
                                "en_BS" => "Bahamas - U.K. English",
                                "en_BH" => "Bahrain - U.K. English",
                                "en_BB" => "Barbados - U.K. English",
                                "en_BE" => "Belgium - U.S. English",
                                "nl_BE" => "Belgium - Dutch",
                                "fr_BE" => "Belgium - French",
                                "en_BZ" => "Belize - U.K. English",
                                "en_BJ" => "Benin - U.K. English",
                                "en_BM" => "Bermuda - U.K. English",
                                "en_BT" => "Bhutan - U.K. English",
                                "en_BO" => "Bolivia - U.K. English",
                                "en_BA" => "Bosnia and Herzegovina - U.K. English",
                                "en_BW" => "Botswana - U.K. English",
                                "en_BR" => "Brazil - U.K. English",
                                "en_VG" => "British Virgin Islands - U.K. English",
                                "en_BN" => "Brunei - U.K. English",
                                "en_BG" => "Bulgaria - U.K. English",
                                "en_BF" => "Burkina Faso - U.K. English",
                                "en_BI" => "Burundi - U.K. English",
                                "en_KH" => "Cambodia - U.K. English",
                                "en_CA" => "Canada - U.S. English",
                                "fr_CA" => "Canada - French",
                                "en_CV" => "Cape Verde - U.K. English",
                                "en_KY" => "Cayman Islands - U.K. English",
                                "en_TD" => "Chad - U.K. English",
                                "en_CL" => "Chile - U.K. English",
                                "en_C2" => "China - U.S. English",
                                "zh_C2" => "China - Simplified Chinese",
                                "en_CO" => "Colombia - U.K. English",
                                "en_KM" => "Comoros - U.K. English",
                                "en_CK" => "Cook Islands - U.K. English",
                                "en_CR" => "Costa Rica - U.K. English",
                                "en_HR" => "Croatia - U.K. English",
                                "en_CY" => "Cyprus - U.K. English",
                                "en_CZ" => "Czech Republic - U.K. English",
                                "en_CD" => "Democratic Republic of the Congo - U.K. English",
                                "en_DK" => "Denmark - U.K. English",
                                "en_DJ" => "Djibouti - U.K. English",
                                "en_DM" => "Dominica - U.K. English",
                                "en_DO" => "Dominican Republic - U.K. English",
                                "en_EC" => "Ecuador - U.K. English",
                                "en_SV" => "El Salvador - U.K. English",
                                "en_ER" => "Eritrea - U.K. English",
                                "en_EE" => "Estonia - U.K. English",
                                "en_ET" => "Ethiopia - U.K. English",
                                "en_FK" => "Falkland Islands - U.K. English",
                                "en_FO" => "Faroe Islands - U.K. English",
                                "en_FM" => "Federated States of Micronesia - U.K. English",
                                "en_FJ" => "Fiji - U.K. English",
                                "en_FI" => "Finland - U.K. English",
                                "fr_FR" => "France - French",
                                "en_FR" => "France - U.S. English",
                                "en_GF" => "French Guiana - U.K. English",
                                "en_PF" => "French Polynesia - U.K. English",
                                "en_GA" => "Gabon Republic - U.K. English",
                                "en_GM" => "Gambia - U.K. English",
                                "de_DE" => "Germany - German",
                                "en_DE" => "Germany - U.S. English",
                                "en_GI" => "Gibraltar - U.K. English",
                                "en_GR" => "Greece - U.K. English",
                                "en_GL" => "Greenland - U.K. English",
                                "en_GD" => "Grenada - U.K. English",
                                "en_GP" => "Guadeloupe - U.K. English",
                                "en_GT" => "Guatemala - U.K. English",
                                "en_GN" => "Guinea - U.K. English",
                                "en_GW" => "Guinea Bissau - U.K. English",
                                "en_GY" => "Guyana - U.K. English",
                                "en_HN" => "Honduras - U.K. English",
                                "zh_HK" => "Hong Kong - Traditional Chinese",
                                "en_HK" => "Hong Kong - U.K. English",
                                "en_HU" => "Hungary - U.K. English",
                                "en_IS" => "Iceland - U.K. English",
                                "en_IN" => "India - U.K. English",
                                "en_ID" => "Indonesia - U.K. English",
                                "en_IE" => "Ireland - U.K. English",
                                "en_IL" => "Israel - U.K. English",
                                "it_IT" => "Italy - Italian",
                                "en_IT" => "Italy - U.S. English",
                                "en_JM" => "Jamaica - U.K. English",
                                "ja_JP" => "Japan - Japanese",
                                "en_JP" => "Japan - U.S. English",
                                "en_JO" => "Jordan - U.K. English",
                                "en_KZ" => "Kazakhstan - U.K. English",
                                "en_KE" => "Kenya - U.K. English",
                                "en_KI" => "Kiribati - U.K. English",
                                "en_KW" => "Kuwait - U.K. English",
                                "en_KG" => "Kyrgyzstan - U.K. English",
                                "en_LA" => "Laos - U.K. English",
                                "en_LV" => "Latvia - U.K. English",
                                "en_LS" => "Lesotho - U.K. English",
                                "en_LI" => "Liechtenstein - U.K. English",
                                "en_LT" => "Lithuania - U.K. English",
                                "en_LU" => "Luxembourg - U.K. English",
                                "en_MG" => "Madagascar - U.K. English",
                                "en_MW" => "Malawi - U.K. English",
                                "en_MY" => "Malaysia - U.K. English",
                                "en_MV" => "Maldives - U.K. English",
                                "en_ML" => "Mali - U.K. English",
                                "en_MT" => "Malta - U.K. English",
                                "en_MH" => "Marshall Islands - U.K. English",
                                "en_MQ" => "Martinique - U.K. English",
                                "en_MR" => "Mauritania - U.K. English",
                                "en_MU" => "Mauritius - U.K. English",
                                "en_YT" => "Mayotte - U.K. English",
                                "es_MX" => "Mexico - Spanish",
                                "en_MX" => "Mexico - U.S. English",
                                "en_MN" => "Mongolia - U.K. English",
                                "en_MS" => "Montserrat - U.K. English",
                                "en_MA" => "Morocco - U.K. English",
                                "en_MZ" => "Mozambique - U.K. English",
                                "en_NA" => "Namibia - U.K. English",
                                "en_NR" => "Nauru - U.K. English",
                                "en_NP" => "Nepal - U.K. English",
                                "nl_NL" => "Netherlands - Dutch",
                                "en_NL" => "Netherlands - U.S. English",
                                "en_AN" => "Netherlands Antilles - U.K. English",
                                "en_NC" => "New Caledonia - U.K. English",
                                "en_NZ" => "New Zealand - U.K. English",
                                "en_NI" => "Nicaragua - U.K. English",
                                "en_NE" => "Niger - U.K. English",
                                "en_NU" => "Niue - U.K. English",
                                "en_NF" => "Norfolk Island - U.K. English",
                                "en_NO" => "Norway - U.K. English",
                                "en_OM" => "Oman - U.K. English",
                                "en_PW" => "Palau - U.K. English",
                                "en_PA" => "Panama - U.K. English",
                                "en_PG" => "Papua New Guinea - U.K. English",
                                "en_PE" => "Peru - U.K. English",
                                "en_PH" => "Philippines - U.K. English",
                                "en_PN" => "Pitcairn Islands - U.K. English",
                                "pl_PL" => "Poland - Polish",
                                "en_PL" => "Poland - U.S. English",
                                "en_PT" => "Portugal - U.K. English",
                                "en_QA" => "Qatar - U.K. English",
                                "en_CG" => "Republic of the Congo - U.K. English",
                                "en_RE" => "Reunion - U.K. English",
                                "en_RO" => "Romania - U.K. English",
                                "en_RU" => "Russia - U.K. English",
                                "en_RW" => "Rwanda - U.K. English",
                                "en_VC" => "Saint Vincent and the Grenadines - U.K. English",
                                "en_WS" => "Samoa - U.K. English",
                                "en_SM" => "San Marino - U.K. English",
                                "en_ST" => "São Tomé and Príncipe - U.K. English",
                                "en_SA" => "Saudi Arabia - U.K. English",
                                "en_SN" => "Senegal - U.K. English",
                                "en_SC" => "Seychelles - U.K. English",
                                "en_SL" => "Sierra Leone - U.K. English",
                                "en_SG" => "Singapore - U.K. English",
                                "en_SK" => "Slovakia - U.K. English",
                                "en_SI" => "Slovenia - U.K. English",
                                "en_SB" => "Solomon Islands - U.K. English",
                                "en_SO" => "Somalia - U.K. English",
                                "en_ZA" => "South Africa - U.K. English",
                                "en_KR" => "South Korea - U.K. English",
                                "es_ES" => "Spain - Spanish",
                                "en_ES" => "Spain - U.S. English",
                                "en_LK" => "Sri Lanka - U.K. English",
                                "en_SH" => "St. Helena - U.K. English",
                                "en_KN" => "St. Kitts and Nevis - U.K. English",
                                "en_LC" => "St. Lucia - U.K. English",
                                "en_PM" => "St. Pierre and Miquelon - U.K. English",
                                "en_SR" => "Suriname - U.K. English",
                                "en_SJ" => "Svalbard and Jan Mayen Islands - U.K. English",
                                "en_SZ" => "Swaziland - U.K. English",
                                "en_SE" => "Sweden - U.K. English",
                                "de_CH" => "Switzerland - German",
                                "fr_CH" => "Switzerland - French",
                                "en_CH" => "Switzerland - U.S. English",
                                "en_TW" => "Taiwan - U.K. English",
                                "en_TJ" => "Tajikistan - U.K. English",
                                "en_TZ" => "Tanzania - U.K. English",
                                "en_TH" => "Thailand - U.K. English",
                                "en_TG" => "Togo - U.K. English",
                                "en_TO" => "Tonga - U.K. English",
                                "en_TT" => "Trinidad and Tobago - U.K. English",
                                "en_TN" => "Tunisia - U.K. English",
                                "en_TR" => "Turkey - U.K. English",
                                "en_TM" => "Turkmenistan - U.K. English",
                                "en_TC" => "Turks and Caicos Islands - U.K. English",
                                "en_TV" => "Tuvalu - U.K. English",
                                "en_UG" => "Uganda - U.K. English",
                                "en_UA" => "Ukraine - U.K. English",
                                "en_AE" => "United Arab Emirates - U.K. English",
                                "en_GB" => "United Kingdom - U.K. English",
                                "en_US" => "United States - U.S. English",
                                "fr_US" => "United States - French",
                                "es_US" => "United States - Spanish",
                                "zh_US" => "United States - Simplified Chinese",
                                "en_UY" => "Uruguay - U.K. English",
                                "en_VU" => "Vanuatu - U.K. English",
                                "en_VA" => "Vatican City State - U.K. English",
                                "en_VE" => "Venezuela - U.K. English",
                                "en_VN" => "Vietnam - U.K. English",
                                "en_WF" => "Wallis and Futuna Islands - U.K. English",
                                "en_YE" => "Yemen - U.K. English",
                                "en_ZM" => "Zambia - U.K. English",
                                "en_GB" => "International",
                                "en_AL" => "Albania - U.K. English",
                                "en_DZ" => "Algeria - U.K. English",
                                "en_AD" => "Andorra - U.K. English",
                                "en_AO" => "Angola - U.K. English",
                                "en_AI" => "Anguilla - U.K. English",
                                "en_AG" => "Antigua and Barbuda - U.K. English",
                                "en_AR" => "Argentina - U.K. English",
                                "en_AM" => "Armenia - U.K. English",
                                "en_AW" => "Aruba - U.K. English",
                                "en_AU" => "Australia - Australian English",
                                "de_AT" => "Austria - German",
                                "en_AT" => "Austria - U.S. English",
                                "en_AZ" => "Azerbaijan Republic - U.K. English",
                                "en_BS" => "Bahamas - U.K. English",
                                "en_BH" => "Bahrain - U.K. English",
                                "en_BB" => "Barbados - U.K. English",
                                "en_BE" => "Belgium - U.S. English",
                                "nl_BE" => "Belgium - Dutch",
                                "fr_BE" => "Belgium - French",
                                "en_BZ" => "Belize - U.K. English",
                                "en_BJ" => "Benin - U.K. English",
                                "en_BM" => "Bermuda - U.K. English",
                                "en_BT" => "Bhutan - U.K. English",
                                "en_BO" => "Bolivia - U.K. English",
                                "en_BA" => "Bosnia and Herzegovina - U.K. English",
                                "en_BW" => "Botswana - U.K. English",
                                "en_BR" => "Brazil - U.K. English",
                                "en_VG" => "British Virgin Islands - U.K. English",
                                "en_BN" => "Brunei - U.K. English",
                                "en_BG" => "Bulgaria - U.K. English",
                                "en_BF" => "Burkina Faso - U.K. English",
                                "en_BI" => "Burundi - U.K. English",
                                "en_KH" => "Cambodia - U.K. English",
                                "en_CA" => "Canada - U.S. English",
                                "fr_CA" => "Canada - French",
                                "en_CV" => "Cape Verde - U.K. English",
                                "en_KY" => "Cayman Islands - U.K. English",
                                "en_TD" => "Chad - U.K. English",
                                "en_CL" => "Chile - U.K. English",
                                "en_C2" => "China - U.S. English",
                                "zh_C2" => "China - Simplified Chinese",
                                "en_CO" => "Colombia - U.K. English",
                                "en_KM" => "Comoros - U.K. English",
                                "en_CK" => "Cook Islands - U.K. English",
                                "en_CR" => "Costa Rica - U.K. English",
                                "en_HR" => "Croatia - U.K. English",
                                "en_CY" => "Cyprus - U.K. English",
                                "en_CZ" => "Czech Republic - U.K. English",
                                "en_CD" => "Democratic Republic of the Congo - U.K. English",
                                "en_DK" => "Denmark - U.K. English",
                                "en_DJ" => "Djibouti - U.K. English",
                                "en_DM" => "Dominica - U.K. English",
                                "en_DO" => "Dominican Republic - U.K. English",
                                "en_EC" => "Ecuador - U.K. English",
                                "en_SV" => "El Salvador - U.K. English",
                                "en_ER" => "Eritrea - U.K. English",
                                "en_EE" => "Estonia - U.K. English",
                                "en_ET" => "Ethiopia - U.K. English",
                                "en_FK" => "Falkland Islands - U.K. English",
                                "en_FO" => "Faroe Islands - U.K. English",
                                "en_FM" => "Federated States of Micronesia - U.K. English",
                                "en_FJ" => "Fiji - U.K. English",
                                "en_FI" => "Finland - U.K. English",
                                "fr_FR" => "France - French",
                                "en_FR" => "France - U.S. English",
                                "en_GF" => "French Guiana - U.K. English",
                                "en_PF" => "French Polynesia - U.K. English",
                                "en_GA" => "Gabon Republic - U.K. English",
                                "en_GM" => "Gambia - U.K. English",
                                "de_DE" => "Germany - German",
                                "en_DE" => "Germany - U.S. English",
                                "en_GI" => "Gibraltar - U.K. English",
                                "en_GR" => "Greece - U.K. English",
                                "en_GL" => "Greenland - U.K. English",
                                "en_GD" => "Grenada - U.K. English",
                                "en_GP" => "Guadeloupe - U.K. English",
                                "en_GT" => "Guatemala - U.K. English",
                                "en_GN" => "Guinea - U.K. English",
                                "en_GW" => "Guinea Bissau - U.K. English",
                                "en_GY" => "Guyana - U.K. English",
                                "en_HN" => "Honduras - U.K. English",
                                "zh_HK" => "Hong Kong - Traditional Chinese",
                                "en_HK" => "Hong Kong - U.K. English",
                                "en_HU" => "Hungary - U.K. English",
                                "en_IS" => "Iceland - U.K. English",
                                "en_IN" => "India - U.K. English",
                                "en_ID" => "Indonesia - U.K. English",
                                "en_IE" => "Ireland - U.K. English",
                                "en_IL" => "Israel - U.K. English",
                                "it_IT" => "Italy - Italian",
                                "en_IT" => "Italy - U.S. English",
                                "en_JM" => "Jamaica - U.K. English",
                                "ja_JP" => "Japan - Japanese",
                                "en_JP" => "Japan - U.S. English",
                                "en_JO" => "Jordan - U.K. English",
                                "en_KZ" => "Kazakhstan - U.K. English",
                                "en_KE" => "Kenya - U.K. English",
                                "en_KI" => "Kiribati - U.K. English",
                                "en_KW" => "Kuwait - U.K. English",
                                "en_KG" => "Kyrgyzstan - U.K. English",
                                "en_LA" => "Laos - U.K. English",
                                "en_LV" => "Latvia - U.K. English",
                                "en_LS" => "Lesotho - U.K. English",
                                "en_LI" => "Liechtenstein - U.K. English",
                                "en_LT" => "Lithuania - U.K. English",
                                "en_LU" => "Luxembourg - U.K. English",
                                "en_MG" => "Madagascar - U.K. English",
                                "en_MW" => "Malawi - U.K. English",
                                "en_MY" => "Malaysia - U.K. English",
                                "en_MV" => "Maldives - U.K. English",
                                "en_ML" => "Mali - U.K. English",
                                "en_MT" => "Malta - U.K. English",
                                "en_MH" => "Marshall Islands - U.K. English",
                                "en_MQ" => "Martinique - U.K. English",
                                "en_MR" => "Mauritania - U.K. English",
                                "en_MU" => "Mauritius - U.K. English",
                                "en_YT" => "Mayotte - U.K. English",
                                "es_MX" => "Mexico - Spanish",
                                "en_MX" => "Mexico - U.S. English",
                                "en_MN" => "Mongolia - U.K. English",
                                "en_MS" => "Montserrat - U.K. English",
                                "en_MA" => "Morocco - U.K. English",
                                "en_MZ" => "Mozambique - U.K. English",
                                "en_NA" => "Namibia - U.K. English",
                                "en_NR" => "Nauru - U.K. English",
                                "en_NP" => "Nepal - U.K. English",
                                "nl_NL" => "Netherlands - Dutch",
                                "en_NL" => "Netherlands - U.S. English",
                                "en_AN" => "Netherlands Antilles - U.K. English",
                                "en_NC" => "New Caledonia - U.K. English",
                                "en_NZ" => "New Zealand - U.K. English",
                                "en_NI" => "Nicaragua - U.K. English",
                                "en_NE" => "Niger - U.K. English",
                                "en_NU" => "Niue - U.K. English",
                                "en_NF" => "Norfolk Island - U.K. English",
                                "en_NO" => "Norway - U.K. English",
                                "en_OM" => "Oman - U.K. English",
                                "en_PW" => "Palau - U.K. English",
                                "en_PA" => "Panama - U.K. English",
                                "en_PG" => "Papua New Guinea - U.K. English",
                                "en_PE" => "Peru - U.K. English",
                                "en_PH" => "Philippines - U.K. English",
                                "en_PN" => "Pitcairn Islands - U.K. English",
                                "pl_PL" => "Poland - Polish",
                                "en_PL" => "Poland - U.S. English",
                                "en_PT" => "Portugal - U.K. English",
                                "en_QA" => "Qatar - U.K. English",
                                "en_CG" => "Republic of the Congo - U.K. English",
                                "en_RE" => "Reunion - U.K. English",
                                "en_RO" => "Romania - U.K. English",
                                "en_RU" => "Russia - U.K. English",
                                "en_RW" => "Rwanda - U.K. English",
                                "en_VC" => "Saint Vincent and the Grenadines - U.K. English",
                                "en_WS" => "Samoa - U.K. English",
                                "en_SM" => "San Marino - U.K. English",
                                "en_ST" => "São Tomé and Príncipe - U.K. English",
                                "en_SA" => "Saudi Arabia - U.K. English",
                                "en_SN" => "Senegal - U.K. English",
                                "en_SC" => "Seychelles - U.K. English",
                                "en_SL" => "Sierra Leone - U.K. English",
                                "en_SG" => "Singapore - U.K. English",
                                "en_SK" => "Slovakia - U.K. English",
                                "en_SI" => "Slovenia - U.K. English",
                                "en_SB" => "Solomon Islands - U.K. English",
                                "en_SO" => "Somalia - U.K. English",
                                "en_ZA" => "South Africa - U.K. English",
                                "en_KR" => "South Korea - U.K. English",
                                "es_ES" => "Spain - Spanish",
                                "en_ES" => "Spain - U.S. English",
                                "en_LK" => "Sri Lanka - U.K. English",
                                "en_SH" => "St. Helena - U.K. English",
                                "en_KN" => "St. Kitts and Nevis - U.K. English",
                                "en_LC" => "St. Lucia - U.K. English",
                                "en_PM" => "St. Pierre and Miquelon - U.K. English",
                                "en_SR" => "Suriname - U.K. English",
                                "en_SJ" => "Svalbard and Jan Mayen Islands - U.K. English",
                                "en_SZ" => "Swaziland - U.K. English",
                                "en_SE" => "Sweden - U.K. English",
                                "de_CH" => "Switzerland - German",
                                "fr_CH" => "Switzerland - French",
                                "en_CH" => "Switzerland - U.S. English",
                                "en_TW" => "Taiwan - U.K. English",
                                "en_TJ" => "Tajikistan - U.K. English",
                                "en_TZ" => "Tanzania - U.K. English",
                                "en_TH" => "Thailand - U.K. English",
                                "en_TG" => "Togo - U.K. English",
                                "en_TO" => "Tonga - U.K. English",
                                "en_TT" => "Trinidad and Tobago - U.K. English",
                                "en_TN" => "Tunisia - U.K. English",
                                "en_TR" => "Turkey - U.K. English",
                                "en_TM" => "Turkmenistan - U.K. English",
                                "en_TC" => "Turks and Caicos Islands - U.K. English",
                                "en_TV" => "Tuvalu - U.K. English",
                                "en_UG" => "Uganda - U.K. English",
                                "en_UA" => "Ukraine - U.K. English",
                                "en_AE" => "United Arab Emirates - U.K. English",
                                "en_GB" => "United Kingdom - U.K. English",
                                "en_US" => "United States - U.S. English",
                                "fr_US" => "United States - French",
                                "es_US" => "United States - Spanish",
                                "zh_US" => "United States - Simplified Chinese",
                                "en_UY" => "Uruguay - U.K. English",
                                "en_VU" => "Vanuatu - U.K. English",
                                "en_VA" => "Vatican City State - U.K. English",
                                "en_VE" => "Venezuela - U.K. English",
                                "en_VN" => "Vietnam - U.K. English",
                                "en_WF" => "Wallis and Futuna Islands - U.K. English",
                                "en_YE" => "Yemen - U.K. English",
                                "en_ZM" => "Zambia - U.K. English",
                                "en_GB" => "International"
                            );           
							
                            ?>
                                <select name="sd_paypal_language">
                                    <?php
                                        $language_selected = get_option('sd_paypal_language', SD_PAYPAL_LANGUAGE);
                                        foreach($languages_list as $key => $value){
                                            echo '<option value="'.$key.'" '.(($key == $language_selected) ? 'SELECTED' : '').'>'.$value.'</option>';
                                        }
                                    ?>
                                </select>
                                <span style="color:#FF0000;">Additional languages are available in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
                            </td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Paypal button for instant purchases', SD_TEXT_DOMAIN); ?></th>
							<td><?php print $this->_paypal_buttons(); ?></td>
							</tr> 
							
							<tr valign="top">
							<th scope="row"><?php _e("or use a shopping cart", SD_TEXT_DOMAIN); ?></th>
							<td>
								<input type='radio' DISABLED /> 
								<img src="<?php echo SD_URL.'/paypal_buttons/'.SD_PAYPAL_ADD_CART_BUTTON;?>" />  
								<img src="<?php echo SD_URL.'/paypal_buttons/'.SD_PAYPAL_VIEW_CART_BUTTON;?>" />
                                <span style="color:#FF0000;">The shopping cart is available only in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
							</td>
							</tr> 
							
							<tr valign="top">
							<th scope="row"><?php _e('Download link valid for', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_old_download_link" value="<?php echo esc_attr(get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)); ?>" /> <?php _e('day(s)', SD_TEXT_DOMAIN)?></td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Increase the download page security', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="sd_safe_download" <?php echo ( ( get_option('sd_safe_download', SD_SAFE_DOWNLOAD)) ? 'CHECKED' : '' ); ?> /> <?php _e('The customers must enter the email address used in the product\'s purchasing to access to the download link. The Store verifies the customer\'s data, from the file link too.', SD_TEXT_DOMAIN)?></td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Request for the acceptance of cookies', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" DISABLED /> <?php _e('The users should accept store cookies to use of shopping cart.', SD_TEXT_DOMAIN)?>
							<span style="color:#FF0000;">The option is available only in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
							</td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Text to request the acceptance of cookies', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" DISABLED size="40" /> <span style="color:#FF0000;">The option is available only in the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a></td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Pack all purchased files as a single ZIP file', SD_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" DISABLED >
                            <span style="color:#FF0000;">To distribute the file as a zipped file is required the commercial version of plugin.</span> <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a>
							<?php
								if(!class_exists('ZipArchive'))
									echo '<br /><span class="explain-text">'.__("Your server can't create Zipped files dynamically. Please, contact to your hosting provider for enable ZipArchive in the PHP script", SD_TEXT_DOMAIN).'</span>';
							?>
							</td>
							</tr>
						 </table>  
					  </div>
					</div>
					
					<!--DISCOUNT BOX -->
                    <div class="postbox">
                        <h3 class='hndle' style="padding:5px;"><span><?php _e('Discount Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
                            <div style="color:#FF0000;">The discounts management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a></div>
                            <div><input type="checkbox" DISABLED /> <?php _e('Display discount promotions in the store page', SD_TEXT_DOMAIN)?></div>
                            <h4><?php _e('Scheduled Discounts', SD_TEXT_DOMAIN);?></h4>
                            <table class="form-table sd_discount_table" style="border:1px dotted #dfdfdf;">
                                <tr>
                                    <td style="font-weight:bold;"><?php _e('Percent of discount', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('In Sales over than ... ', SD_TEXT_DOMAIN); echo($currency); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                                    <td></td>
                                </tr>
                            </table>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Percent of discount (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> %</td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid for sales over than (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> USD</td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid from (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid to (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></th>
                                    <td><textarea DISABLED cols="60"></textarea></td>
                                </tr>
                                <tr><td colspan="2"><input type="button" class="button" value="<?php _e('Add/Update Discount'); ?>" DISABLED></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <!--COUPONS BOX -->
                    <div class="postbox">
                        <h3 class='hndle' style="padding:5px;"><span><?php _e('Coupons Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
                            <div style="color:#FF0000;">The coupons management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads#download" target="_blank">Press Here</a></div>
                            <h4><?php _e('Coupons List', SD_TEXT_DOMAIN);?></h4>
                            <table class="form-table sd_coupon_table" style="border:1px dotted #dfdfdf;">
                                <tr>
                                    <td style="font-weight:bold;"><?php _e('Percent of discount', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Coupon', SD_TEXT_DOMAIN);?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                                    <td></td>
                                </tr>
                            </table>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Percent of discount (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /> %</td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Coupon (*)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid from (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Valid to (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                                    <td><input type="text" DISABLED /></td>
                                </tr>
                                <tr><td colspan="2"><input type="button" class="button" value="<?php _e('Add/Update Coupon'); ?>"DISABLED /></td></tr>
                            </table>
                        </div>
                    </div>
                        
                    <!-- NOTIFICATIONS BOX -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Notification Settings', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">

						<table class="form-table">
							<tr valign="top">        
							<th scope="row"><?php _e('Notification "from" email', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_from_email" size="40" value="<?php echo esc_attr(get_option('sd_notification_from_email', SD_NOTIFICATION_FROM_EMAIL)); ?>" /></td>
							</tr>    
						
							<tr valign="top">        
							<th scope="row"><?php _e('Send notification to email', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_email" size="40" value="<?php echo esc_attr(get_option('sd_notification_to_email', SD_NOTIFICATION_TO_EMAIL)); ?>" /></td>
							</tr>
							 
							<tr valign="top">
							<th scope="row"><?php _e('Email subject confirmation to user', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_payer_subject" size="40" value="<?php echo esc_attr(get_option('sd_notification_to_payer_subject', SD_NOTIFICATION_TO_PAYER_SUBJECT)); ?>" /></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email confirmation to user', SD_TEXT_DOMAIN); ?></th>
							<td><textarea name="sd_notification_to_payer_message" cols="60" rows="5"><?php echo esc_attr(get_option('sd_notification_to_payer_message', SD_NOTIFICATION_TO_PAYER_MESSAGE)); ?></textarea></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email subject notification to admin', SD_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="sd_notification_to_seller_subject" size="40" value="<?php echo esc_attr(get_option('sd_notification_to_seller_subject', SD_NOTIFICATION_TO_SELLER_SUBJECT)); ?>" /></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email notification to admin', SD_TEXT_DOMAIN); ?></th>
							<td><textarea name="sd_notification_to_seller_message"  cols="60" rows="5"><?php echo esc_attr(get_option('sd_notification_to_seller_message', SD_NOTIFICATION_TO_SELLER_MESSAGE)); ?></textarea></td>
							</tr>
						 </table>  
					  </div>
					</div>
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sd_settings' ); ?>
					<div class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Settings', SD_TEXT_DOMAIN); ?>" />
					</form>

<?php				
				break;
				case 'reports':
					if ( isset($_POST['sd_purchase_stats']) && wp_verify_nonce( $_POST['sd_purchase_stats'], plugin_basename( __FILE__ ) ) ){
						if(isset($_POST['purchase_id'])){ // Delete the purchase
							$wpdb->query($wpdb->prepare(
								"DELETE FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE id=%d",
								$_POST['purchase_id']
							));
						}
					}
					
					$from_day = (isset($_POST['from_day'])) ? $_POST['from_day'] : date('j');
					$from_month = (isset($_POST['from_month'])) ? $_POST['from_month'] : date('m');
					$from_year = (isset($_POST['from_year'])) ? $_POST['from_year'] : date('Y');
					
					$to_day = (isset($_POST['to_day'])) ? $_POST['to_day'] : date('j');
					$to_month = (isset($_POST['to_month'])) ? $_POST['to_month'] : date('m');
					$to_year = (isset($_POST['to_year'])) ? $_POST['to_year'] : date('Y');
					
					$purchase_list = $wpdb->get_results("SELECT purchase.*, posts.* FROM ".$wpdb->prefix.SDDB_PURCHASE." AS purchase, ".$wpdb->prefix."posts AS posts WHERE posts.ID = purchase.product_id AND DATEDIFF(purchase.date, '{$from_year}-{$from_month}-{$from_day}')>=0 AND DATEDIFF(purchase.date, '{$to_year}-{$to_month}-{$to_day}')<=0;");
					
?>
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="purchase_form">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sd_purchase_stats' ); ?>
					<input type="hidden" name="tab" value="reports" />
					<!-- FILTER REPORT -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Filter from date', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<?php
								$months_list = array(
									'01' => __('January', SD_TEXT_DOMAIN),
									'02' => __('February', SD_TEXT_DOMAIN),
									'03' => __('March', SD_TEXT_DOMAIN),
									'04' => __('April', SD_TEXT_DOMAIN),
									'05' => __('May', SD_TEXT_DOMAIN),
									'06' => __('June', SD_TEXT_DOMAIN),
									'07' => __('July', SD_TEXT_DOMAIN),
									'08' => __('August', SD_TEXT_DOMAIN),
									'09' => __('September', SD_TEXT_DOMAIN),
									'10' => __('October', SD_TEXT_DOMAIN),
									'11' => __('November', SD_TEXT_DOMAIN),
									'12' => __('December', SD_TEXT_DOMAIN),
								);
							?>
							<label><?php _e('From: ', SD_TEXT_DOMAIN); ?></label>
							<select name="from_day">
							<?php
								for($i=1; $i <=31; $i++) print '<option value="'.$i.'"'.(($from_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
							?>
							</select>
							<select name="from_month">
							<?php
								foreach($months_list as $month => $name) print '<option value="'.$month.'"'.(($from_month == $month) ? ' SELECTED' : '').'>'.$name.'</option>';
							?>
							</select>
							<input type="text" name="from_year" value="<?php print $from_year; ?>" />
							
							<label><?php _e('To: ', SD_TEXT_DOMAIN); ?></label>
							<select name="to_day">
							<?php
								for($i=1; $i <=31; $i++) print '<option value="'.$i.'"'.(($to_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
							?>
							</select>
							<select name="to_month">
							<?php
								foreach($months_list as $month => $name) print '<option value="'.$month.'"'.(($to_month == $month) ? ' SELECTED' : '').'>'.$name.'</option>';
							?>
							</select>
							<input type="text" name="to_year" value="<?php print $to_year; ?>" />
							
							<input type="submit" value="<?php _e('Search', SD_TEXT_DOMAIN); ?>" class="button-primary" />
						</div>
					</div>	
					<!-- PURCHASE LIST -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Sell Downloads sales report', SD_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<?php 
								if(count($purchase_list)){	
									print '
										<div>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="sd_load_report(this, \'sales_by_country\', \''.__( 'Sales by country', SD_TEXT_DOMAIN ).'\', \'residence_country\', \'Pie\', \'residence_country\', \'count\');" /> '.__( 'Sales by country', SD_TEXT_DOMAIN ).'</label>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="sd_load_report(this, \'sales_by_currency\', \''.__( 'Sales by currency', SD_TEXT_DOMAIN ).'\', \'mc_currency\', \'Bar\', \'mc_currency\', \'sum\');" /> '.__( 'Sales by currency', SD_TEXT_DOMAIN ).'</label>
											<label><input type="checkbox" onclick="sd_load_report(this, \'sales_by_product\', \''.__( 'Sales by product', SD_TEXT_DOMAIN ).'\', \'product_name\', \'Bar\', \'post_title\', \'sum\');" /> '.__( 'Sales by product', SD_TEXT_DOMAIN ).'</label>
										</div>';
								}
							?>
						    <div id="charts_content" >
								<div id="sales_by_country"></div>
								<div id="sales_by_currency"></div>
								<div id="sales_by_product"></div>
							</div>
							<div class="sd-section-title"><?php _e( 'Products List', SD_TEXT_DOMAIN ); ?></div>
							<table class="form-table" style="border-bottom:1px solid #CCC;margin-bottom:10px;">
								<THEAD>
									<TR style="border-bottom:1px solid #CCC;">
										<TH>Product</TH><TH>Buyer</TH><TH>Amount</TH><TH>Currency</TH><TH>Download link</TH><TH>Notes</TH><TH></TH>
									</TR>
								</THEAD>
								<TBODY>
								<?php
								$totals = array('UNDEFINED'=>0);
                                if(count($purchase_list)){	
									$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
									$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

									foreach($purchase_list as $purchase){
										
										if(preg_match('/mc_currency=([^\s]*)/', $purchase->paypal_data, $matches)){
											$currency = strtoupper($matches[1]);
											if(!isset($totals[$currency])) $totals[$currency] = $purchase->amount;
											else $totals[$currency] += $purchase->amount;
										}else{
											$currency = '';
											$totals['UNDEFINED'] += $purchase->amount;
										}
										echo '
											<TR>
												<TD><a href="'.get_permalink($purchase->ID).'" target="_blank">'.$purchase->post_title.'</a></TD>
												<TD>'.$purchase->email.'</TD>
												<TD>'.$purchase->amount.'</TD>
												<TD>'.$currency.'</TD>
												<TD><a href="'.$dlurl.'purchase_id='.$purchase->purchase_id.'" target="_blank">Download Link</a></TD>
												<TD>'.$purchase->note.'</TD>
                                                <TD><input type="button" class="button-primary" onclick="delete_purchase_sd('.$purchase->id.');" value="Delete"></TD>
											</TR>
										';
									}
								}else{
									echo '
										<TR>
											<TD COLSPAN="7">
												'.__('No sales yet', SD_TEXT_DOMAIN).'
											</TD>
										</TR>
									';
								}	
								?>
								</TBODY>
							</table>
							
							<?php
								if(count($totals) > 1 || $totals['UNDEFINED']){
							?>
									<table style="border: 1px solid #CCC;">
										<TR><TD COLSPAN="2" style="border-bottom:1px solid #CCC;">TOTALS</TD></TR>
										<TR><TD style="border-bottom:1px solid #CCC;">CURRENCY</TD><TD style="border-bottom:1px solid #CCC;">AMOUNT</TD></TR>
									<?php
										foreach($totals as $currency=>$amount)
											if($amount)
												print "<TR><TD><b>{$currency}</b></TD><TD>{$amount}</TD></TR>";
									?>	
									</table>
							<?php	
								}
							?>
						</div>
					</div>
					</form>
<?php					
				break;
			}	
		} // End settings_page

/** LOADING PUBLIC OR ADMINSITRATION RESOURCES **/		

		/**
		* Load public scripts and styles
		*/
		function public_resources(){
			wp_enqueue_script('jquery');
			if( wp_style_is( 'wp-mediaelement', 'registered' ) ){
				wp_enqueue_style( 'wp-mediaelement' );
				wp_enqueue_script( 'wp-mediaelement' );
			}else{
				wp_enqueue_style( 'wp-mediaelement',  plugin_dir_url(__FILE__).'mediaelement/wp-mediaelement.css' );
				wp_enqueue_script( 'wp-mediaelement', plugin_dir_url(__FILE__).'mediaelement/mediaelement-and-player.min.js', array( 'jquery' ) );
			}
			
			wp_enqueue_style('sd-style', plugin_dir_url(__FILE__).'sd-styles/sd-public.css');
            wp_enqueue_script('sd-media-script', plugin_dir_url(__FILE__).'sd-script/sd-public.js', array('wp-mediaelement'), null, true);
			wp_localize_script( 'sd-media-script', 
								'sd_global', 
								array(
									'url' => SD_URL, 
									'hurl' => SD_H_URL,
									'texts' => array(
									  'close_demo' => 'close',
									  'download_demo' => 'download file',
									  'plugin_fault' => 'The Object to display the demo file is not enabled in your browser. CLICK HERE to download the demo file',
									)
								)
							);
		} // End public_resources
		
		/**
		* Load admin scripts and styles
		*/
		function admin_resources($hook){
			global $post;

			if(strpos($hook, "sell-downloads") !== false){
				wp_enqueue_script('sd-chart-script', plugin_dir_url(__FILE__).'sd-script/Chart.min.js', array( 'jquery' ) );
				wp_enqueue_script('sd-admin-script', plugin_dir_url(__FILE__).'sd-script/sd-admin.js', array('jquery','sd-chart-script'), null, true);
                wp_enqueue_style('sd-admin-style', plugin_dir_url(__FILE__).'sd-styles/sd-admin.css');
				wp_localize_script('sd-admin-script', 'sd_global', array( 'aurl' => admin_url() ));
			}
        
			if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'index.php') {
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('sd-admin-script', plugin_dir_url(__FILE__).'sd-script/sd-admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'media-upload'), null, true);
				
				if( isset( $post ) && $post->post_type == "sd_product"){
					// Scripts and styles required for metaboxs
					wp_enqueue_style('sd-admin-style', plugin_dir_url(__FILE__).'sd-styles/sd-admin.css');
					wp_localize_script('sd-admin-script', 'sell_downloads', array('post_id' => $post->ID));	
				}else{
					// Scripts required for sell downloads insertion
					wp_enqueue_style('wp-jquery-ui-dialog');
					
					// Set the variables for insertion dialog
					$tags = '';
					// Load file types
					$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));
					
                    $tags .= '<div title="'.__('Insert Sell Downloads', SD_TEXT_DOMAIN).'"><div style="padding:20px;">';
					$tags .= '<div>'.__('Columns:', SD_TEXT_DOMAIN).' <br /><input type="text" name="columns" id="columns" style="width:100%" value="1" /></div>';		
					
					$tags .= '<div>'.__('Filter results by file type:', SD_TEXT_DOMAIN).'<br /><select id="type" name="type" style="width:100%"><option value="all">'.__('All file types', SD_TEXT_DOMAIN).'</option>';
					foreach($type_list as $type){
							$tags .= '<option value="'.$type->term_id.'">'.$type->name.'</option>';
					}
					$tags .= '</select></div>';
					$tags .= '</div></div>';
					
					wp_localize_script('sd-admin-script', 'sell_downloads', array('tags' => $tags));	
				}	
			}
		} // End admin_resources
		

/** LOADING SELL DOWNLOADS AND ITEMS ON WORDPRESS SECTIONS **/		
				
		/**
		* Replace the sell_downloads shortcode with correct items
		*
		*/
		function load_store($atts, $content, $tag){
			global $wpdb;
			
			$page_id = 'sd_page_'.get_the_ID();
            
            if( !isset( $_SESSION[ $page_id ] ) ) $_SESSION[ $page_id ] = array();
  			
			// Generated sell downloads
			$sell_downloads = "";
			$page_links = "";
			$header = "";
			
			// Extract the sell downloads attributes
			extract(shortcode_atts(array(
					'type'		=> 'all',
					'columns'  	=> 1
				), $atts)
			);

			// Extract query_string variables correcting sell downloads attributes
			if(isset($_REQUEST['filter_by_type'])){
				$_SESSION[ $page_id ]['sd_type'] = $_REQUEST['filter_by_type'];
			}
			
			if(isset($_SESSION[ $page_id ]['sd_type'])){
				$type = $_SESSION[ $page_id ]['sd_type'];
			}
			
			if(isset($_REQUEST['ordering_by']) && in_array($_REQUEST['ordering_by'], array('plays', 'price', 'post_title', 'post_date'))){
				$_SESSION[ $page_id ]['sd_ordering'] = $_REQUEST['ordering_by'];
			}elseif( !isset($_SESSION[ $page_id ]['sd_ordering']) ){
				$_SESSION[ $page_id ]['sd_ordering'] = "post_date";
			}
			
			// Extract info from sell downloads options
			$allow_filter_by_type = get_option('sd_filter_by_type', SD_FILTER_BY_TYPE);

 			// Items per page
			$items_page 			= max(get_option('sd_items_page', SD_ITEMS_PAGE), 1);
			// Display pagination
			$items_page_selector 	= get_option('sd_items_page_selector', SD_ITEMS_PAGE_SELECTOR);
			
			// Query clauses 
			$_select 	= "SELECT DISTINCT posts.ID, posts.post_type";
			$_from 		= "FROM ".$wpdb->prefix."posts as posts,".$wpdb->prefix.SDDB_POST_DATA." as posts_data"; 
			$_where 	= "WHERE posts.ID = posts_data.id AND posts.post_status='publish'";
			$_order_by 	= "ORDER BY ".(($_SESSION[ $page_id ]['sd_ordering'] == "post_title" || $_SESSION[ $page_id ]['sd_ordering'] == "post_date" ) ? "posts" : "posts_data").".".$_SESSION[ $page_id ]['sd_ordering']." ".(($_SESSION[ $page_id ]['sd_ordering'] == 'plays' || $_SESSION[ $page_id ]['sd_ordering'] == 'post_date') ? "DESC" : "ASC");
			$_limit 	= "";
			
			if($type !== 'all'){
				// Load the taxonomy tables
				$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy, ".$wpdb->prefix."term_relationships as term_relationships, ".$wpdb->prefix."terms as terms";
				
				$_where .= " AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id AND term_relationships.object_id=posts.ID AND taxonomy.term_id=terms.term_id AND (";
				
				if($type !== 'all'){
					// Search for types assigned directly to the posts
					$_where .= "(taxonomy.taxonomy='sd_type' AND ";
					
					if(is_numeric($type))
						$_where .= "terms.term_id='$type'";
					else
						$_where .= "terms.slug='$type'";	
					
					$_where .= ")";
					
				}
				
				$_where .= ")";
				
				// End taxonomies
			} 
			
			$_where .= " AND post_type='sd_product'";
			
			// Create pagination section
			if($items_page_selector && $items_page){
				// Checking for page parameter or get page from session variables
				// Clear the page number if filtering option change
				if(isset($_POST['filter_by_type'])){
					$_SESSION[ $page_id ]['sd_page_number'] = 0;
				}elseif(isset($_GET['page_number'])){
					$_SESSION[ $page_id ]['sd_page_number'] = $_GET['page_number'];
				}elseif(!isset($_SESSION[ $page_id ]['sd_page_number'])){
					$_SESSION[ $page_id ]['sd_page_number'] = 0;
				}

				$_limit = "LIMIT ".($_SESSION[ $page_id ]['sd_page_number']*$items_page).", $items_page";
				
				// Get total records for pagination
				$query = "SELECT COUNT(DISTINCT posts.ID) ".$_from." ".$_where;
				$total = $wpdb->get_var($query);
				$total_pages = ceil($total/max($items_page,1));
				
				if($total_pages > 1){
				
					// Make page links
					$page_links .= "<DIV class='sell-downloads-pagination'>";
					$page_href = '?'.((strlen($_SERVER['QUERY_STRING'])) ? preg_replace('/(&)?page_number=\d+/', '', $_SERVER['QUERY_STRING']).'&' : '');	
					
				
					for($i=0, $h = $total_pages; $i < $h; $i++){
						if($_SESSION[ $page_id ]['sd_page_number'] == $i)
							$page_links .= "<span class='page-selected'>".($i+1)."</span>";
						else	
							$page_links .= "<a class='page-link' href='".$page_href."page_number=".$i."'>".($i+1)."</a>";
					}
					$page_links .= "</DIV>";
				}	
			}
			
			// Create items section
			$query = $_select." ".$_from." ".$_where." ".$_order_by." ".$_limit;
			$results = $wpdb->get_results($query);
			$tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');
			
			$width = floor(100/min($columns, max(count($results),1)));
			$sell_downloads .= "<div class='sell-downloads-items'>";
			$item_counter = 0;
			foreach($results as $result){
				$obj = new SDProduct($result->ID);
				$sell_downloads .= "<div style='width:{$width}%;' class='sell-downloads-item'>".$obj->display_content('store', $tpl, 'return')."</div>";
				$item_counter++;
				if($item_counter % $columns == 0)
					$sell_downloads .= "<div style='clear:both;'></div>";
			}
			$sell_downloads .= "<div style='clear:both;'></div>";
			$sell_downloads .= "</div>";
			$header .= "
						<form method='post'>
						<div class='sell-downloads-header'>
						";
			// Create filter section
			if($allow_filter_by_type ){
				$header .= "<div class='sell-downloads-filters'><span>".__('Filter by', SD_TEXT_DOMAIN)."</span>";
                // List all file types
				if($allow_filter_by_type){
					$header .= "<span>".__(' file type: ', SD_TEXT_DOMAIN).
							"<select id='filter_by_type' name='filter_by_type' onchange='this.form.submit();'>
							<option value='all'>".__('All file types', SD_TEXT_DOMAIN)."</option>
							";
					$types = get_terms("sd_type");
					foreach($types as $type_item){
                    	$header .= "<option value='".$type_item->slug."' ".(($type == $type_item->slug || $type == $type_item->term_id) ? "SELECTED" : "").">".$type_item->name."</option>";
					}
					$header .= "</select></span>";
				}
				$header .="</div>";
			}
			
			// Create order filter
			$header .= "<div class='sell-downloads-ordering'>".
							__('Order by: ', SD_TEXT_DOMAIN).
							"<select id='ordering_by' name='ordering_by' onchange='this.form.submit();'>
								<option value='post_date' ".(($_SESSION[ $page_id ]['sd_ordering'] == 'post_date') ? "SELECTED" : "").">".__('Date', SD_TEXT_DOMAIN)."</option>
								<option value='post_title' ".(($_SESSION[ $page_id ]['sd_ordering'] == 'post_title') ? "SELECTED" : "").">".__('Name', SD_TEXT_DOMAIN)."</option>
								<option value='plays' ".(($_SESSION[ $page_id ]['sd_ordering'] == 'plays') ? "SELECTED" : "").">".__('Popularity', SD_TEXT_DOMAIN)."</option>
								<option value='price' ".(($_SESSION[ $page_id ]['sd_ordering'] == 'price') ? "SELECTED" : "").">".__('Price', SD_TEXT_DOMAIN)."</option>
							</select>
						</div>";
						
			$header .= "
						</div>
						</form>
						";
            
            return $header.$sell_downloads.$page_links;
		} // End load_store
			
/** MODIFY CONTENT OF POSTS LOADED **/
		
		/*
		* Load the templates for products display
		*/
		function load_templates(){
			add_filter('the_content', array(&$this, 'display_content'));
		} // End load_templates
		
		/**
		* Display content of products through templates
		*/
		function display_content($content){
			global $post;
			
			if(in_the_loop() && $post && $post->post_type == 'sd_product'){
				$tpl = new sell_downloads_tpleng(dirname(__FILE__).'/sd-templates/', 'comment');
				$product = new SDProduct($post->ID);
				return $product->display_content(((is_singular()) ? 'single' : 'multiple'), $tpl, 'return');
			}else{
				return $content;
			}
		} // End display_content
		

		/**
		* Set a media button for sell downloads insertion
		*/
		function set_sell_downloads_button(){
			global $post;
			
			if( isset( $post ) && $post->post_type != 'sd_product')
			print '<a href="javascript:open_insertion_sell_downloads_window();" title="'.__('Insert Sell Downloads').'"><img src="'.SD_CORE_IMAGES_URL.'/sell-downloads-icon.png'.'" alt="'.__('Insert Sell Downloads').'" /></a>';
		} // End set_sell_downloads_button
		
		
		/**
		*	Check for post to delete and remove the metadata saved on additional metadata tables
		*/
		function delete_post($pid){
			global $wpdb;
			return  $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix.SDDB_POST_DATA." WHERE id=%d;",$pid));
		} // End delete_post

	} // End SellDownloads class
	
	// Initialize SellDownloads class
	if( session_id() == "" || !isset( $_SESSION ) ) session_start(); 
	
	$GLOBALS['sell_downloads'] = new SellDownloads;
	
	register_activation_hook(__FILE__, array(&$GLOBALS['sell_downloads'], 'register'));
	
} // Class exists check
?>
