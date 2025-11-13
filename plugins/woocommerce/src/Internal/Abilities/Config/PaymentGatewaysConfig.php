<?php
/**
 * Payment Gateways MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Payment Gateways MCP tool.
 */
class PaymentGatewaysConfig {

	/**
	 * Get the configuration array for the payment gateways tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/payment-gateways',
			'operations'         => array( 'list', 'get', 'update' ),
			'controller'         => \WC_REST_Payment_Gateways_Controller::class,
			'route'              => '/wc/v3/payment_gateways',
			'label'              => __( 'Manage payment gateways', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce payment gateways. Use action parameter to list, get, or update payment gateways.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential update parameters.
				'enabled',
				'title',
				'description',
				'order',
				'settings',
			),
		);
	}
}
