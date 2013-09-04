<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Google Analytics Integration
 *
 * Allows tracking code to be inserted into store pages.
 *
 * @class 		WC_Google_Analytics
 * @extends		WC_Integration
 * @version		1.6.4
 * @package		WooCommerce/Classes/Integrations
 * @author 		WooThemes
 */
class WC_Google_Analytics extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
        $this->id					= 'google_analytics';
        $this->method_title     	= __( 'Google Analytics', 'woocommerce' );
        $this->method_description	= __( 'Google Analytics is a free service offered by Google that generates detailed statistics about the visitors to a website.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->ga_id 							= $this->get_option( 'ga_id' );
		$this->ga_set_domain_name               = $this->get_option( 'ga_set_domain_name' );
		$this->ga_standard_tracking_enabled 	= $this->get_option( 'ga_standard_tracking_enabled' );
		$this->ga_ecommerce_tracking_enabled 	= $this->get_option( 'ga_ecommerce_tracking_enabled' );
		$this->ga_event_tracking_enabled		= $this->get_option( 'ga_event_tracking_enabled' );

		// Actions
		add_action( 'woocommerce_update_options_integration_google_analytics', array( $this, 'process_admin_options') );

		// Tracking code
		add_action( 'wp_footer', array( $this, 'google_tracking_code' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'ecommerce_tracking_code' ) );

		// Event tracking code
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_to_cart' ) );
		add_action( 'wp_footer', array( $this, 'loop_add_to_cart' ) );
    }


    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'ga_id' => array(
				'title' 			=> __( 'Google Analytics ID', 'woocommerce' ),
				'description' 		=> __( 'Log into your google analytics account to find your ID. e.g. <code>UA-XXXXX-X</code>', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> get_option('woocommerce_ga_id') // Backwards compat
			),
			'ga_set_domain_name' => array(
				'title' 			=> __( 'Set Domain Name', 'woocommerce' ),
				'description' 		=> sprintf( __( '(Optional) Sets the <code>_setDomainName</code> variable. <a href="%s">See here for more information</a>.', 'woocommerce' ), 'https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingSite#multipleDomains' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'ga_standard_tracking_enabled' => array(
				'title' 			=> __( 'Tracking code', 'woocommerce' ),
				'label' 			=> __( 'Add tracking code to your site\'s footer. You don\'t need to enable this if using a 3rd party analytics plugin.', 'woocommerce' ),
				'type' 				=> 'checkbox',
				'checkboxgroup'		=> 'start',
				'default' 			=> get_option('woocommerce_ga_standard_tracking_enabled') ? get_option('woocommerce_ga_standard_tracking_enabled') : 'no'  // Backwards compat
			),
			'ga_ecommerce_tracking_enabled' => array(
				'label' 			=> __( 'Add eCommerce tracking code to the thankyou page', 'woocommerce' ),
				'type' 				=> 'checkbox',
				'checkboxgroup'		=> '',
				'default' 			=> get_option('woocommerce_ga_ecommerce_tracking_enabled') ? get_option('woocommerce_ga_ecommerce_tracking_enabled') : 'no'  // Backwards compat
			),
			'ga_event_tracking_enabled' => array(
				'label' 			=> __( 'Add event tracking code for add to cart actions', 'woocommerce' ),
				'type' 				=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'default' 			=> 'no'
			)
		);

    } // End init_form_fields()


	/**
	 * Google Analytics standard tracking
	 *
	 * @access public
	 * @return void
	 */
	function google_tracking_code() {
		global $woocommerce;

		if ( is_admin() || current_user_can('manage_options') || $this->ga_standard_tracking_enabled == "no" ) return;

		$tracking_id = $this->ga_id;

		if ( ! $tracking_id ) return;

		$loggedin 	= ( is_user_logged_in() ) ? 'yes' : 'no';
		if ( is_user_logged_in() ) {
			$user_id 		= get_current_user_id();
			$current_user 	= get_user_by('id', $user_id);
			$username 		= $current_user->user_login;
		} else {
			$user_id 		= '';
			$username 		= __( 'Guest', 'woocommerce' );
		}

		if ( ! empty( $this->ga_set_domain_name ) )
			$set_domain_name = "['_setDomainName', '" . esc_js( $this->ga_set_domain_name ) . "'],\n";
		else
			$set_domain_name = '';

		echo "<script type='text/javascript'>

			var _gaq = _gaq || [];
			_gaq.push(
				['_setAccount', '" . esc_js( $tracking_id ) . "'], " . $set_domain_name . "
				['_setCustomVar', 1, 'logged-in', '" . $loggedin . "', 1],
				['_trackPageview']
			);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();

		</script>";
	}


	/**
	 * Google Analytics eCommerce tracking
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	function ecommerce_tracking_code( $order_id ) {
		global $woocommerce;

		if ( $this->ga_ecommerce_tracking_enabled == "no" || current_user_can('manage_options') || get_post_meta( $order_id, '_ga_tracked', true ) == 1 )
			return;

		$tracking_id = $this->ga_id;

		if ( ! $tracking_id ) return;

		// Doing eCommerce tracking so unhook standard tracking from the footer
		remove_action( 'wp_footer', array( $this, 'google_tracking_code' ) );

		// Get the order and output tracking code
		$order = new WC_Order( $order_id );

		$loggedin = is_user_logged_in() ? 'yes' : 'no';

		if ( is_user_logged_in() ) {
			$user_id 		= get_current_user_id();
			$current_user 	= get_user_by('id', $user_id);
			$username 		= $current_user->user_login;
		} else {
			$user_id 		= '';
			$username 		= __( 'Guest', 'woocommerce' );
		}

		if ( ! empty( $this->ga_set_domain_name ) )
			$set_domain_name = "['_setDomainName', '" . esc_js( $this->ga_set_domain_name ) . "'],";
		else
			$set_domain_name = '';

		$code = "
			var _gaq = _gaq || [];

			_gaq.push(
				['_setAccount', '" . esc_js( $tracking_id ) . "'], " . $set_domain_name . "
				['_setCustomVar', 1, 'logged-in', '" . esc_js( $loggedin ) . "', 1],
				['_trackPageview']
			);

			_gaq.push(['_addTrans',
				'" . esc_js( $order->get_order_number() ) . "', // order ID - required
				'" . esc_js( get_bloginfo( 'name' ) ) . "',  	// affiliation or store name
				'" . esc_js( $order->get_total() ) . "',   	    // total - required
				'" . esc_js( $order->get_total_tax() ) . "',    // tax
				'" . esc_js( $order->get_shipping() ) . "',	    // shipping
				'" . esc_js( $order->billing_city ) . "',       // city
				'" . esc_js( $order->billing_state ) . "',      // state or province
				'" . esc_js( $order->billing_country ) . "'     // country
			]);
		";

		// Order items
		if ( $order->get_items() ) {
			foreach ( $order->get_items() as $item ) {
				$_product = $order->get_product_from_item( $item );

				$code .= "_gaq.push(['_addItem',";
				$code .= "'" . esc_js( $order->get_order_number() ) . "',";
				$code .= "'" . esc_js( $_product->get_sku() ? __( 'SKU:', 'woocommerce' ) . ' ' . $_product->get_sku() : $_product->id ) . "',";
				$code .= "'" . esc_js( $item['name'] ) . "',";

				if ( isset( $_product->variation_data ) ) {

					$code .= "'" . esc_js( woocommerce_get_formatted_variation( $_product->variation_data, true ) ) . "',";

				} else {
					$out = array();
					$categories = get_the_terms($_product->id, 'product_cat');
					if ( $categories ) {
						foreach ( $categories as $category ){
							$out[] = $category->name;
						}
					}
					$code .= "'" . esc_js( join( "/", $out) ) . "',";
				}

				$code .= "'" . esc_js( $order->get_item_total( $item, true, true ) ) . "',";
				$code .= "'" . esc_js( $item['qty'] ) . "'";
				$code .= "]);";
			}
		}

		$code .= "
			_gaq.push(['_trackTrans']); 					// submits transaction to the Analytics servers

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		";

		echo '<script type="text/javascript">' . $code . '</script>';

		update_post_meta( $order_id, '_ga_tracked', 1 );
	}


	/**
	 * Google Analytics event tracking for single product add to cart
	 *
	 * @access public
	 * @return void
	 */
	function add_to_cart() {

		if ( $this->disable_tracking( $this->ga_event_tracking_enabled ) ) return;
		if ( ! is_single() ) return;

		global $product;

		$parameters = array();
		// Add single quotes to allow jQuery to be substituted into _trackEvent parameters
		$parameters['category'] = "'" . __( 'Products', 'woocommerce' ) . "'";
		$parameters['action'] = "'" . __( 'Add to cart', 'woocommerce' ) . "'";
		$parameters['label'] = "'" . esc_js( $product->get_sku() ? __('SKU:', 'woocommerce') . ' ' . $product->get_sku() : "#" . $product->id ) . "'";

		$this->event_tracking_code( $parameters, '.single_add_to_cart_button' );
	}


	/**
	 * Google Analytics event tracking for loop add to cart
	 *
	 * @access public
	 * @return void
	 */
	function loop_add_to_cart() {

		if ( $this->disable_tracking( $this->ga_event_tracking_enabled ) ) return;

		$parameters = array();
		// Add single quotes to allow jQuery to be substituted into _trackEvent parameters
		$parameters['category'] = "'" . __( 'Products', 'woocommerce' ) . "'";
		$parameters['action'] 	= "'" . __( 'Add to Cart', 'woocommerce' ) . "'";
		$parameters['label'] 	= "($(this).data('product_sku')) ? ('SKU: ' + $(this).data('product_sku')) : ('#' + $(this).data('product_id'))"; // Product SKU or ID

		$this->event_tracking_code( $parameters, '.add_to_cart_button:not(.product_type_variable, .product_type_grouped)' );
	}


	/**
	 * Google Analytics event tracking for loop add to cart
	 *
	 * @access private
	 * @param mixed $parameters associative array of _trackEvent parameters
	 * @param mixed $selector jQuery selector for binding click event
	 * @return void
	 */
	private function event_tracking_code( $parameters, $selector ) {
		global $woocommerce;

		$parameters = apply_filters( 'woocommerce_ga_event_tracking_parameters', $parameters );

		$woocommerce->add_inline_js("
			$('" . $selector . "').click(function() {
				" . sprintf( "_gaq.push(['_trackEvent', %s, %s, %s]);", $parameters['category'], $parameters['action'], $parameters['label'] ) . "
			});
		");
	}


	/**
	 * Check if tracking is disabled
	 *
	 * @access private
	 * @param mixed $type
	 * @return bool
	 */
	private function disable_tracking( $type ) {

		if ( is_admin() || current_user_can( 'manage_options' ) || ( ! $this->ga_id ) || 'no' == $type ) return true;

	}

}


/**
 * Add the integration to WooCommerce.
 *
 * @package		WooCommerce/Classes/Integrations
 * @access public
 * @param array $integrations
 * @return array
 */
function add_google_analytics_integration( $integrations ) {
	$integrations[] = 'WC_Google_Analytics';
	return $integrations;
}

add_filter('woocommerce_integrations', 'add_google_analytics_integration' );
