<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for flat rate shipping
 */
$settings = array(
	'enabled' => array(
		'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
		'type' 			=> 'checkbox',
		'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
		'default' 		=> 'no',
	),
	'title' => array(
		'title' 		=> __( 'Method Title', 'woocommerce' ),
		'type' 			=> 'text',
		'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'		=> __( 'Flat Rate', 'woocommerce' ),
		'desc_tip'		=> true
	),
	'availability' => array(
		'title' 		=> __( 'Availability', 'woocommerce' ),
		'type' 			=> 'select',
		'default' 		=> 'all',
		'class'			=> 'availability wc-enhanced-select',
		'options'		=> array(
			'all' 		=> __( 'All allowed countries', 'woocommerce' ),
			'specific' 	=> __( 'Specific Countries', 'woocommerce' ),
		),
	),
	'countries' => array(
		'title' 		=> __( 'Specific Countries', 'woocommerce' ),
		'type' 			=> 'multiselect',
		'class'			=> 'wc-enhanced-select',
		'css'			=> 'width: 450px;',
		'default' 		=> '',
		'options'		=> WC()->countries->get_shipping_countries(),
		'custom_attributes' => array(
			'data-placeholder' => __( 'Select some countries', 'woocommerce' )
		)
	),
	'tax_status' => array(
		'title' 		=> __( 'Tax Status', 'woocommerce' ),
		'type' 			=> 'select',
		'class'         => 'wc-enhanced-select',
		'default' 		=> 'taxable',
		'options'		=> array(
			'taxable' 	=> __( 'Taxable', 'woocommerce' ),
			'none' 		=> _x( 'None', 'Tax status', 'woocommerce' )
		)
	),
	'cost' => array(
		'title' 		=> __( 'Cost', 'woocommerce' ),
		'type' 			=> 'text',
		'placeholder'	=> wc_format_localized_price( 0 ),
		'description'	=> __( 'Enter a cost (excl. tax) or sum, e.g. <code>10 * [qty]</code>.', 'woocommerce' ) . '<br/>' . __( 'Supports the following placeholders: <code>[qty]</code> = number of items, <code>[cost]</code> = cost of items.', 'woocommerce' ),
		'default'		=> '',
		'desc_tip'		=> true
	)
);

if ( WC()->shipping->get_shipping_classes() ) {
	foreach ( WC()->shipping->get_shipping_classes() as $shipping_class ) {
		$settings[ 'class_cost_' . $shipping_class->slug ] = array(
			'title'       => sprintf( __( '"%s" Cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'        => 'text',
			'placeholder' => wc_format_localized_price( 0 ),
			'description'	=> __( 'Enter a cost (excl. tax) or sum, e.g. <code>10 * [qty]</code>.', 'woocommerce' ) . '<br/>' . __( 'Supports the following placeholders: <code>[qty]</code> = number of items, <code>[cost]</code> = cost of items.', 'woocommerce' ),
			'default'     => '',
			'desc_tip'    => true
		);
	}
}

if ( $this->get_option( 'options', false ) ) {
	$settings['options'] = array(
		'title' 		=> __( 'Additional Rates', 'woocommerce' ),
		'type' 			=> 'textarea',
		'description'	=> __( 'One per line: Option Name | Additional Cost [+- Percents] | Per Cost Type (order, class, or item) Example: <code>Priority Mail | 6.95 [+ 0.2%] | order</code>.', 'woocommerce' ),
		'default'		=> '',
		'desc_tip'		=> true,
		'placeholder'	=> __( 'Option Name | Additional Cost [+- Percents%] | Per Cost Type (order, class, or item)', 'woocommerce' )
	);
}

return $settings;
