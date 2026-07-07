<?php
/**
 * CartAddItems class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\POS\StoreApi\Schemas\AddItemsSchema;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Utilities\AddItemToCartTrait;

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

	use AddItemToCartTrait;
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

			// Each batch item runs the web add-item pipeline verbatim (shared
			// AddItemToCartTrait) against a per-item view of the request, so
			// extensions written for the web route behave identically here.
			$item_request = clone $request;
			$item_request->set_param( 'id', $result['id'] );
			$item_request->set_param( 'quantity', $result['quantity'] );
			$item_request->set_param( 'variation', $item['variation'] ?? array() );

			try {
				$result['key']   = $this->add_item_to_cart_from_request( $item_request );
				$result['added'] = true;
				++$added_count;
			} catch ( RouteException $error ) {
				// What the web route would have answered with a 4xx.
				$result['error'] = array(
					'code'    => $error->getErrorCode(),
					'message' => wp_specialchars_decode( $error->getMessage(), ENT_QUOTES ),
				);
			} catch ( \Throwable $unexpected ) {
				// What the web route would have answered with a generic 500
				// (same code, see AbstractRoute's dispatch catch). Web parity
				// means no cleanup: the cart keeps whatever state core left,
				// and the envelope's cart — like a web client's re-fetch — is
				// the source of truth. Totals are recalculated below before
				// anything serializes the cart again.
				$result['error'] = array(
					'code'    => 'woocommerce_rest_unknown_server_error',
					'message' => wp_specialchars_decode( $unexpected->getMessage(), ENT_QUOTES ),
				);
				$this->cart_controller->get_cart_instance()->calculate_totals();
			}

			$results[] = $result;
		}

		if ( 0 === $added_count ) {
			// A listener that throws on woocommerce_add_to_cart aborts *after*
			// CartController committed the line, so the item is in the cart even
			// though it is reported added=false. Attach the authoritative cart —
			// the same source of truth the 201 envelope and the 409 conflict
			// response give the client — so the client reconciles instead of
			// re-scanning and doubling.
			$error_data = array(
				'items' => $results,
				'cart'  => $this->cart_schema->get_item_response( $this->cart_controller->get_cart_for_response() ),
			);

			throw new RouteException(
				'woocommerce_pos_rest_no_items_added',
				esc_html__( 'None of the requested items could be added to the cart.', 'woocommerce' ),
				400,
				$error_data // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Structured data, encoded by the REST server.
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
