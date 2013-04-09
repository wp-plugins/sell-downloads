<?php

	echo 'Start IPN';

	$item_name = $_POST['item_name'];
	$item_number = $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
	$txn_id = $_POST['txn_id'];
	$receiver_email = $_POST['receiver_email'];
	$payer_email = $_POST['payer_email'];
	$payment_type = $_POST['payment_type'];

	if ($payment_status != 'Completed' && $payment_type != 'echeck') exit;
	if ($payment_type == 'echeck' && $payment_status == 'Completed') exit;
	
    $paypal_data = "";
	foreach ($_POST as $item => $value) $paypal_data .= $item."=".$value."\r\n";


    if(!isset($_GET['purchase_id'])) exit;
    $purchase_id = $_GET['purchase_id'];

    if(!isset($_GET['id'])) exit;
    $id = $_GET['id'];

    $_post = get_post($id);
    if(is_null($_post)) exit;
    
    if($_post->post_type == "sd_product") $obj = new SDProduct($id);
    else exit;
    
    if (!isset($obj->price) || abs($payment_amount - $obj->price) > 0.2) exit;
    if(sell_downloads_register_purchase($id, $purchase_id, $payer_email, $payment_amount, $paypal_data)) $obj->purchases++;
    
	$sd_notification_from_email 		= get_option('sd_notification_from_email', SD_NOTIFICATION_FROM_EMAIL);
	$sd_notification_to_email   		= get_option('sd_notification_to_email', SD_NOTIFICATION_TO_EMAIL);
	
	$sd_notification_to_payer_subject   = get_option('sd_notification_to_payer_subject', SD_NOTIFICATION_TO_PAYER_SUBJECT);
	$sd_notification_to_payer_message   = get_option('sd_notification_to_payer_message', SD_NOTIFICATION_TO_PAYER_MESSAGE);
	
	$sd_notification_to_seller_subject  = get_option('sd_notification_to_seller_subject', SD_NOTIFICATION_TO_SELLER_SUBJECT);
	$sd_notification_to_seller_message  = get_option('sd_notification_to_seller_message', SD_NOTIFICATION_TO_SELLER_MESSAGE);
	
	$information_payer = "Product: {$item_name}\n".
						 "Amount: {$payment_amount} {$payment_currency}\n".
						 "Download Link: ".SD_H_URL."?sd_download=download&purchase_id={$_GET['purchase_id']}\n";
						 
	$information_seller = "Product: {$item_name}\n".
						  "Amount: {$payment_amount} {$payment_currency}\n".
						  "Buyer Email: {$payer_email}\n".
						  "Download Link: ".SD_H_URL."?sd_download=download&purchase_id={$_GET['purchase_id']}\n";
						 
	$sd_notification_to_payer_message  = str_replace("%INFORMATION%", $information_payer, $sd_notification_to_payer_message);
	$sd_notification_to_seller_message = str_replace("%INFORMATION%", $information_seller, $sd_notification_to_seller_message);
	
	// Send email to payer
	wp_mail($payer_email, $sd_notification_to_payer_subject, $sd_notification_to_payer_message,
            "From: \"$sd_notification_from_email\" <$sd_notification_from_email>\r\n".
            "Content-Type: text/plain; charset=utf-8\n".
            "X-Mailer: PHP/" . phpversion());

    // Send email to seller
	wp_mail($sd_notification_to_email , $sd_notification_to_seller_subject, $sd_notification_to_seller_message,
			"From: \"$sd_notification_from_email\" <$sd_notification_from_email>\r\n".
			"Content-Type: text/plain; charset=utf-8\n".
			"X-Mailer: PHP/" . phpversion());

   echo 'OK';
   exit();
?>