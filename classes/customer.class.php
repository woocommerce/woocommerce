<?php
/**
 * Customer
 * 
 * The WooCommerce custoemr class handles storage of the current customer's data, such as location.
 *
 * @class woocommerce_customer
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_customer {
	
	/** constructor */
	function __construct() {
		
		if ( !isset($_SESSION['customer']) ) :
			
			$default = get_option('woocommerce_default_country');
        	if (strstr($default, ':')) :
        		$country = current(explode(':', $default));
        		$state = end(explode(':', $default));
        	else :
        		$country = $default;
        		$state = '';
        	endif;
			$data = array(
				'country' 			=> $country,
				'state' 			=> $state,
				'postcode' 			=> '',
				'shipping_country' 	=> $country,
				'shipping_state' 	=> $state,
				'shipping_postcode' => '',
				'is_vat_exempt' 	=> false
			);			
			$_SESSION['customer'] = $data;
			$_SESSION['calculated_shipping'] = false;
			
		endif;
		
	}
    
    /** Is customer outside base country? */
	function is_customer_outside_base() {
		if (isset($_SESSION['customer']['country'])) :
			
			$default = get_option('woocommerce_default_country');
        	if (strstr($default, ':')) :
        		$country = current(explode(':', $default));
        		$state = end(explode(':', $default));
        	else :
        		$country = $default;
        		$state = '';
        	endif;
        	
			if ($country!==$_SESSION['customer']['country']) return true;
			
		endif;
		return false;
	}
	
	/** Is customer VAT exempt? */
	function is_vat_exempt() {
		if (isset($_SESSION['customer']['is_vat_exempt']) && $_SESSION['customer']['is_vat_exempt']) return true;
		return false;
	}
	
	/** Gets the state from the current session */
	function get_state() {
		if (isset($_SESSION['customer']['state'])) return $_SESSION['customer']['state'];
	}
	
	/** Gets the country from the current session */
	function get_country() {
		if (isset($_SESSION['customer']['country'])) return $_SESSION['customer']['country'];
	}
	
	/** Gets the postcode from the current session */
	function get_postcode() {
		if (isset($_SESSION['customer']['postcode']) && $_SESSION['customer']['postcode'] !== false) return strtolower(str_replace(' ', '', $_SESSION['customer']['postcode']));
	}
	
	/** Gets the state from the current session */
	function get_shipping_state() {
		if (isset($_SESSION['customer']['shipping_state'])) return $_SESSION['customer']['shipping_state'];
	}
	
	/** Gets the country from the current session */
	function get_shipping_country() {
		if (isset($_SESSION['customer']['shipping_country'])) return $_SESSION['customer']['shipping_country'];
	}
	
	/** Gets the postcode from the current session */
	function get_shipping_postcode() {
		if (isset($_SESSION['customer']['shipping_postcode'])) return strtolower(str_replace(' ', '', $_SESSION['customer']['shipping_postcode']));
	}
	
	/** Sets session data for the location */
	function set_location( $country, $state, $postcode = '' ) {
		$data = (array) $_SESSION['customer'];
		
		$data['country'] = $country;
		$data['state'] = $state;
		$data['postcode'] = $postcode;
		
		$_SESSION['customer'] = $data;
	}
	
	/** Sets session data for the country */
	function set_country( $country ) {
		$_SESSION['customer']['country'] = $country;
	}
	
	/** Sets session data for the state */
	function set_state( $state ) {
		$_SESSION['customer']['state'] = $state;
	}
	
	/** Sets session data for the postcode */
	function set_postcode( $postcode ) {
		$_SESSION['customer']['postcode'] = $postcode;
	}
	
	/** Sets session data for the location */
	function set_shipping_location( $country, $state = '', $postcode = '' ) {
		$data = (array) $_SESSION['customer'];
		
		$data['shipping_country'] = $country;
		$data['shipping_state'] = $state;
		$data['shipping_postcode'] = $postcode;
		
		$_SESSION['customer'] = $data;
	}
	
	/** Sets session data for the country */
	function set_shipping_country( $country ) {
		$_SESSION['customer']['shipping_country'] = $country;
	}
	
	/** Sets session data for the state */
	function set_shipping_state( $state ) {
		$_SESSION['customer']['shipping_state'] = $state;
	}
	
	/** Sets session data for the postcode */
	function set_shipping_postcode( $postcode ) {
		$_SESSION['customer']['shipping_postcode'] = $postcode;
	}
	
	/** Sets session data for the tax exemption */
	function set_is_vat_exempt( $is_vat_exempt ) {
		$_SESSION['customer']['is_vat_exempt'] = $is_vat_exempt;
	}
	
	/**
	 * Gets a user's downloadable products if they are logged in
	 *
	 * @return   array	downloads	Array of downloadable products
	 */
	function get_downloadable_products() {
		
		global $wpdb;
		
		$downloads = array();
		
		if (is_user_logged_in()) :
			
			$user_info = get_userdata(get_current_user_id());

			$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."woocommerce_downloadable_product_permissions WHERE user_id = '%s';", get_current_user_id()) );
			
			if ($results) foreach ($results as $result) :
				
				if ($result->order_id>0) :
				
					$order = &new woocommerce_order( $result->order_id );
					
					if ( $order->status!='completed' && $order->status!='processing' ) continue;
						
					$product_post = get_post( $result->product_id );
	
					if ($product_post->post_type=='product_variation') :
						$_product = &new woocommerce_product_variation( $result->product_id );
					else :
						$_product = &new woocommerce_product( $result->product_id );
					endif;					
					
					if ($_product->exists) :
						$download_name = $_product->get_title();
					else :
						$download_name = '#' . $result->product_id;
					endif;
					
					$downloads[] = array(
						'download_url' => add_query_arg('download_file', $result->product_id, add_query_arg('order', $result->order_key, add_query_arg('email', $user_info->user_email, home_url()))),
						'product_id' => $result->product_id,
						'download_name' => $download_name,
						'order_id' => $order->id,
						'order_key' => $order->order_key,
						'downloads_remaining' => $result->downloads_remaining
					);
				
				endif;
				
			endforeach;
		
		endif;
		
		return apply_filters('woocommerce_customer_get_downloadable_products', $downloads);
		
	}
	
}