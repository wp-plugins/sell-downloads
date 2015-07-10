<?php

if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

    $ms_paypal_email = get_option('sd_paypal_email');

	$baseurl = SD_H_URL.'?sd_action=ipn';
	$returnurl = $GLOBALS['sell_downloads']->_sd_create_pages( 'sd-download-page', 'Download the purchased products' );
    $returnurl .= ( strpos( $returnurl, '?' ) === false ) ? '?' : '&';
	
	if( preg_match( '/^(http(s)?:\/\/[^\/\n]*)/i', SD_H_URL, $matches ) && strpos( $_SERVER['HTTP_REFERER'], $matches[ 0 ] ) ) $cancel_url = $_SERVER['HTTP_REFERER'];
    if(empty($cancel_url)) $cancel_url = SD_H_URL;
            
    if($ms_paypal_email){ // Check for sealer email
        mt_srand(sell_downloads_make_seed());
        $randval = mt_rand(1,999999);
            
        $purchase_id = md5($randval.uniqid('', true));
        
        if(isset($_POST['sd_product_id'])){
            $product = $wpdb->get_row($wpdb->prepare("SELECT posts_data.id as id, posts_data.price as price, posts.post_title as title FROM ".$wpdb->prefix.SDDB_POST_DATA." as posts_data INNER JOIN ".$wpdb->prefix."posts as posts ON posts.ID = posts_data.id WHERE posts_data.id=%d AND posts.post_status='publish' AND posts.post_type='sd_product'", $_POST['sd_product_id']));
            
            if($product){
                $amount = $product->price;
                $title = $product->title;
                $number = $product->id;
                $ID = $product->id;
            }else{
                $price = 0;
            }    
        }    
        
        if($amount > 0){
            $code = '<form action="https://www.paypal.com/cgi-bin/webscr" name="ppform'.$randval.'" method="post">'.
            '<input type="hidden" name="business" value="'.$ms_paypal_email.'" />'.
            '<input type="hidden" name="item_name" value="'.$title.'" />'.
            '<input type="hidden" name="item_number" value="Item Number '.$number.'" />'.
            '<input type="hidden" name="amount" value="'.$amount.'" />'.
            '<input type="hidden" name="currency_code" value="'.get_option('sd_paypal_currency', SD_PAYPAL_LANGUAGE).'" />'.
            '<input type="hidden" name="lc" value="'.get_option('sd_paypal_language', SD_PAYPAL_LANGUAGE).'" />'.
            ''.
            '<input type="hidden" name="return" value="'.$returnurl.'&purchase_id='.$purchase_id.'" />'.
            '<input type="hidden" name="cancel_return" value="'.$cancel_url.'" />'.
            '<input type="hidden" name="notify_url" value="'.$baseurl.'&id='.$ID.'&purchase_id='.$purchase_id.'&rtn_act=purchased_product_sell_downloads" />'.
            ''.
            '<input type="hidden" name="cmd" value="_xclick" />'.
            '<input type="hidden" name="page_style" value="Primary" />'.
            '<input type="hidden" name="no_shipping" value="1" />'.
            '<input type="hidden" name="no_note" value="1" />'.
            '<input type="hidden" name="bn" value="NetFactorSL_SI_Custom" />'.
            '<input type="hidden" name="ipn_test" value="1" />'.
            '</form>'.
            '<script type="text/javascript">document.ppform'.$randval.'.submit();'.'</script>';
            echo $code;
            exit;
        }
    }   
	
	header('location: '.$cancel_url);
?>