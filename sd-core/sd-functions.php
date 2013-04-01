<?php

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
	
	function sell_downloads_is_200($url){
		$options['http'] = array(
			'method' => "HEAD",
			'ignore_errors' => 1,
			'max_redirects' => 0
		);
		$body = file_get_contents($url, NULL, stream_context_create($options));
		sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $code);
		return $code === 200;
	} // sell_downloads_is_200
	
	function sd_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = md5($file).'.'.$ext;
		$file_path = SD_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($file_path))
			return SD_URL.'/sd-downloads/'.$new_file_name.'?param='.$rand;
		
		if(file_put_contents($file_path, file_get_contents($file))){
			return SD_URL.'/sd-downloads/'.$new_file_name.'?param='.$rand;
		}
		return false;
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
					unlink($file_name);
				}
			}
		}
		$d->close();
	} // End sd_remove_download_links
	
	function sd_product_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	} // End sd_product_title
	
    function sd_generate_downloads_title($the_title){
        global $id;
        if($id)
            return __('Download the purchased products', SD_TEXT_DOMAIN);
        else
            return $the_title;
    }
    
	function sd_generate_downloads($the_content){
    	global $wpdb, $download_links_str, $id;
		if($id){
            sd_remove_download_links();
            
            $purchase_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
            
            if($purchase_rows){ // Exists the purchase
                $urls = array();
                $tmp_arr = array();
                foreach($purchase_rows as $purchase){
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
                
                $download_links_str = '';
                
                if(count($urls)){
                    foreach($urls as $url){
                        $download_link = sd_copy_download_links($url->link);
                        if($download_link){
                            $download_links_str .= '<div> <a href="'.$download_link.'">'.$url->title.'</a></div>';
                        }
                    }
                }
                
                if(empty($download_links_str)){
                    $download_links_str = __('The list of purchased products is empty', SD_TEXT_DOMAIN);
                }
                include_once(dirname(__FILE__).'/../sd-templates/sd-donwload-page-template.php');
            } // End purchase checking	
            return '';
        }else{
            return $the_content;
        }    
	} //sd_generate_downloads
	
?>