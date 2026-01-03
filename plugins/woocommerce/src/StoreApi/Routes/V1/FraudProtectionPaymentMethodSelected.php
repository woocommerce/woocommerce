<?php
/**
 * FraudProtectionPaymentMethodSelected class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler;
use Automattic\WooCommerce\StoreApi\SchemaController;

defined( 'ABSPATH' ) || exit;

/**
 * Store API route for tracking payment method selection for fraud protection.
 *
 * @since 10.5.0
 * @internal
 */
class FraudProtectionPaymentMethodSelected extends AbstractRoute {

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-payment-method-selected';

	/**
	 * The schema item type.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'error';

	/**
	 * Constructor accepts no schema.
	 *
	 * @param SchemaController $schema_controller Schema controller instance.
	 */
	public function __construct( $schema_controller ) {
		$this->schema_controller = $schema_controller;
		// Intentionally not calling parent constructor to avoid schema requirement.
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/fraud-protection/payment-method-selected';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'payment_method' => [
						'description' => __( 'The payment method slug that was selected.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
				],
			],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$payment_method = $request->get_param( 'payment_method' );

		// Get the checkout event scheduler from the container.
		$scheduler = wc_get_container()->get( CheckoutEventScheduler::class );

		// Build event data for payment method selection.
		$event_data = [
			'action'  => 'payment_method_selected',
			'payment' => [
				'payment_method_type' => $payment_method,
			],
		];

		// Schedule the tracking.
		$scheduler->schedule_tracking( 'checkout_blocks_payment_method_selected', $event_data );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Payment method tracked.', 'woocommerce' ),
			],
			200
		);
	}
}
