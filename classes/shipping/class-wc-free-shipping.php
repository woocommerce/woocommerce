<?php
/**
 * Free Shipping Method
 *
 * A simple shipping method for free shipping
 *
 * @class 		WC_Free_Shipping
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Free_Shipping extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        $this->id 			= 'free_shipping';
        $this->method_title = __('Free Shipping', 'woocommerce');
		$this->init();
    }

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {
		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
        $this->enabled		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->min_amount 	= $this->settings['min_amount'];
		$this->availability = $this->settings['availability'];
		$this->countries 	= $this->settings['countries'];
		$this->requires_coupon 	= $this->settings['requires_coupon'];

		// Actions
		add_action('woocommerce_update_options_shipping_'.$this->id, array(&$this, 'process_admin_options'));
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	global $woocommerce;

    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable Free Shipping', 'woocommerce' ),
							'default' 		=> 'yes'
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'Free Shipping', 'woocommerce' )
						),
			'min_amount' => array(
							'title' 		=> __( 'Minimum Order Amount', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __('Users will need to spend this amount to get free shipping. Leave blank to disable.', 'woocommerce'),
							'default' 		=> ''
						),
			'requires_coupon' => array(
							'title' 		=> __( 'Coupon', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Free shipping requires a free shipping coupon', 'woocommerce' ),
							'description' 	=> __('Users will need to enter a valid free shipping coupon code to use this method. If a coupon is used, the minimum order amount will be ignored.', 'woocommerce'),
							'default' 		=> 'no'
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __('All allowed countries', 'woocommerce'),
								'specific' 	=> __('Specific Countries', 'woocommerce')
							)
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						)
			);

    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Free Shipping', 'woocommerce'); ?></h3>
    	<p><?php _e('Free Shipping - does what it says on the tin.', 'woocommerce'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    }


    /**
     * is_available function.
     *
     * @access public
     * @param mixed $package
     * @return bool
     */
    function is_available( $package ) {
    	global $woocommerce;

    	if ( $this->enabled == "no" ) return false;

		$ship_to_countries = '';

		if ( $this->availability == 'specific' ) {
			$ship_to_countries = $this->countries;
		} else {
			if ( get_option('woocommerce_allowed_countries') == 'specific' )
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
		}

		if ( is_array( $ship_to_countries ) )
			if ( ! in_array( $package['destination']['country'], $ship_to_countries ) )
				return false;

		// Enabled logic
		$is_available = true;

		if ( $this->requires_coupon == "yes" ) {

			if ( $woocommerce->cart->applied_coupons ) {
				foreach ($woocommerce->cart->applied_coupons as $code) {
					$coupon = new WC_Coupon( $code );

					if ( $coupon->enable_free_shipping() )
						return true;
				}
			}

			// No coupon found, as it stands, free shipping is disabled
			$is_available = false;

		}

		if ( isset( $woocommerce->cart->cart_contents_total ) && ! empty( $this->min_amount ) ) {

			if ( $woocommerce->cart->prices_include_tax )
				$total = $woocommerce->cart->tax_total + $woocommerce->cart->cart_contents_total;
			else
				$total = $woocommerce->cart->cart_contents_total;

			if ( $this->min_amount > $total )
				$is_available = false;
			else
				$is_available = true;

		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available );
    }


    /**
     * calculate_shipping function.
     *
     * @access public
     * @return array
     */
    function calculate_shipping() {
    	$args = array(
    		'id' 	=> $this->id,
    		'label' => $this->title,
    		'cost' 	=> 0,
    		'taxes' => false
    	);
    	$this->add_rate( $args );
    }

}

/**
 * add_free_shipping_method function.
 *
 * @package		WooCommerce/Classes/Shipping
 * @access public
 * @param array $methods
 * @return array
 */
function add_free_shipping_method( $methods ) {
	$methods[] = 'WC_Free_Shipping';
	return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_free_shipping_method' );