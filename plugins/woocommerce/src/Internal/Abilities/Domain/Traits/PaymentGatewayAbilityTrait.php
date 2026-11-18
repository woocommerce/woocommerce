<?php
/**
 * Payment gateway ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Gateway output built from public properties only. Gateway settings hold API
 * keys and passwords, so only the saved title and description are read.
 */
trait PaymentGatewayAbilityTrait {

	/**
	 * Gateway response item; `$order` is its 0-based position at checkout.
	 *
	 * @param \WC_Payment_Gateway $gateway Gateway.
	 * @param int                 $order   Checkout position.
	 * @return array
	 */
	protected static function format_payment_gateway_for_response( \WC_Payment_Gateway $gateway, int $order ): array {
		// The title and description getters run checkout filters that may expect a cart.
		return array(
			'id'           => (string) $gateway->id,
			'title'        => (string) ( $gateway->title ? $gateway->title : $gateway->get_option( 'title' ) ),
			'method_title' => (string) $gateway->get_method_title(),
			'description'  => (string) ( $gateway->description ? $gateway->description : $gateway->get_option( 'description' ) ),
			'enabled'      => 'yes' === $gateway->enabled,
			'order'        => $order,
		);
	}

	/**
	 * Closed item shape, so no settings field can be added to the output.
	 *
	 * @return array
	 */
	protected static function get_payment_gateway_item_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'           => array( 'type' => 'string' ),
				'title'        => array(
					'type'        => 'string',
					'description' => __( 'Title shown to customers at checkout. Can be empty.', 'woocommerce' ),
				),
				'method_title' => array(
					'type'        => 'string',
					'description' => __( 'Name of the payment method or provider.', 'woocommerce' ),
				),
				'description'  => array(
					'type'        => 'string',
					'description' => __( 'Description shown to customers at checkout. Can be empty.', 'woocommerce' ),
				),
				'enabled'      => array( 'type' => 'boolean' ),
				'order'        => array(
					'type'        => 'integer',
					'description' => __( 'Position in the checkout order, starting at 0.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Unknown-gateway error shared by the gateway abilities.
	 *
	 * @param string $code Error code.
	 * @return \WP_Error
	 */
	protected static function payment_gateway_not_found( string $code ): \WP_Error {
		return new \WP_Error(
			$code,
			__( 'Payment gateway not found. Use the woocommerce/payment-gateways-query ability to list gateway IDs.', 'woocommerce' ),
			array( 'status' => 404 )
		);
	}
}
