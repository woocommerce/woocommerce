<?php

### Update price if on sale

function jigoshop_update_sale_prices() {
	
	global $wpdb;
	
	// On Sale Products
	$on_sale = $wpdb->get_results("
		SELECT post_id FROM $wpdb->postmeta
		WHERE meta_key = 'sale_price_dates_from'
		AND meta_value < ".strtotime('NOW')."
	");
	if ($on_sale) foreach ($on_sale as $product) :
		
		$data = unserialize( get_post_meta($product, 'product_data', true) );
		$price = get_post_meta($product, 'price', true); 
	
		if ($data['sale_price'] && $price!==$data['sale_price']) update_post_meta($product, 'price', $data['sale_price']);
		
	endforeach;
	
	// Expired Sales
	$sale_expired = $wpdb->get_results("
		SELECT post_id FROM $wpdb->postmeta
		WHERE meta_key = 'sale_price_dates_to'
		AND meta_value < ".strtotime('NOW')."
	");
	if ($sale_expired) foreach ($sale_expired as $product) :
	
		$data = unserialize( get_post_meta($product, 'product_data', true) );
		$price = get_post_meta($product, 'price', true); 
	
		if ($data['regular_price'] && $price!==$data['regular_price']) update_post_meta($product, 'price', $data['regular_price']);
		
		// Sale has expired - clear the schedule boxes
		update_post_meta($product, 'sale_price_dates_from', '');
		update_post_meta($product, 'sale_price_dates_to', '');
		
	endforeach;
	
}

function jigoshop_update_sale_prices_schedule_check(){
	wp_schedule_event(time(), 'daily', 'jigoshop_update_sale_prices_schedule_check');
	update_option('jigoshop_update_sale_prices', 'yes');
}

if (get_option('jigoshop_update_sale_prices')!='yes') jigoshop_update_sale_prices_schedule_check();

add_action('jigoshop_update_sale_prices_schedule_check', 'jigoshop_update_sale_prices');