<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Local Delivery Shipping Method
 *
 * A simple shipping method allowing local delivery as a shipping method
 *
 * @class 		WC_Shipping_Local_Delivery
 * @version		2.3.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Local_Delivery extends WC_Shipping_Local_Pickup {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'local_delivery';
		$this->method_title       = __( 'Local Delivery', 'woocommerce' );
		$this->method_description = __( 'Local delivery is a simple shipping method for delivering orders locally.', 'woocommerce' );
		$this->init();
	}

	/**
	 * init function.
	 */
	public function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->type         = $this->get_option( 'type' );
		$this->fee          = $this->get_option( 'fee' );
		$this->type         = $this->get_option( 'type' );
		$this->codes        = $this->get_option( 'codes' );
		$this->availability = $this->get_option( 'availability' );
		$this->countries    = $this->get_option( 'countries' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @param array $package (default: array())
	 */
	public function calculate_shipping( $package = array() ) {
		$shipping_total = 0;

		switch ( $this->type ) {
			case 'fixed' :
				$shipping_total = $this->fee;
			break;
			case 'percent' :
				$shipping_total = $package['contents_cost'] * ( $this->fee / 100 );
			break;
			case 'product' :
				foreach ( $package['contents'] as $item_id => $values ) {
					if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
						$shipping_total += $this->fee * $values['quantity'];
	                }
				}
			break;
		}

		$rate = array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => $shipping_total
		);

		$this->add_rate( $rate );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable local delivery', 'woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Local Delivery', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'type' => array(
				'title'       => __( 'Fee Type', 'woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'How to calculate delivery charges', 'woocommerce' ),
				'default'     => 'fixed',
				'options'     => array(
				'fixed'       => __( 'Fixed amount', 'woocommerce' ),
					'percent'     => __( 'Percentage of cart total', 'woocommerce' ),
					'product'     => __( 'Fixed amount per product', 'woocommerce' ),
				),
				'desc_tip'    => true,
			),
			'fee' => array(
				'title'       => __( 'Delivery Fee', 'woocommerce' ),
				'type'        => 'price',
				'description' => __( 'What fee do you want to charge for local delivery, disregarded if you choose free. Leave blank to disable.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => wc_format_localized_price( 0 )
			),
			'codes' => array(
				'title'       => __( 'Allowed Zip/Post Codes', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => __( 'What zip/post codes are available for local pickup?', 'woocommerce' ),
				'default'     => '',
				'description' => __( 'Separate codes with a comma. Accepts wildcards, e.g. <code>P*</code> will match a postcode of PE30. Also accepts a pattern, e.g. <code>NG1___</code> would match NG1 1AA but not NG10 1AA', 'woocommerce' ),
				'placeholder' => 'e.g. 12345, 56789'
			),
			'availability' => array(
				'title'       => __( 'Method availability', 'woocommerce' ),
				'type'        => 'select',
				'default'     => 'all',
				'class'       => 'availability wc-enhanced-select',
				'options'     => array(
					'all'         => __( 'All allowed countries', 'woocommerce' ),
					'specific'    => __( 'Specific Countries', 'woocommerce' )
				)
			),
			'countries' => array(
				'title'       => __( 'Specific Countries', 'woocommerce' ),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'css'         => 'width: 450px;',
				'default'     => '',
				'options'     => WC()->countries->get_shipping_countries(),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select some countries', 'woocommerce' )
				)
			)
		);
	}
}
