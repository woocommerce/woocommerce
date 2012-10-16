<?php
/**
 * Local Pickup Shipping Method
 *
 * A simple shipping method allowing free pickup as a shipping method
 *
 * @class 		WC_Local_Pickup
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Local_Pickup extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id 			= 'local_pickup';
		$this->method_title = __( 'Local Pickup', 'woocommerce' );
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
		$this->title		= $this->settings['title'];
		$this->availability	= $this->settings['availability'];
		$this->countries	= $this->settings['countries'];

		// Actions
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( &$this, 'taxable_address' ) );
		add_action( 'woocommerce_shipping_method_chosen', array( &$this, 'method_chosen' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @return void
	 */
	function calculate_shipping() {
		$rate = array(
			'id' 		=> $this->id,
			'label' 	=> $this->title,
		);
		$this->add_rate($rate);
	}

	/**
	 * init_form_fields function.
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
    	global $woocommerce;
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable local pickup', 'woocommerce' ),
				'default' 		=> 'no'
			),
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'		=> __( 'Local Pickup', 'woocommerce' )
			),
			'availability' => array(
				'title' 		=> __( 'Method availability', 'woocommerce' ),
				'type' 			=> 'select',
				'default' 		=> 'all',
				'class'			=> 'availability',
				'options'		=> array(
					'all' 		=> __( 'All allowed countries', 'woocommerce' ),
					'specific' 	=> __( 'Specific Countries', 'woocommerce' )
				)
			),
			'countries' => array(
				'title' 		=> __( 'Specific Countries', 'woocommerce' ),
				'type' 			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default' 		=> '',
				'options'		=> $woocommerce->countries->countries
			),
			'apply_base_tax' => array(
				'title' 		=> __( 'Apply base tax rate', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'When this shipping method is chosen, apply the base tax rate rather than for the customer\'s given address.', 'woocommerce' ),
				'default' 		=> 'no'
			),
		);
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		global $woocommerce; ?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e( 'Local pickup is a simple method which allows the customer to pick up their order themselves.', 'woocommerce' ); ?></p>
		<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
    	</table> <?php
	}

	/**
	 * is_available function.
	 *
	 * @access public
	 * @param array $package
	 * @return bool
	 */
	function is_available( $package ) {
		global $woocommerce;
		$is_available = true;

		if ( $this->enabled == 'no' ) {
			$is_available = false;
		} else {
			$ship_to_countries = '';

			if ( $this->availability == 'specific' ) {
				$ship_to_countries = $this->countries;
			} elseif ( get_option( 'woocommerce_allowed_countries' ) == 'specific' ) {
				$ship_to_countries = get_option( 'woocommerce_specific_allowed_countries' );
			}

			if ( is_array( $ship_to_countries ) && ! in_array( $package['destination']['country'], $ship_to_countries ) ) {
				$is_available = false;
			}
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}
	
	
	/**
	 * taxable_address function.
	 * 
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	function taxable_address( $address ) {
		global $woocommerce;
		
		if ( ! empty( $woocommerce->session->chosen_shipping_method ) && $woocommerce->session->chosen_shipping_method == 'local_pickup' ) {
			if ( ! empty( $this->settings['apply_base_tax'] ) && $this->settings['apply_base_tax'] == 'yes' ) {
				
				$country 	= $woocommerce->countries->get_base_country();
				$state 		= $woocommerce->countries->get_base_state();
				
				$address = array( $country, $state, '', '' );				
			}
		}
		return $address;
	}
	
	/**
	 * Refresh totals when chosen so we can refresh the tax if we are using local pickup.
	 * 
	 * @access public
	 * @return void
	 */
	function method_chosen( $method ) {
		global $woocommerce;
		
		if ( $method == 'local_pickup' && ! empty( $this->settings['apply_base_tax'] ) && $this->settings['apply_base_tax'] == 'yes' ) {
			$woocommerce->cart->calculate_totals();
		}
	}
}

/**
 * add_local_pickup_method function.
 *
 * @package		WooCommerce/Classes/Shipping
 * @access public
 * @param array $methods
 * @return array
 */
function add_local_pickup_method($methods) {
	$methods[] = 'WC_Local_Pickup';
	return $methods;
}

add_filter('woocommerce_shipping_methods','add_local_pickup_method');