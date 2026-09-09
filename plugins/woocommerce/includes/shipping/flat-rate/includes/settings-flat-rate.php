<?php
/**
 * Settings for flat rate shipping.
 *
 * @package WooCommerce\Classes\Shipping
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Utilities\I18nUtil;

// This description is shown as a tooltip, and wc_sanitize_tooltip() strips <code> tags, so the placeholders are
// listed as `placeholder = meaning` pairs rather than relying on markup to delimit them.
$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. 10.00 * [qty].', 'woocommerce' ) . '<br/><br/>' . sprintf(
	/* translators: %s: store weight unit label, e.g. kg */
	__( 'Supports the following placeholders: [qty] = number of items, [cost] = total cost of items, [weight] = total weight of items in %s, [fee percent="10" min_fee="20" max_fee=""] = percentage based fee.', 'woocommerce' ),
	I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit', 'kg' ) )
) . '<br/><br/>' . __( 'The weight accepts an optional minimum and maximum, e.g. [weight min="1" max="20"].', 'woocommerce' );
$cost_link = sprintf( '<span id="wc-shipping-advanced-costs-help-text">%s <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#advanced-costs">%s</a>.</span>', __( 'Charge a flat rate per item, or enter a cost formula to charge a percentage based cost or a minimum fee. Learn more about', 'woocommerce' ), __( 'advanced costs', 'woocommerce' ) );

$settings = array(
	'title'      => array(
		'title'       => __( 'Name', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'woocommerce' ),
		'default'     => __( 'Flat rate', 'woocommerce' ),
		'placeholder' => __( 'e.g. Standard national', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'tax_status' => array(
		'title'   => __( 'Tax status', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => ProductTaxStatus::TAXABLE,
		'options' => array(
			ProductTaxStatus::TAXABLE => __( 'Taxable', 'woocommerce' ),
			ProductTaxStatus::NONE    => _x( 'None', 'Tax status', 'woocommerce' ),
		),
	),
	'cost'       => array(
		'title'             => __( 'Cost', 'woocommerce' ),
		'type'              => 'text',
		'class'             => 'wc-shipping-modal-price',
		'placeholder'       => '',
		'description'       => $cost_desc,
		'default'           => '0',
		'desc_tip'          => true,
		'sanitize_callback' => array( $this, 'sanitize_cost' ),
	),
);

$shipping_classes = WC()->shipping()->get_shipping_classes();

if ( ! empty( $shipping_classes ) ) {
	$settings['class_costs'] = array(
		'title'       => __( 'Shipping class costs', 'woocommerce' ),
		'type'        => 'title',
		'default'     => '',
		/* translators: %s: URL for link */
		'description' => sprintf( __( 'These costs can optionally be added based on the <a target="_blank" href="%s">product shipping class</a>. Learn more about <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#shipping-classes">setting shipping class costs</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
	);
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}
		$settings[ 'class_cost_' . $shipping_class->term_id ] = array(
			/* translators: %s: shipping class name */
			'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'              => 'text',
			'class'             => 'wc-shipping-modal-price',
			'placeholder'       => __( 'N/A', 'woocommerce' ),
			'description'       => $cost_desc,
			'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ), // Before 2.5.0, we used slug here which caused issues with long setting names.
			'desc_tip'          => true,
			'sanitize_callback' => array( $this, 'sanitize_cost' ),
		);
	}

	$settings['no_class_cost'] = array(
		'title'             => __( 'No shipping class cost', 'woocommerce' ),
		'type'              => 'text',
		'class'             => 'wc-shipping-modal-price',
		'placeholder'       => __( 'N/A', 'woocommerce' ),
		'description'       => $cost_desc,
		'default'           => '',
		'desc_tip'          => true,
		'sanitize_callback' => array( $this, 'sanitize_cost' ),
	);

	$settings['type'] = array(
		'title'       => __( 'Calculation type', 'woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'default'     => 'class',
		'options'     => array(
			'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
			'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
		),
		'description' => $cost_link,
	);
}

return $settings;
