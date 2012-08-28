<?php
/**
 * Customer
 *
 * The WooCommerce customer class handles storage of the current customer's data, such as location.
 *
 * @class 		WC_Customer
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Customer {

	/**
	 * Constructor for the customer class loads the customer from the PHP session.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		if ( ! isset( $_SESSION['customer'] ) ) {

			$default = get_option('woocommerce_default_country');

        	if ( strstr( $default, ':' ) ) {
        		$country = current( explode( ':', $default ) );
        		$state = end( explode( ':', $default ) );
        	} else {
        		$country = $default;
        		$state = '';
        	}

			$data = array(
				'country' 			=> $country,
				'state' 			=> '',
				'postcode' 			=> '',
				'shipping_country' 	=> $country,
				'shipping_state' 	=> '',
				'shipping_postcode' => '',
				'is_vat_exempt' 	=> false
			);
			$_SESSION['customer'] = $data;
			$_SESSION['calculated_shipping'] = false;
		}
	}


	/**
	 * has_calculated_shipping function.
	 *
	 * @access public
	 * @return bool
	 */
	function has_calculated_shipping() {
		return ( ! empty( $_SESSION['calculated_shipping'] ) && $_SESSION['calculated_shipping'] ) ? true : false;
	}


	/**
	 * Set customer address to match shop base address.
	 *
	 * @access public
	 * @return void
	 */
	function set_to_base() {
		$default = get_option('woocommerce_default_country');
    	if (strstr($default, ':')) :
    		$country = current(explode(':', $default));
    		$state = end(explode(':', $default));
    	else :
    		$country = $default;
    		$state = '';
    	endif;
    	$_SESSION['customer']['country'] = $country;
    	$_SESSION['customer']['state'] = $state;
    	$_SESSION['customer']['postcode'] = '';
	}


	/**
	 * Set customer shipping address to base address.
	 *
	 * @access public
	 * @return void
	 */
	function set_shipping_to_base() {
		$default = get_option('woocommerce_default_country');
    	if (strstr($default, ':')) :
    		$country = current(explode(':', $default));
    		$state = end(explode(':', $default));
    	else :
    		$country = $default;
    		$state = '';
    	endif;
    	$_SESSION['customer']['shipping_country'] = $country;
    	$_SESSION['customer']['shipping_state'] = $state;
    	$_SESSION['customer']['shipping_postcode'] = '';
	}


	/**
	 * Is customer outside base country?
	 *
	 * @access public
	 * @return bool
	 */
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

			if ($country!==$_SESSION['customer']['shipping_country']) return true;
			if ($state && $state!==$_SESSION['customer']['shipping_state']) return true;

		endif;
		return false;
	}


	/**
	 * Is customer VAT exempt?
	 *
	 * @access public
	 * @return bool
	 */
	function is_vat_exempt() {
		return ( isset( $_SESSION['customer']['is_vat_exempt'] ) && $_SESSION['customer']['is_vat_exempt'] ) ? true : false;
	}


	/**
	 * Gets the state from the current session.
	 *
	 * @access public
	 * @return string
	 */
	function get_state() {
		if (isset($_SESSION['customer']['state'])) return $_SESSION['customer']['state'];
	}


	/**
	 * Gets the country from the current session
	 *
	 * @access public
	 * @return string
	 */
	function get_country() {
		if (isset($_SESSION['customer']['country'])) return $_SESSION['customer']['country'];
	}


	/**
	 * Gets the postcode from the current session.
	 *
	 * @access public
	 * @return string
	 */
	function get_postcode() {
		global $woocommerce;
		$validation = $woocommerce->validation();
		if (isset($_SESSION['customer']['postcode']) && $_SESSION['customer']['postcode'] !== false) return $validation->format_postcode( $_SESSION['customer']['postcode'], $this->get_country());
	}


	/**
	 * Gets the state from the current session.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_state() {
		if (isset($_SESSION['customer']['shipping_state'])) return $_SESSION['customer']['shipping_state'];
	}


	/**
	 * Gets the country from the current session.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_country() {
		if (isset($_SESSION['customer']['shipping_country'])) return $_SESSION['customer']['shipping_country'];
	}


	/**
	 * Gets the postcode from the current session.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_postcode() {
		global $woocommerce;
		$validation = $woocommerce->validation();
		if (isset($_SESSION['customer']['shipping_postcode'])) return $validation->format_postcode( $_SESSION['customer']['shipping_postcode'], $this->get_shipping_country());
	}


	/**
	 * Sets session data for the location.
	 *
	 * @access public
	 * @param mixed $country
	 * @param mixed $state
	 * @param string $postcode (default: '')
	 * @return void
	 */
	function set_location( $country, $state, $postcode = '' ) {
		$data = (array) $_SESSION['customer'];

		$data['country'] = $country;
		$data['state'] = $state;
		$data['postcode'] = $postcode;

		$_SESSION['customer'] = $data;
	}


	/**
	 * Sets session data for the country.
	 *
	 * @access public
	 * @param mixed $country
	 * @return void
	 */
	function set_country( $country ) {
		$_SESSION['customer']['country'] = $country;
	}


	/**
	 * Sets session data for the state.
	 *
	 * @access public
	 * @param mixed $state
	 * @return void
	 */
	function set_state( $state ) {
		$_SESSION['customer']['state'] = $state;
	}


	/**
	 * Sets session data for the postcode.
	 *
	 * @access public
	 * @param mixed $postcode
	 * @return void
	 */
	function set_postcode( $postcode ) {
		$_SESSION['customer']['postcode'] = $postcode;
	}


	/**
	 * Sets session data for the location.
	 *
	 * @access public
	 * @param mixed $country
	 * @param string $state (default: '')
	 * @param string $postcode (default: '')
	 * @return void
	 */
	function set_shipping_location( $country, $state = '', $postcode = '' ) {
		$data = (array) $_SESSION['customer'];

		$data['shipping_country'] = $country;
		$data['shipping_state'] = $state;
		$data['shipping_postcode'] = $postcode;

		$_SESSION['customer'] = $data;
	}


	/**
	 * Sets session data for the country.
	 *
	 * @access public
	 * @param mixed $country
	 * @return void
	 */
	function set_shipping_country( $country ) {
		$_SESSION['customer']['shipping_country'] = $country;
	}


	/**
	 * Sets session data for the state.
	 *
	 * @access public
	 * @param mixed $state
	 * @return void
	 */
	function set_shipping_state( $state ) {
		$_SESSION['customer']['shipping_state'] = $state;
	}


	/**
	 * Sets session data for the postcode.
	 *
	 * @access public
	 * @param mixed $postcode
	 * @return void
	 */
	function set_shipping_postcode( $postcode ) {
		$_SESSION['customer']['shipping_postcode'] = $postcode;
	}


	/**
	 * Sets session data for the tax exemption.
	 *
	 * @access public
	 * @param mixed $is_vat_exempt
	 * @return void
	 */
	function set_is_vat_exempt( $is_vat_exempt ) {
		$_SESSION['customer']['is_vat_exempt'] = $is_vat_exempt;
	}


	/**
	 * Gets a user's downloadable products if they are logged in.
	 *
	 * @access public
	 * @return array Array of downloadable products
	 */
	function get_downloadable_products() {

		global $wpdb;

		$downloads = array();

		if (is_user_logged_in()) :

			$user_info = get_userdata(get_current_user_id());

			$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."woocommerce_downloadable_product_permissions WHERE user_id = '%s' ORDER BY order_id, product_id, download_id", get_current_user_id()) );
			
			$_product = null;
			$order = null;
			$file_number = 0;
			if ($results) foreach ($results as $result) :

				if ($result->order_id>0) :

					if ( ! $order || $order->id != $result->order_id ) {
						// new order
						$order = new WC_Order( $result->order_id );
						$_product = null;
					}

					// order exists and downloads permitted?
					if ( ! $order->id || ! $order->is_download_permitted() ) continue;

					if ( ! $_product || $_product->id != $result->product_id ) :
						// new product
						$file_number = 0;
						$_product = new WC_Product( $result->product_id );
					endif;

					if ( ! $_product->exists() ) continue;
					
					if ( ! $_product->has_file( $result->download_id ) ) continue;

					// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files
					$download_name = $_product->get_title() . ( $file_number > 0 ? ' &mdash; ' . sprintf( __( 'File %d', 'woocommerce' ), $file_number + 1 ) : '' );
					if ( $file_number == 1 ) $downloads[ count( $downloads ) - 1 ]['download_name'] .= ' &mdash; ' . sprintf( __( 'File %d', 'woocommerce' ), $file_number );

					$downloads[] = array(
						'download_url' => add_query_arg( array( 'download_file' => $result->product_id, 'order' => $result->order_key, 'email' => $result->user_email, 'key' => $result->download_id ), trailingslashit( home_url() ) ),
						'download_id' => $result->download_id,
						'product_id' => $result->product_id,
						'download_name' => $download_name,
						'order_id' => $order->id,
						'order_key' => $order->order_key,
						'downloads_remaining' => $result->downloads_remaining
					);

					$file_number++;

				endif;

			endforeach;

		endif;

		return apply_filters('woocommerce_customer_get_downloadable_products', $downloads);
	}
}

/**
 * woocommerce_customer class.
 *
 * @extends 	WC_Customer
 * @deprecated 	1.4
 * @package		WooCommerce/Classes
 */
class woocommerce_customer extends WC_Customer {
	public function __construct() {
		_deprecated_function( 'woocommerce_customer', '1.4', 'WC_Customer()' );
		parent::__construct();
	}
}