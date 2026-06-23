<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\StoreApi\Utilities\ProcessCheckoutTrait;

/**
 * Checkout class.
 *
 * The checkout request lifecycle and place-order orchestration live in
 * {@see ProcessCheckoutTrait}, shared with first-party reusers that run the same
 * pipeline under their own namespace — POS
 * ({@see \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Checkout},
 * `wc/internal/pos/v1`). This class contributes only the web endpoint shape
 * (path + args); behavioural changes belong in the trait (shared with reusers)
 * or, for anything a reuser should opt out of, behind a seam/filter.
 */
class Checkout extends AbstractCartRoute {
	use DraftOrderTrait;
	use CheckoutTrait;
	use ProcessCheckoutTrait;

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
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array_merge(
					[
						'payment_data'      => [
							'description' => __( 'Data to pass through to the payment method when processing payment.', 'woocommerce' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'key'   => [
										'type' => 'string',
									],
									'value' => [
										'type' => [ 'string', 'boolean' ],
									],
								],
							],
						],
						'customer_password' => [
							'description' => __( 'Customer password for new accounts, if applicable.', 'woocommerce' ),
							'type'        => 'string',
						],
					],
					$this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE )
				),
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array_merge(
					[
						'additional_fields' => [
							'description' => __( 'Additional fields related to the order.', 'woocommerce' ),
							'type'        => 'object',
						],
						'payment_method'    => [
							'description' => __( 'Selected payment method for the order.', 'woocommerce' ),
							'type'        => 'string',
						],
						'order_notes'       => [
							'description' => __( 'Order notes.', 'woocommerce' ),
							'type'        => 'string',
						],
					],
					$this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE )
				),
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}
}
