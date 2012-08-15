<?php
/**
 * Local Delivery Shipping Method
 *
 * A simple shipping method allowing local delivery as a shipping method
 *
 * @class 		WC_Local_Delivery
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Local_Delivery extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id			= 'local_delivery';
		$this->method_title = __('Local Delivery', 'woocommerce');
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
		$this->enabled		= empty( $this->settings['enabled'] ) ? 'no' : $this->settings['enabled'];
		$this->title		= empty( $this->settings['title'] ) ? '' : $this->settings['title'];
		$this->type 		= $this->settings['type'];
		$this->fee			= empty( $this->settings['fee'] ) ? '' : $this->settings['fee'];
		$this->type			= empty( $this->settings['type'] ) ? '' : $this->settings['type'];
		$this->codes		= empty( $this->settings['codes'] ) ? '' : $this->settings['codes'];
		$this->availability	= empty( $this->settings['availability'] ) ? '' : $this->settings['availability'];
		$this->countries	= empty( $this->settings['countries'] ) ? '' : $this->settings['countries'];

		add_action('woocommerce_update_options_shipping_'.$this->id, array(&$this, 'process_admin_options'));
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param array $package (default: array())
	 * @return void
	 */
	function calculate_shipping( $package = array() ) {
		global $woocommerce;

		$shipping_total = 0;
		$fee = ( trim( $this->fee ) == '' ) ? 0 : $this->fee;

		if ( $this->type =='fixed' ) 	$shipping_total 	= $this->fee;

		if ( $this->type =='percent' ) 	$shipping_total 	= $package['contents_cost'] * ( $this->fee / 100 );

		if ( $this->type == 'product' )	{
			foreach ( $woocommerce->cart->get_cart() as $item_id => $values ) {
				$_product = $values['data'];

				if ( $values['quantity'] > 0 && $_product->needs_shipping() )
					$shipping_total += $this->fee * $values['quantity'];
			}
		}

		$rate = array(
			'id' 		=> $this->id,
			'label' 	=> $this->title,
			'cost' 		=> $shipping_total
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
				'label' 		=> __( 'Enable local delivery', 'woocommerce' ),
				'default' 		=> 'no'
			),
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'		=> __( 'Local Delivery', 'woocommerce' )
			),
			'type' => array(
				'title' 		=> __( 'Fee Type', 'woocommerce' ),
				'type' 			=> 'select',
				'description' 	=> __( 'How to calculate delivery charges', 'woocommerce' ),
				'default' 		=> 'fixed',
				'options' 		=> array(
					'fixed' 	=> __('Fixed amount', 'woocommerce'),
					'percent'	=> __('Percentage of cart total', 'woocommerce'),
					'product'	=> __('Fixed amount per product', 'woocommerce'),
				),
			),
			'fee' => array(
				'title' 		=> __( 'Delivery Fee', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'What fee do you want to charge for local delivery, disregarded if you choose free. Leave blank to disable.', 'woocommerce' ),
				'default'		=> ''
			),
			'codes' => array(
				'title' 		=> __( 'Zip/Post Codes', 'woocommerce' ),
				'type' 			=> 'textarea',
				'description' 	=> __( 'What zip/post codes would you like to offer delivery to? Separate codes with a comma.', 'woocommerce' ),
				'default'		=> ''
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
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		global $woocommerce; ?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e('Local delivery is a simple shipping method for delivering orders locally.', 'woocommerce'); ?></p>
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

    	if ($this->enabled=="no") return false;

		// If post codes are listed, let's use them.
		$codes = '';
		if($this->codes != '') {
			foreach(explode(',',$this->codes) as $code) {
				$codes[] = $this->clean($code);
			}
		}

		if (is_array($codes))
			if ( ! in_array($this->clean( $package['destination']['postcode'] ), $codes))
				return false;

		// Either post codes not setup, or post codes are in array... so lefts check countries for backwards compatability.
		$ship_to_countries = '';
		if ($this->availability == 'specific') :
			$ship_to_countries = $this->countries;
		else :
			if (get_option('woocommerce_allowed_countries')=='specific') :
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
			endif;
		endif;

		if (is_array($ship_to_countries))
			if (!in_array( $package['destination']['country'] , $ship_to_countries))
				return false;

		// Yay! We passed!
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true );
    }


    /**
     * clean function.
     *
     * @access public
     * @param mixed $code
     * @return string
     */
    function clean($code) {
    	return str_replace('-','',sanitize_title($code));
    }

}

/**
 * add_local_delivery_method function.
 *
 * @package		WooCommerce/Classes/Shipping
 * @access public
 * @param array $methods
 * @return array
 */
function add_local_delivery_method($methods) {
	$methods[] = 'WC_Local_Delivery';
	return $methods;
}

add_filter('woocommerce_shipping_methods','add_local_delivery_method');