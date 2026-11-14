<?php
/**
 * The payment method description shown at checkout.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'type'        => 'text',
	'Edit'        => 'textarea',
	'label'       => __( 'Description', 'woocommerce' ),
	'description' => __( 'Payment method description that the customer will see during checkout.', 'woocommerce' ),
);
