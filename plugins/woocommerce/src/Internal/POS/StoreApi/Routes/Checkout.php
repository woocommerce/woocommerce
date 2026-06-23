<?php
/**
 * Checkout class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\StoreApi\Utilities\ProcessCheckoutTrait;

/**
 * POS /checkout route.
 *
 * Adapter-style: extends the abstract {@see AbstractCartRoute} (the designed
 * extension point, the same one agentic commerce builds on) and runs the *exact
 * same* checkout pipeline as the web route by composing the shared
 * {@see ProcessCheckoutTrait} — no subclassing of the concrete web route, no
 * duplicated orchestration. Only the POS endpoint shape lives here: the
 * capability gate and `cart_token`/nonce seams from {@see PosRouteTrait}, and a
 * {@see self::get_args()} carrying the POS relaxations (optional billing/shipping
 * address, optional `customer_id`).
 *
 * Because the pipeline is shared through the trait, a change to web checkout
 * reaches POS automatically — the same "POS stays in sync with web" property the
 * inheritance spike had, but coupled to the documented trait/controller surface
 * rather than the concrete route's internals.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Checkout extends AbstractCartRoute {

	use DraftOrderTrait;
	use CheckoutTrait;
	use ProcessCheckoutTrait;
	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'checkout';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'checkout';

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
	 * Same shape as the web checkout route, with POS relaxations layered in: a
	 * capability permission callback, the `cart_token` parameter, an optional
	 * `customer_id` for order attribution, and billing/shipping address args
	 * marked not-required (an in-person sale usually has neither).
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
					$this->pos_cart_token_arg()
				),
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'payment_data'      => array(
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
						'customer_password' => array(
							'description' => __( 'Customer password for new accounts, if applicable.', 'woocommerce' ),
							'type'        => 'string',
						),
						'customer_id'       => array(
							'description' => __( 'ID of an existing customer account to associate the order with. Omit for a guest sale. The order is attributed to the account, but cart pricing, coupons and tax still resolve as a guest.', 'woocommerce' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'context'     => array( 'view', 'edit' ),
						),
					),
					$this->pos_cart_token_arg(),
					$this->pos_relax_address_required( $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ) )
				),
			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'additional_fields' => array(
							'description' => __( 'Additional fields related to the order.', 'woocommerce' ),
							'type'        => 'object',
						),
						'payment_method'    => array(
							'description' => __( 'Selected payment method for the order.', 'woocommerce' ),
							'type'        => 'string',
						),
						'order_notes'       => array(
							'description' => __( 'Order notes.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
					$this->pos_cart_token_arg(),
					$this->pos_relax_address_required( $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ) )
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => true ),
		);
	}
}
