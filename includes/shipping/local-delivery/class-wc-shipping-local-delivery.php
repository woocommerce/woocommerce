<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local Delivery Shipping Method.
 *
 * A simple shipping method allowing local delivery as a shipping method.
 *
 * @class 		WC_Shipping_Local_Delivery
 * @version		2.6.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Local_Delivery extends WC_Shipping_Local_Pickup {

	/**
	 * Constructor.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                    = 'local_delivery';
		$this->instance_id 			 = absint( $instance_id );
		$this->method_title          = __( 'Local Delivery', 'woocommerce' );
		$this->method_description    = __( 'Local delivery is a simple shipping method for delivering orders locally.', 'woocommerce' );
		$this->supports              = array(
			'shipping-zones',
			'instance-settings'
		);
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

		$this->add_rate( array(
			'id'    => $this->id . $this->instance_id,
			'label' => $this->title,
			'cost'  => $shipping_total
		) );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
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
			)
		);
	}
}
