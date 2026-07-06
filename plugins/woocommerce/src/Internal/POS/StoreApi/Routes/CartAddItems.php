<?php
/**
 * CartAddItems class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\POS\StoreApi\Schemas\AddItemsSchema;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;

/**
 * POS cart/add-items route.
 *
 * Adds one or more items to the transaction cart in a single request — a
 * scan is an array of one. Each item runs through the same
 * CartController::add_to_cart() pipeline (and every validation rule and
 * extension hook) as the web cart/add-item route; the difference is the
 * contract: items are processed independently, failures are reported per
 * item, and the response is the {@see AddItemsSchema} envelope (full cart +
 * per-item outcomes) rather than the bare cart.
 *
 * Partial success is deliberate: 201 when at least one item was added, 400
 * (with the same per-item detail) when none were.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CartAddItems extends AbstractCartRoute {

	use PosRouteTrait;

	/**
	 * Capability required to call this route.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'pos-cart-add-items';

	/**
	 * The route's schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = AddItemsSchema::IDENTIFIER;

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
		return '/cart/add-items';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'items' => array(
						'description' => __( 'Items to add to the cart. Processed independently: failures are reported per item.', 'woocommerce' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
						'minItems'    => 1,
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'id', 'quantity' ),
							'properties' => array(
								'id'        => array(
									'description' => __( 'The cart item product or variation ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'quantity'  => array(
									'description' => __( 'Quantity of this item to add to the cart.', 'woocommerce' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
								),
								'variation' => array(
									'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
									'type'        => 'array',
									'context'     => array( 'view', 'edit' ),
									'items'       => array(
										'type'       => 'object',
										'properties' => array(
											'attribute' => array(
												'description' => __( 'Variation attribute name.', 'woocommerce' ),
												'type'    => 'string',
												'context' => array( 'view', 'edit' ),
											),
											'value'     => array(
												'description' => __( 'Variation attribute value.', 'woocommerce' ),
												'type'    => 'string',
												'context' => array( 'view', 'edit' ),
											),
										),
									),
								),
							),
						),
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException When no item could be added.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$results     = array();
		$added_count = 0;

		foreach ( (array) $request['items'] as $item ) {
			$result = array(
				'id'       => absint( $item['id'] ?? 0 ),
				'quantity' => wc_stock_amount( $item['quantity'] ?? 0 ),
				'added'    => false,
				'key'      => null,
				'error'    => null,
			);

			/**
			 * Filters cart item data sent via the API before it is passed to the cart controller.
			 *
			 * Applied per item, mirroring the web cart/add-item route, so extensions
			 * hooked there behave identically for POS adds.
			 *
			 * @since 8.8.0
			 *
			 * @param array            $add_to_cart_data An array of cart item data.
			 * @param \WP_REST_Request $request          Full details about the request.
			 * @return array
			 */
			$add_to_cart_data = apply_filters(
				'woocommerce_store_api_add_to_cart_data',
				array(
					'id'             => $result['id'],
					'quantity'       => $result['quantity'],
					'variation'      => $item['variation'] ?? array(),
					'cart_item_data' => array(),
				),
				$request
			);

			$keys_before_attempt = array_keys( $this->cart_controller->get_cart_instance()->get_cart_contents() );

			try {
				$result['key']   = $this->cart_controller->add_to_cart( $add_to_cart_data );
				$result['added'] = true;
				++$added_count;

				$cart_item = $this->cart_controller->get_cart_instance()->get_cart_item( $result['key'] );
				if ( ! empty( $cart_item ) ) {
					$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

					/**
					 * Fires when an item is added to the cart from a user request.
					 *
					 * @param int       $product_id Product ID (variation ID for variable products).
					 * @param int|float $quantity   Quantity added to the cart.
					 *
					 * @since 10.6.0
					 */
					do_action( 'internal_woocommerce_cart_item_added_from_user_request', $product_id, $add_to_cart_data['quantity'] ?? $cart_item['quantity'] );
				}
			} catch ( RouteException $error ) {
				$result['error'] = array(
					'code'    => $error->getErrorCode(),
					'message' => wp_specialchars_decode( $error->getMessage(), ENT_QUOTES ),
				);
			} catch ( \Throwable $unexpected ) {
				// Third-party code on the add-to-cart hooks can throw anything.
				// Items are processed independently by contract: one plugin
				// blowing up on one item must not abort the batch as a 500 —
				// earlier items are already in the cart, and the operator's
				// natural reaction (rescan the basket) would double-add them.
				//
				// An exception can interrupt core's add mid-write, leaving a
				// half-populated cart line (no totals) that would blow up
				// response serialization — drop any line this attempt created.
				// set_cart_contents (not remove_cart_item) on purpose: the
				// line never validly existed, so no removal hooks and no
				// restorable removed_contents entry, which would carry the
				// same corrupt data into the response.
				$cart          = $this->cart_controller->get_cart_instance();
				$orphaned_keys = array_diff( array_keys( $cart->get_cart_contents() ), $keys_before_attempt );
				if ( $orphaned_keys ) {
					$contents = $cart->get_cart_contents();
					foreach ( $orphaned_keys as $orphaned_key ) {
						unset( $contents[ $orphaned_key ] );
					}
					$cart->set_cart_contents( $contents );
				}

				$result['error'] = array(
					'code'    => 'woocommerce_pos_rest_add_item_failed',
					'message' => wp_specialchars_decode( $unexpected->getMessage(), ENT_QUOTES ),
				);
			}

			$results[] = $result;
		}

		if ( 0 === $added_count ) {
			throw new RouteException(
				'woocommerce_pos_rest_no_items_added',
				esc_html__( 'None of the requested items could be added to the cart.', 'woocommerce' ),
				400,
				array( 'items' => $results ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Structured data, encoded by the REST server.
			);
		}

		/**
		 * The envelope schema this route is constructed with.
		 *
		 * @var AddItemsSchema $schema
		 */
		$schema = $this->schema;

		$response = rest_ensure_response(
			$schema->get_item_response(
				array(
					'cart'  => $this->cart_controller->get_cart_for_response(),
					'items' => $results,
				)
			)
		);
		$response->set_status( 201 );

		return $response;
	}
}
