<?php

	// Errors management
	$sd_errors = array();
	function sell_downloads_setError($error_text){
		global $sd_errors;
		$sd_errors[] = __($error_text, SD_TEXT_DOMAIN);
	}	

	// Check if URL is for a local file, and return the relative URL or false
	function sd_is_local( $file ){
		if( strpos( $file, SD_H_URL ) !== false ){
			$parts = explode( '/', str_replace('\\', '/', str_replace( SD_H_URL, '', SD_URL.'/sd-core' ) ) );
			$file = str_replace( SD_H_URL, '', $file );
			$path = '';
			for( $i = 0; $i < count( $parts ); $i++ ){
				$path .= '../';
			}
			$file = dirname( __FILE__ ).'/'.$path.$file;
			return file_exists( $file ) ? $file : false;
		}
		return false;
	}
	
	// Check downloads permissions
	function sd_check_download_permissions(){
		global $wpdb;
		// If not session, create it
		if( session_id() == "" || !isset( $_SESSION ) ) session_start();

		// Check if download for free or the user is an admin
		if(	!empty( $_SESSION[ 'sd_download_for_free' ] ) || current_user_can( 'manage_options' ) ) return true;
		
		// and check the existence of a parameter with the purchase_id
		if( empty( $_REQUEST[ 'purchase_id' ] ) ){ 
			sell_downloads_setError( 'The purchase id is required' );
			return false;
		}	
		
		if( get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) ){
			// Check if the user has typed the email used to purchase the product 
			if( !empty( $_REQUEST[ 'sd_user_email' ] ) ) $_SESSION[ 'sd_user_email' ] =  $_REQUEST[ 'sd_user_email' ];
			
			if( empty( $_SESSION[ 'sd_user_email' ] ) ){ 
				$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); 
				$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).'purchase_id='.$_REQUEST[ 'purchase_id' ];
				sell_downloads_setError( "Please, enter the email address used in products purchasing" );
				return false;
			}	
			$days = $wpdb->get_var( $wpdb->prepare( 'SELECT DATEDIFF(NOW(), date) FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s AND email=%s', array( $_REQUEST[ 'purchase_id' ], $_SESSION[ 'sd_user_email' ] ) ) );
		}else{
			$days = $wpdb->get_var( $wpdb->prepare( 'SELECT DATEDIFF(NOW(), date) FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s', array( $_REQUEST[ 'purchase_id' ] ) ) );
		}

		if( is_null( $days ) ){
			sell_downloads_setError( 'There is no product associated with the entered data' );
			return false;
		}elseif( get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK) < $days ){ 
			sell_downloads_setError( 'The download link has expired, please contact to the vendor' );
			return false;	
		}

		return true;
	} // End sd_check_download_permissions

	// Check if the PHP memory is sufficient
	function sell_downloads_check_memory( $files = array() ){
		$required = 0;
		
		$m = ini_get( 'memory_limit' );
		$m = trim($m);
		$l = strtolower($m[strlen($m)-1]); // last
		switch($l) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$m *= 1024;
			case 'm':
				$m *= 1024;
			case 'k':
				$m *= 1024;
		}

		foreach ( $files as $file ){
			$memory_available = $m - memory_get_usage(true);
			if( ( $relative_path = sd_is_local( $file ) ) !== false ){
				$required += filesize( $relative_path );
				if( $required >= $memory_available - 100 ) return false;
			}else{
				$response = wp_remote_head( $file );
				if( !is_wp_error( $response ) && $response['response']['code'] == 200 ){
					$required += $response['headers']['content-length'];
					if( $required >= $memory_available - 100 ) return false;
				}else return false;
			}	
		}
		return true;
	} // music_store_check_memory

	function sell_downloads_extract_attr_as_str($arr, $attr, $separator){
		$result = '';
		$c = count($arr);
		if($c){
			$t = (array)$arr[0];
			$result .= $t[$attr];
			for($i=1; $i < $c; $i++){
				$t = (array)$arr[$i];
				$result .= $separator.$t[$attr];
			}	
		}
		
		return $result;
	} // End sell_downloads_extract_attr_as_str

	function sell_downloads_get_img_id($url){
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $wpdb->prefix . "posts" . " WHERE guid='%s';", $url )); 
		return $attachment[0];
	} // End sell_downloads_get_img_id

	function sell_downloads_make_seed() {
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}

	function sell_downloads_register_purchase($product_id, $purchase_id, $email, $amount, $paypal_data){
		global $wpdb;
		return $wpdb->insert(
			$wpdb->prefix.SDDB_PURCHASE,
			array(
				'product_id'  => $product_id,
				'purchase_id' => $purchase_id,
				'date'		  => date( 'Y-m-d H:i:s'),
				'email'		  => $email,
				'amount'	  => $amount,
				'paypal_data' => $paypal_data
			),
			array('%d', '%s', '%s', '%s', '%f', '%s')
		);
	}
	
	function sd_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = md5($file).'.'.$ext;
		$file_path = SD_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($file_path))
			return $new_file_name;
		
		if( ( $path = sd_is_local( $file ) ) !== false ){
			if( copy( $path, $file_path) ) return $new_file_name;
		}else{
			if( !sell_downloads_check_memory( array( $file ) ) ) return $file;
			$response = wp_remote_get($file);
			if( !is_wp_error( $response ) && $response['response']['code'] == 200 && file_put_contents($file_path, $response['body'])) return $new_file_name;
		}
		return $file;
	} // End sd_copy_download_links
	
	function sd_remove_download_links(){
		global $htaccess_accepted;
		
		$now = time();
		$dif = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;
		$d = dir(SD_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
			if($entry != '.' && $entry != '..' && $entry != 'sell-downloads-icon.png'){
				if($entry == '.htaccess'){
					if(!$htaccess_accepted){ // Remove the htaccess if it is not accepted
						@unlink(SD_DOWNLOAD.'/'.$entry);
					}
				}else{
					$file_name = SD_DOWNLOAD.'/'.$entry;
					$date = filemtime($file_name);
					if($now-$date >= $dif){ // Delete file
						@unlink($file_name);
					}
				}
			}
		}
		$d->close();
	} // End sd_remove_download_links

	function sd_product_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	} // End sd_product_title
	
	function sd_generate_downloads($the_content){
		global $wpdb, $download_links_str, $id;
		
		if( sd_check_download_permissions() ){
			if($id){
				global $htaccess_accepted;
				
				$response = wp_remote_get(SD_URL.'/sd-downloads/sell-downloads-icon.png');
				$htaccess_accepted = (!is_wp_error( $response ) && ( $response['response']['code'] == 200 || $response['response']['code'] == 403 ) );
			
				sd_remove_download_links();
				
				$purchase_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
				
				if($purchase_rows){ // Exists the purchase
					$interval = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;
					
					$urls = array();
					$tmp_arr = array();
					$download_links_str = '';
					
					foreach($purchase_rows as $purchase){
						if(!current_user_can( 'manage_options' )){
							$diff = abs(strtotime($purchase->date)-time());
							if($diff > $interval){
								$download_links_str = __('The download link has expired, please contact to the vendor', SD_TEXT_DOMAIN);
								break;
							}
						}    
						
						$id = $purchase->product_id;
					
						$_post = get_post($id);
						if(is_null($_post)) return;
						if($_post->post_type == 'sd_product') $obj = new SDProduct($id);
						else return;
						
						$productObj = new stdClass();
						if(isset($obj->file) && !in_array($obj->file, $tmp_arr)){ 
							$productObj->title = sd_product_title($obj);
							$productObj->link  = $obj->file;
							$urls[] = $productObj;
							$tmp_arr[] = $obj->file;
						}
					}
					
					if(count($urls)){
						foreach($urls as $url){
							$download_link = sd_copy_download_links($url->link);
							if( $download_link !== $url->link ) $download_link = SD_H_URL.'?sd_action=f-download'.( ( isset( $_SESSION[ 'sd_user_email' ] ) ) ? '&sd_user_email='.$_SESSION[ 'sd_user_email' ] : '' ).'&f='.$download_link.( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ?  '&purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );
							$download_links_str .= '<div> <a href="'.$download_link.'">'.$url->title.'</a></div>';
						}
					}
					
					if(empty($download_links_str)){
						$download_links_str = __('The list of purchased products is empty', SD_TEXT_DOMAIN);
					}
					return $download_links_str;
				} // End purchase checking	
				
			}
		}else{
			global $sd_errors;
			$error = ( !empty( $_REQUEST[ 'error_mssg' ] ) ) ? $_REQUEST[ 'error_mssg' ] : '';
			if( !empty( $_SESSION[ 'sd_user_email' ] ) ){
				$error .= '<li>'.implode( '</li><li>', $sd_errors ).'</li>';
			}
			$the_content .= ( !empty( $error ) )  ? '<div class="sd-error-mssg"><ul>'.$error.'</ul></div>' : '';				
			if( get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) ){
				$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); 
				$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).( ( isset( $_REQUEST[ 'purchase_id' ] ) ) ? 'purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );	
				$the_content .= '
					<form action="'.$dlurl.'" method="POST" >
						<div style="text-align:center;">
							<div>
								'.__( 'Type the email address used to purchase our products', SD_TEXT_DOMAIN ).'
							</div>
							<div>
								<input type="text" name="sd_user_email" /> <input type="submit" value="Get Products" />
							</div>	
						</div>
					</form>
				';
			}
		}	
		return $the_content;
			
	} //sd_generate_downloads
	
	function sd_download_file(){
		global $wpdb, $sd_errors;
		if( isset( $_REQUEST[ 'f' ] ) && sd_check_download_permissions() ){
			if( isset( $_REQUEST[ 'purchase_id' ]) )
			header( 'Content-Disposition: attachment; filename="'.$_REQUEST[ 'f' ].'"' );
			readfile( SD_DOWNLOAD.'/'.$_REQUEST[ 'f' ] );
		}else{
			$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); 
			$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).'error_mssg='.urlencode( '<li>'.implode( '</li><li>', $sd_errors ).'</li>' ).( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ? '&purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );
			header( 'location: '.$dlurl );
		}
	} // End ms_download_file
?>