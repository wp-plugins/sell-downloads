<?php

	// Errors management
	$sd_errors = array();
	function sell_downloads_setError($error_text){
		global $sd_errors;
		$sd_errors[] = __($error_text, SD_TEXT_DOMAIN);
	}	

	if (!function_exists('sd_mime_content_type')) {
		function sd_mime_content_type($filename) {
			$idx = strtolower(end( explode( '.', $filename )) );
			$mimet = array(	'ai' =>'application/postscript',
				'3gp' =>'audio/3gpp',
				'flv' =>'video/x-flv',
				'aif' =>'audio/x-aiff',
				'aifc' =>'audio/x-aiff',
				'aiff' =>'audio/x-aiff',
				'asc' =>'text/plain',
				'atom' =>'application/atom+xml',
				'avi' =>'video/x-msvideo',
				'bcpio' =>'application/x-bcpio',
				'bmp' =>'image/bmp',
				'cdf' =>'application/x-netcdf',
				'cgm' =>'image/cgm',
				'cpio' =>'application/x-cpio',
				'cpt' =>'application/mac-compactpro',
				'crl' =>'application/x-pkcs7-crl',
				'crt' =>'application/x-x509-ca-cert',
				'csh' =>'application/x-csh',
				'css' =>'text/css',
				'dcr' =>'application/x-director',
				'dir' =>'application/x-director',
				'djv' =>'image/vnd.djvu',
				'djvu' =>'image/vnd.djvu',
				'doc' =>'application/msword',
				'dtd' =>'application/xml-dtd',
				'dvi' =>'application/x-dvi',
				'dxr' =>'application/x-director',
				'eps' =>'application/postscript',
				'etx' =>'text/x-setext',
				'ez' =>'application/andrew-inset',
				'gif' =>'image/gif',
				'gram' =>'application/srgs',
				'grxml' =>'application/srgs+xml',
				'gtar' =>'application/x-gtar',
				'hdf' =>'application/x-hdf',
				'hqx' =>'application/mac-binhex40',
				'html' =>'text/html',
				'html' =>'text/html',
				'ice' =>'x-conference/x-cooltalk',
				'ico' =>'image/x-icon',
				'ics' =>'text/calendar',
				'ief' =>'image/ief',
				'ifb' =>'text/calendar',
				'iges' =>'model/iges',
				'igs' =>'model/iges',
				'jpe' =>'image/jpeg',
				'jpeg' =>'image/jpeg',
				'jpg' =>'image/jpeg',
				'js' =>'application/x-javascript',
				'kar' =>'audio/midi',
				'latex' =>'application/x-latex',
				'm3u' =>'audio/x-mpegurl',
				'man' =>'application/x-troff-man',
				'mathml' =>'application/mathml+xml',
				'me' =>'application/x-troff-me',
				'mesh' =>'model/mesh',
				'm4a' =>'audio/x-m4a',
				'mid' =>'audio/midi',
				'midi' =>'audio/midi',
				'mif' =>'application/vnd.mif',
				'mov' =>'video/quicktime',
				'movie' =>'video/x-sgi-movie',
				'mp2' =>'audio/mpeg',
				'mp3' =>'audio/mpeg',
				'mp4' =>'video/mp4',
				'm4v' =>'video/x-m4v',
				'mpe' =>'video/mpeg',
				'mpeg' =>'video/mpeg',
				'mpg' =>'video/mpeg',
				'mpga' =>'audio/mpeg',
				'ms' =>'application/x-troff-ms',
				'msh' =>'model/mesh',
				'mxu m4u' =>'video/vnd.mpegurl',
				'nc' =>'application/x-netcdf',
				'oda' =>'application/oda',
				'ogg' =>'application/ogg',
				'pbm' =>'image/x-portable-bitmap',
				'pdb' =>'chemical/x-pdb',
				'pdf' =>'application/pdf',
				'pgm' =>'image/x-portable-graymap',
				'pgn' =>'application/x-chess-pgn',
				'php' =>'application/x-httpd-php',
				'php4' =>'application/x-httpd-php',
				'php3' =>'application/x-httpd-php',
				'phtml' =>'application/x-httpd-php',
				'phps' =>'application/x-httpd-php-source',
				'png' =>'image/png',
				'pnm' =>'image/x-portable-anymap',
				'ppm' =>'image/x-portable-pixmap',
				'ppt' =>'application/vnd.ms-powerpoint',
				'ps' =>'application/postscript',
				'qt' =>'video/quicktime',
				'ra' =>'audio/x-pn-realaudio',
				'ram' =>'audio/x-pn-realaudio',
				'ras' =>'image/x-cmu-raster',
				'rdf' =>'application/rdf+xml',
				'rgb' =>'image/x-rgb',
				'rm' =>'application/vnd.rn-realmedia',
				'roff' =>'application/x-troff',
				'rtf' =>'text/rtf',
				'rtx' =>'text/richtext',
				'sgm' =>'text/sgml',
				'sgml' =>'text/sgml',
				'sh' =>'application/x-sh',
				'shar' =>'application/x-shar',
				'shtml' =>'text/html',
				'silo' =>'model/mesh',
				'sit' =>'application/x-stuffit',
				'skd' =>'application/x-koan',
				'skm' =>'application/x-koan',
				'skp' =>'application/x-koan',
				'skt' =>'application/x-koan',
				'smi' =>'application/smil',
				'smil' =>'application/smil',
				'snd' =>'audio/basic',
				'spl' =>'application/x-futuresplash',
				'src' =>'application/x-wais-source',
				'sv4cpio' =>'application/x-sv4cpio',
				'sv4crc' =>'application/x-sv4crc',
				'svg' =>'image/svg+xml',
				'swf' =>'application/x-shockwave-flash',
				't' =>'application/x-troff',
				'tar' =>'application/x-tar',
				'tcl' =>'application/x-tcl',
				'tex' =>'application/x-tex',
				'texi' =>'application/x-texinfo',
				'texinfo' =>'application/x-texinfo',
				'tgz' =>'application/x-tar',
				'tif' =>'image/tiff',
				'tiff' =>'image/tiff',
				'tr' =>'application/x-troff',
				'tsv' =>'text/tab-separated-values',
				'txt' =>'text/plain',
				'ustar' =>'application/x-ustar',
				'vcd' =>'application/x-cdlink',
				'vrml' =>'model/vrml',
				'vxml' =>'application/voicexml+xml',
				'wav' =>'audio/x-wav',
				'wbmp' =>'image/vnd.wap.wbmp',
				'wbxml' =>'application/vnd.wap.wbxml',
				'wml' =>'text/vnd.wap.wml',
				'wmlc' =>'application/vnd.wap.wmlc',
				'wmlc' =>'application/vnd.wap.wmlc',
				'wmls' =>'text/vnd.wap.wmlscript',
				'wmlsc' =>'application/vnd.wap.wmlscriptc',
				'wmlsc' =>'application/vnd.wap.wmlscriptc',
				'wrl' =>'model/vrml',
				'xbm' =>'image/x-xbitmap',
				'xht' =>'application/xhtml+xml',
				'xhtml' =>'application/xhtml+xml',
				'xls' =>'application/vnd.ms-excel',
				'xml xsl' =>'application/xml',
				'xpm' =>'image/x-xpixmap',
				'xslt' =>'application/xslt+xml',
				'xul' =>'application/vnd.mozilla.xul+xml',
				'xwd' =>'image/x-xwindowdump',
				'xyz' =>'chemical/x-xyz',
				'zip' =>'application/zip'
			);

			if (isset( $mimet[$idx] )) {
				return $mimet[$idx];
			} else {
				return 'application/octet-stream';
			}
		}
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
			$file = urldecode( dirname( __FILE__ ).'/'.$path.$file );
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
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN DATEDIFF(NOW(), date) ELSE DATEDIFF(NOW(), checking_date) END AS days, downloads, id FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s AND email=%s ORDER BY checking_date DESC, date DESC', array( $_REQUEST[ 'purchase_id' ], $_SESSION[ 'sd_user_email' ] ) ) );
		}else{
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN DATEDIFF(NOW(), date) ELSE DATEDIFF(NOW(), checking_date) END AS days, downloads, id FROM '.$wpdb->prefix.SDDB_PURCHASE.' WHERE purchase_id=%s ORDER BY checking_date DESC, date DESC', array( $_REQUEST[ 'purchase_id' ] ) ) );
		}

		if( is_null( $data ) ){
			if( !isset( $_REQUEST[ 'timeout' ] ) )
            {
                sell_downloads_setError(
                    '<div id="sell_downloads_error_mssg"></div>
                    <script>
                        var timeout_text = "'.__( 'The store is processing the purchase. You will be redirected in', SD_TEXT_DOMAIN ).'";
                    </script>'
                );
            }
            else
            {
                sell_downloads_setError( 'There is no product associated with the entered data' );
            }    
			return false;
		}elseif( get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK) < $data->days ){ 
			sell_downloads_setError( 'The download link has expired, please contact to the vendor' );
			return false;	
		}elseif( get_option('sd_downloads_number', SD_DOWNLOADS_NUMBER) > 0 &&  get_option('sd_downloads_number', SD_DOWNLOADS_NUMBER) <= $data->downloads ){
			sell_downloads_setError( 'The number of downloads has reached its limit, please contact to the vendor' );
			return false;
		}
		
		if( isset( $_REQUEST[ 'f' ] ) )
		{
			$wpdb->query( $wpdb->prepare( 'UPDATE '.$wpdb->prefix.SDDB_PURCHASE.' SET downloads=downloads+1 WHERE id=%d', $data->id ) );
		}
		
		return true;
	} // End sd_check_download_permissions

	// Check if the PHP memory is sufficient
	function sell_downloads_check_memory( $files = array(), $forceLocal = false ){
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
			if( $forceLocal || ( $relative_path = sd_is_local( $file ) ) !== false ){
				if( $forceLocal )
				{
					$relative_path = dirname( __FILE__ ).'/../sd-downloads/'.$file;
				}
				
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
		$now = time();
		$dif = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;
		$d = dir(SD_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
			if($entry != '.' && $entry != '..' && $entry != '.htaccess'){
				$file_name = SD_DOWNLOAD.'/'.$entry;
				$date = filemtime($file_name);
				if($now-$date >= $dif){ // Delete file
					@unlink($file_name);
				}
			}
		}
		$d->close();
	} // End sd_remove_download_links

	function sd_product_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	} // End sd_product_title
	
	function sd_generate_downloads(){
		global $wpdb, $download_links_str, $id;
		$str = '';
		if( sd_check_download_permissions() ){
			if($id){
				sd_remove_download_links();
				$purchase_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
				
				if($purchase_rows){ // Exists the purchase
					$interval = get_option('sd_old_download_link', SD_OLD_DOWNLOAD_LINK)*86400;
					
					$urls = array();
					$tmp_arr = array();
					$download_links_str = '';
					
					foreach($purchase_rows as $purchase){
						$id = $purchase->product_id;
					
						$_post = get_post($id);
						if(is_null($_post)) return '';
						if($_post->post_type == 'sd_product') $obj = new SDProduct($id);
						else return '';
						
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
                    
                    $str .= $download_links_str;
				} // End purchase checking	
				
			}
		}else{
			global $sd_errors;
			$error = ( !empty( $_REQUEST[ 'error_mssg' ] ) ) ? $_REQUEST[ 'error_mssg' ] : '';
			if( (!get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) && !empty($sd_errors)) || !empty( $_SESSION[ 'sd_user_email' ] ) ){
				$error .= '<li>'.implode( '</li><li>', $sd_errors ).'</li>';
			}
			$str .= ( !empty( $error ) )  ? '<div class="sd-error-mssg"><ul>'.$error.'</ul></div>' : '';				
			if( get_option( 'sd_safe_download', SD_SAFE_DOWNLOAD ) ){
				$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); 
				$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).( ( isset( $_REQUEST[ 'purchase_id' ] ) ) ? 'purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );	
				$str .= '
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
		return $str;
			
	} //sd_generate_downloads
	
	function sd_download_file(){
		global $wpdb, $sd_errors;
		if( isset( $_REQUEST[ 'f' ] ) && sd_check_download_permissions() ){
			header( 'Content-Type: '.sd_mime_content_type( basename( $_REQUEST[ 'f' ] ) ) );
			header( 'Content-Disposition: attachment; filename="'.$_REQUEST[ 'f' ].'"' );
			
			if( sell_downloads_check_memory( array( $_REQUEST[ 'f' ] ), true ) )
			{
				readfile( SD_DOWNLOAD.'/'.$_REQUEST[ 'f' ] );
			}
			else			
			{
				@unlink( SD_DOWNLOAD.'/.htaccess');
				header( 'location:'.SD_URL.'/sd-downloads/'.basename( $_REQUEST[ 'f' ] ) );
			}
			
		}else{
			$dlurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' ); 
			$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ? 'purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );
			header( 'location: '.$dlurl );
		}
	} // End ms_download_file
	
	// From PayPal Data RAW
	/*
	  $fieldsArr, array( 'fields name' => 'alias', ... )
	  $selectAdd, used if is required complete the results like: COUNT(*) as count
	  $groupBy, array( 'alias', ... ) the alias used in the $fieldsArr parameter
	  $orderBy, array( 'alias' => 'direction', ... ) the alias used in the $fieldsArr parameter, direction = ASC or DESC
	*/
	function sd_getFromPayPalData( $fieldsArr, $selectAdd = '', $from = '', $where = '', $groupBy = array(), $orderBy = array(), $returnAs = 'json' ){
		global $wpdb;
		
		$_select = 'SELECT ';
		$_from = 'FROM '.$wpdb->prefix.SDDB_PURCHASE.( ( !empty( $from ) ) ? ','.$from : '' );
		$_where = 'WHERE '.( ( !empty( $where ) ) ? $where : 1 );
		$_groupBy = ( !empty( $groupBy ) ) ? 'GROUP BY ' : '';
		$_orderBy = ( !empty( $orderBy ) ) ? 'ORDER BY ' : '';
		
		$separator = '';
		foreach( $fieldsArr as $key => $value ){
			$length = strlen( $key )+1;
			$_select .= $separator.' 
							SUBSTRING(paypal_data, 
							LOCATE("'.$key.'", paypal_data)+'.$length.', 
							LOCATE("\r\n", paypal_data, LOCATE("'.$key.'", paypal_data))-(LOCATE("'.$key.'", paypal_data)+'.$length.')) AS '.$value; 
			$separator = ',';
		}
		
		if( !empty( $selectAdd ) ){
			$_select .= $separator.$selectAdd; 
		}
		
		$separator = '';
		foreach( $groupBy as $value ){
			$_groupBy .= $separator.$value;
			$separator = ',';
		}
		
		$separator = '';
		foreach( $orderBy as $key => $value ){
			$_orderBy .= $separator.$key.' '.$value;
			$separator = ',';
		}
		
		$query = $_select.' '.$_from.' '.$_where.' '.$_groupBy.' '.$_orderBy;
		$result = $wpdb->get_results( $query );
		
		if( !empty( $result ) ){
			switch( $returnAs ){
				case 'json':
					return json_encode( $result );
				break;
				default:
					return $result;
				break;
			}
		}
	} // End sd_getFromPayPalData
?>