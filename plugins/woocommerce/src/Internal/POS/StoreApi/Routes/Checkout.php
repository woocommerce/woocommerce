<?php
/**
 * Checkout class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\StoreApi\Utilities\ProcessCheckoutTrait;

/**
 * POS checkout route: turns the transaction cart into a real `pending` order.
 *
 * Composes the same place-order pipeline as the web checkout
 * ({@see ProcessCheckoutTrait}) and the shared {@see CheckoutSchema}, so the
 * response shape is identical to web checkout and the created order is
 * immediately readable via the classic wc/v3 orders API — where the app
 * records the payment taken at the register. An effectively empty request
 * body is valid for POS: the payment-method and billing/shipping/email
 * requirements are relaxed by
 * {@see \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPolicy}
 * through the additive Store API checkout filters, and this route's argument
 * schema drops the address `required` flags to match.
 *
 * The optional `customer_id` identifies the purchaser as an existing customer
 * account (see PolicyHooks\CustomerAccountPolicy); otherwise the order is a
 * guest sale — never the operator's (see PolicyHooks\CurrentUserSwap).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class Checkout extends AbstractCartRoute {

	use CheckoutTrait;
	use ProcessCheckoutTrait, PosRouteTrait {
		PosRouteTrait::requires_nonce insteadof ProcessCheckoutTrait;
		PosRouteTrait::get_response insteadof ProcessCheckoutTrait;
		ProcessCheckoutTrait::get_response as process_checkout_get_response;
	}

	/**
	 * Route the token pre-check into the checkout's own dispatch wrapper
	 * (which carries checkout-specific error handling — stock release,
	 * InvalidCartException mapping) instead of the cart route base one.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function dispatch_pos_response( \WP_REST_Request $request ) {
		return $this->process_checkout_get_response( $request );
	}

	/**
	 * Capability required to call this route.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'pos-checkout';

	/**
	 * The route's schema — the shared web checkout schema, unchanged.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = CheckoutSchema::IDENTIFIER;

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
		return '/checkout';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * POST only, deliberately: the POS client places the order in a single
	 * call; the web checkout's GET (draft state) and PUT (draft update)
	 * endpoints have no POS flow behind them, and unused capability-gated
	 * endpoints running under the POS policy relaxations would be pure
	 * unreviewed surface.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'payment_data' => array(
							'description' => __( 'Data to pass through to the payment method when processing payment.', 'woocommerce' ),
							'type'        => 'array',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'key'   => array(
										'type' => 'string',
									),
									'value' => array(
										'type' => array( 'string', 'boolean' ),
									),
								),
							),
						),
						'customer_id'  => array(
							'description' => __( 'ID of an existing customer account to associate the order with. Omit for a guest sale.', 'woocommerce' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'context'     => array( 'view', 'edit' ),
						),
					),
					$this->relax_address_required( $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ) )
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Drop the schema-level `required` flag from the address arguments.
	 *
	 * An in-person sale usually has neither a billing nor a shipping address;
	 * the deeper address validation is relaxed separately by CheckoutPolicy
	 * through the Store API filters. A no-op for arg sets without those keys.
	 *
	 * @param array $args Endpoint args from get_endpoint_args_for_item_schema().
	 * @return array
	 */
	private function relax_address_required( array $args ): array {
		foreach ( array( 'billing_address', 'shipping_address' ) as $address_arg ) {
			if ( isset( $args[ $address_arg ] ) && is_array( $args[ $address_arg ] ) ) {
				$args[ $address_arg ]['required'] = false;
			}
		}

		return $args;
	}
}
