<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for flat rate shipping
 */
return array(
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
		),
	),
	'cost_per_order' => array(
		'title' 		=> __( 'Cost per order', 'woocommerce' ),
		'type' 			=> 'price',
		'placeholder'	=> wc_format_localized_price( 0 ),
		'description'	=> __( 'Enter a cost (excluding tax) per order, e.g. 5.00. Default is 0.', 'woocommerce' ),
		'default'		=> '',
		'desc_tip'		=> true
	),
	'additional_costs' => array(
		'title'			=> __( 'Additional Costs', 'woocommerce' ),
		'type'			=> 'title',
		'description'   => __( 'Additional costs can be added below - these will all be added to the per-order cost above.', 'woocommerce' )
	),
	'type' => array(
		'title' 		=> __( 'Costs Added...', 'woocommerce' ),
		'type' 			=> 'select',
		'class'         => 'wc-enhanced-select',
		'default' 		=> 'order',
		'options' 		=> array(
			'order' 	=> __( 'Per Order - charge shipping for the entire order as a whole', 'woocommerce' ),
			'item' 		=> __( 'Per Item - charge shipping for each item individually', 'woocommerce' ),
			'class' 	=> __( 'Per Class - charge shipping for each shipping class in an order', 'woocommerce' ),
		),
	),
	'additional_costs_table' => array(
		'type'				=> 'additional_costs_table'
	),
	'minimum_fee' => array(
		'title' 		=> __( 'Minimum Handling Fee', 'woocommerce' ),
		'type' 			=> 'price',
		'placeholder'	=> wc_format_localized_price( 0 ),
		'description'	=> __( 'Enter a minimum fee amount. Fee\'s less than this will be increased. Leave blank to disable.', 'woocommerce' ),
		'default'		=> '',
		'desc_tip'		=> true
	),
	'addons' => array(
		'title'			=> __( 'Add-on Rates', 'woocommerce' ),
		'type'			=> 'title',
		'description'   => __( 'Add-on rates are extra shipping options with additional costs (based on the flat rate).', 'woocommerce' )
	),
	'options' => array(
		'title' 		=> __( 'Rates', 'woocommerce' ),
		'type' 			=> 'textarea',
		'description'	=> __( 'One per line: Option Name | Additional Cost [+- Percents] | Per Cost Type (order, class, or item) Example: <code>Priority Mail | 6.95 [+ 0.2%] | order</code>.', 'woocommerce' ),
		'default'		=> '',
		'desc_tip'		=> true,
		'placeholder'	=> __( 'Option Name | Additional Cost [+- Percents%] | Per Cost Type (order, class, or item)', 'woocommerce' )
	),
);
