<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApiParameterUtil;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema;
use Automattic\WooCommerce\Utilities\MetaDataUtil;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Order_Refunds_V2_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Order_Refunds_V2_Controller {
	use CogsAwareTrait;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Register the routes for order refunds, including the refund preview route.
	 *
	 * @return void
	 *
	 * @since 11.1.0
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			array(
				'args'   => array(
					'order_id' => array(
						'description' => __( 'The order ID.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				// permission_callback below intentionally uses the create-refund capability:
				// preview is read-only but logically part of the refund-creation flow, so it
				// requires the same capability. This prevents read-only-API clients from
				// probing refund state on orders they cannot act on.
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'preview_refund' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'line_items' => $this->get_preview_line_items_arg_schema(),
					),
				),
				'schema' => array( $this, 'get_public_preview_schema' ),
			)
		);
	}

	/**
	 * Preview a refund without creating it.
	 *
	 * Returns server-computed refund totals and per-line breakdowns for the
	 * requested line items, using the same calculation engine as the wc/v4
	 * refunds endpoints, so clients do not have to replicate tax, rounding,
	 * and currency-precision logic.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @since 11.1.0
	 */
	public function preview_refund( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		// wc_get_order returns WC_Order|WC_Order_Refund|false; only a WC_Order
		// (shop_order) is previewable here — refunds and missing IDs are rejected.
		if ( ! $order instanceof WC_Order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Round caller-supplied refund_total values once, up front, so validation and
		// the computed preview use the same precision the create flow stores. Reused
		// for both validate and build below.
		$line_items = $this->data_utils()->normalize_refund_totals( $request['line_items'] );

		$validation_error = $this->data_utils()->validate_preview_line_items( $line_items, $order );

		// The WP_Error already carries its HTTP status in the error data; returning
		// it directly lets the REST server respond with that status. The shared
		// validation engine emits unprefixed codes (the wc/v4 convention); they are
		// prefixed here at the v3 boundary so this endpoint follows the
		// `woocommerce_rest_*` convention of the rest of the v3 surface.
		if ( is_wp_error( $validation_error ) ) {
			return $this->prefix_error_code( $validation_error );
		}

		try {
			$preview = $this->data_utils()->build_refund_preview( $order, $line_items );
		} catch ( InvalidArgumentException $e ) {
			// validate_preview_line_items above should have caught any bad input.
			// If build_refund_preview still throws InvalidArgumentException, treat
			// it as a server-side invariant violation, log for observability, and
			// return a generic message (do not leak internal IDs to clients).
			wc_get_logger()->error(
				sprintf( 'Refund preview invariant violation on order %d: %s', $order->get_id(), $e->getMessage() ),
				array( 'source' => 'wc-rest-refunds' )
			);
			return new WP_Error(
				'woocommerce_rest_invalid_preview_request',
				__( 'The refund preview could not be generated due to an unexpected error.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Refund preview unexpected error on order %d: %s', $order->get_id(), $e->getMessage() ),
				array( 'source' => 'wc-rest-refunds' )
			);
			return new WP_Error(
				'woocommerce_rest_unexpected_preview_error',
				__( 'An unexpected error occurred while generating the refund preview.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// Reject a non-positive aggregate total up front. A refund of only a negative
		// discount line, or a product plus discount that nets to zero, would otherwise
		// preview successfully and then fail at create time.
		if ( (float) $preview['total'] <= 0 ) {
			return new WP_Error(
				'woocommerce_rest_invalid_refund_amount',
				__( 'Refund total must be greater than zero.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Final guard: even when per-line validation passes, the aggregate
		// preview total can still exceed the order's remaining refundable
		// amount (e.g. an amount-only partial refund applied previously).
		// `total` is already tax-inclusive; compare directly against max_refundable.
		$preview_total_with_tax = abs( (float) $preview['total'] );
		if ( $preview_total_with_tax > (float) $preview['max_refundable'] ) {
			return new WP_Error(
				'woocommerce_rest_preview_exceeds_max_refundable',
				sprintf(
					/* translators: 1: requested preview total including tax, 2: remaining refundable */
					__( 'Requested refund preview (%1$s) exceeds the remaining refundable amount (%2$s).', 'woocommerce' ),
					wc_format_decimal( $preview_total_with_tax, wc_get_price_decimals() ),
					$preview['max_refundable']
				),
				array( 'status' => 422 )
			);
		}

		/**
		 * Filters the refund preview data before it is returned.
		 *
		 * @param array           $preview Preview data (breakdown, subtotal, tax, total, max_refundable).
		 * @param WC_Order        $order   The order the refund preview was computed for.
		 * @param WP_REST_Request $request The request.
		 *
		 * @since 11.1.0
		 */
		$preview = apply_filters( 'woocommerce_rest_prepare_order_refund_preview', $preview, $order, $request );

		return rest_ensure_response( $preview );
	}

	/**
	 * Get the public schema for the refund preview endpoint.
	 *
	 * @return array
	 *
	 * @since 11.1.0
	 */
	public function get_public_preview_schema() {
		$schema          = wc_get_container()->get( RefundPreviewSchema::class )->get_item_schema();
		$schema['title'] = 'order_refund_preview';

		return $schema;
	}

	/**
	 * Get the argument schema for the preview route's line_items parameter.
	 *
	 * Mirrors the wc/v4 preview endpoint's argument schema (including the
	 * line_item_id key naming) so clients can send the same payload to both
	 * API versions.
	 *
	 * @return array
	 *
	 * @since 11.1.0
	 */
	private function get_preview_line_items_arg_schema() {
		return array(
			'description'       => __( 'Line items to include in the refund preview.', 'woocommerce' ),
			'type'              => 'array',
			'required'          => true,
			'minItems'          => 1,
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type'                 => 'object',
				'required'             => array( 'line_item_id' ),
				'additionalProperties' => false,
				'properties'           => array(
					'line_item_id' => array(
						'description' => __( 'ID of the original order line item.', 'woocommerce' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
					'quantity'     => array(
						'description' => __( 'Quantity to refund. Required when refund_total is omitted.', 'woocommerce' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
					'refund_total' => array(
						// No `minimum` here on purpose: validate_preview_line_items() owns
						// the sign rule and returns the actionable `invalid_refund_total`
						// code. A refund_total must be non-zero and match the line's sign —
						// negative is valid for a discount/credit line, positive for a normal
						// line; zero and wrong-sign values are rejected. A schema `minimum`
						// would wrongly forbid the negative form, and a generic
						// `rest_invalid_param` is less useful to clients.
						'description' => __( 'Tax-inclusive amount to refund for this line item. Must be non-zero and match the line\'s sign (negative for discount or credit lines, positive otherwise). Required when quantity is omitted.', 'woocommerce' ),
						'type'        => array( 'number', 'null' ),
					),
				),
			),
		);
	}

	/**
	 * Validate and normalize the scalar types of one compute_totals line item.
	 *
	 * The REST schema cannot validate the line_items subtree (the property is
	 * readonly for backward compatibility), so without this check malformed
	 * values such as an array refund_total would reach the calculation engine
	 * and fail with a TypeError instead of a 400 response. Uses the same error
	 * codes as the engine's own validation, and casts numeric strings to their
	 * proper types.
	 *
	 * @param array $line_item Line item in schema format (line_item_id keys).
	 * @return array|WP_Error The normalized line item, or WP_Error on an invalid type.
	 *
	 * @since 11.1.0
	 */
	private function normalize_line_item_types( array $line_item ) {
		// IDs must be whole numbers: silently truncating a fractional id such as
		// 123.5 to 123 would target a different line or tax bucket than requested.
		if ( isset( $line_item['line_item_id'] ) && ! is_int( $line_item['line_item_id'] ) ) {
			if ( ! is_numeric( $line_item['line_item_id'] ) || (float) (int) $line_item['line_item_id'] !== (float) $line_item['line_item_id'] ) {
				return new WP_Error( 'invalid_line_item', __( 'Line item id must be an integer.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['line_item_id'] = (int) $line_item['line_item_id'];
		}

		if ( isset( $line_item['quantity'] ) ) {
			$quantity = $line_item['quantity'];
			if ( ! is_numeric( $quantity ) || (float) (int) $quantity !== (float) $quantity ) {
				return new WP_Error( 'invalid_quantity', __( 'Quantity must be a whole number.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['quantity'] = (int) $quantity;
		}

		if ( isset( $line_item['refund_total'] ) ) {
			if ( ! is_numeric( $line_item['refund_total'] ) ) {
				return new WP_Error( 'invalid_refund_total', __( 'refund_total must be a number.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['refund_total'] = (float) $line_item['refund_total'];
		}

		if ( isset( $line_item['refund_tax'] ) ) {
			if ( ! is_array( $line_item['refund_tax'] ) ) {
				return new WP_Error( 'invalid_line_item', __( 'refund_tax must be an array of objects with id and refund_total.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			foreach ( $line_item['refund_tax'] as $index => $tax ) {
				if ( ! is_array( $tax ) || ! isset( $tax['id'], $tax['refund_total'] ) || ! is_numeric( $tax['id'] ) || ! is_numeric( $tax['refund_total'] )
					|| (float) (int) $tax['id'] !== (float) $tax['id'] ) {
					return new WP_Error( 'invalid_line_item', __( 'refund_tax entries must be objects with an integer id and a numeric refund_total.', 'woocommerce' ), array( 'status' => 400 ) );
				}
				$line_item['refund_tax'][ $index ] = array(
					'id'           => (int) $tax['id'],
					'refund_total' => (float) $tax['refund_total'],
				);
			}
		}

		return $line_item;
	}

	/**
	 * Get the shared refund calculation engine.
	 *
	 * DataUtils is the calculation/validation engine shared with the wc/v4
	 * refunds endpoints (the V4 segment in its namespace is historical); using
	 * it here keeps wc/v3 and wc/v4 refund math identical.
	 *
	 * @return DataUtils
	 */
	private function data_utils(): DataUtils {
		return wc_get_container()->get( DataUtils::class );
	}

	/**
	 * Prefix a shared-engine error code with `woocommerce_rest_`.
	 *
	 * DataUtils emits unprefixed codes (the wc/v4 convention). The wc/v3 surface
	 * uses `woocommerce_rest_*`, so errors crossing into a v3 response are
	 * renamed at this boundary. Codes that already carry the prefix pass through
	 * unchanged, and the message and data (including the HTTP status) are kept.
	 *
	 * @param WP_Error $error The error whose code should be prefixed.
	 *
	 * @return WP_Error
	 */
	private function prefix_error_code( WP_Error $error ): WP_Error {
		$code = (string) $error->get_error_code();

		if ( str_starts_with( $code, 'woocommerce_rest_' ) ) {
			return $error;
		}

		return new WP_Error( 'woocommerce_rest_' . $code, $error->get_error_message(), $error->get_error_data() );
	}

	/**
	 * Prepares one object for create or update operation.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		// The opt-in compute_totals mode routes through the shared wc/v4 refund
		// calculation pipeline. It is a separate path so that requests without the
		// flag behave exactly as before, including degenerate forms such as
		// quantity-only line items producing a 0.00 refund. The schema declares
		// compute_totals as boolean with a false default, so the REST layer has
		// already sanitized the value by the time this runs.
		if ( $creating && true === $request['compute_totals'] ) {
			return $this->create_refund_with_computed_totals( $request );
		}

		RestApiParameterUtil::adjust_create_refund_request_parameters( $request );

		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), 404 );
		}

		if ( 0 > $request['amount'] ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_refund', __( 'Refund amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		// Create the refund.
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $request['amount'],
				'reason'         => $request['reason'],
				'line_items'     => $request['line_items'],
				'refund_payment' => $request['api_refund'],
				'restock_items'  => $request['api_restock'],
			)
		);

		if ( is_wp_error( $refund ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', $refund->get_error_message(), 500 );
		}

		if ( ! $refund ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', __( 'Cannot create order refund, please try again.', 'woocommerce' ), 500 );
		}

		if ( ! empty( $request['meta_data'] ) ) {
			MetaDataUtil::update( $request['meta_data'], $refund );
			$refund->save_meta_data();
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $coupon   Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $refund, $request, $creating );
	}

	/**
	 * Create a refund with server-computed per-line totals (compute_totals mode).
	 *
	 * Mirrors the wc/v4 refund creation pipeline: line items may omit refund_total
	 * (computed from quantity at the order's stored unit price, tax-inclusive,
	 * clamped to the remaining refundable amount), input is validated against the
	 * order's refund history, and the refund amount is derived from the line items
	 * unless an explicit amount override is supplied. Error codes are intentionally
	 * identical to the wc/v4 creation endpoint (unprefixed) so clients can share
	 * error handling across both API versions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WC_Data The created refund, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @since 11.1.0
	 */
	private function create_refund_with_computed_totals( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		// wc_get_order can return a WC_Order_Refund for refund IDs — reject those
		// here since refunds are not refundable themselves.
		if ( ! $order instanceof WC_Order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Map the v3 public line-item shape ({id, quantity, refund_total, refund_tax})
		// to the schema format the shared calculation engine consumes ({line_item_id,
		// quantity, refund_total, refund_tax}), and strictly validate value types:
		// the REST layer cannot do it because the line_items schema property is
		// readonly for backward compatibility, so its args are not registered.
		$line_items = array();
		foreach ( (array) ( $request['line_items'] ?? array() ) as $line_item ) {
			if ( ! is_array( $line_item ) ) {
				return new WP_Error( 'invalid_line_item', __( 'Each line item must be an object.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			if ( isset( $line_item['id'] ) && ! isset( $line_item['line_item_id'] ) ) {
				$line_item['line_item_id'] = $line_item['id'];
				unset( $line_item['id'] );
			}

			$line_item = $this->normalize_line_item_types( $line_item );
			if ( is_wp_error( $line_item ) ) {
				return $line_item;
			}

			$line_items[] = $line_item;
		}

		// Fill in refund_total for any line items that omit it. The simplified
		// request form sends only {id, quantity}; the backend derives the
		// tax-inclusive total from the order's unit price × quantity. Scoped try:
		// compute_line_item_refund_total throws InvalidArgumentException on
		// quantity < 1, but fill_missing_refund_totals pre-checks that condition,
		// so this branch is defensive against a future invariant break only.
		try {
			$line_items = $this->data_utils()->fill_missing_refund_totals( $line_items, $order );
		} catch ( InvalidArgumentException $e ) {
			wc_get_logger()->error(
				sprintf( 'Refund creation invariant violation on order %d (%s): %s', $order->get_id(), get_class( $e ), $e->getMessage() ),
				array( 'source' => 'wc-rest-refunds' )
			);
			return new WP_Error(
				'invalid_refund_request',
				__( 'The refund could not be created due to an unexpected error.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// The WP_Error already carries its HTTP status (400/422) in the error data,
		// so create and preview return the same code for the same invalid input.
		$validation_error = $this->data_utils()->validate_line_items( $line_items, $order );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		// Convert line items to internal format. refund_total is tax-inclusive when no
		// explicit refund_tax is supplied (auto-computed values, or client values) — the
		// converter splits the tax portion out via the line's stored total/tax ratio.
		// When the client supplies an explicit refund_tax breakdown, refund_total is the
		// tax-exclusive subtotal and the tax is added on top (core Woo semantics).
		$line_item_data   = $this->data_utils()->convert_line_items_to_internal_format( $line_items, $order );
		$calculated_total = ! empty( $line_items ) ? $this->data_utils()->calculate_refund_amount( $line_items ) : 0;

		// has_param() distinguishes an omitted amount from an explicitly supplied one.
		// An explicit zero (including string forms like "0.00", which are truthy) must
		// be rejected rather than silently falling back to the calculated amount: a
		// request meaning "refund nothing" must never refund the full computed total.
		$has_amount    = $request->has_param( 'amount' );
		$refund_amount = $has_amount ? $request['amount'] : $calculated_total;

		if ( (float) $refund_amount <= 0 ) {
			return new WP_Error( 'invalid_refund_amount', __( 'Refund total must be greater than zero.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Prevent under-refunding: amount cannot be less than the calculated line items
		// total. Over-refunding is allowed for goodwill/compensation scenarios.
		if ( $has_amount && $calculated_total > 0 && NumberUtil::round( (float) $refund_amount, wc_get_price_decimals() ) < NumberUtil::round( $calculated_total, wc_get_price_decimals() ) ) {
			return new WP_Error(
				'invalid_refund_amount',
				sprintf(
					/* translators: %1$s: refund amount, %2$s: calculated total from line items */
					__( 'Refund amount (%1$s) cannot be less than the total of line items (%2$s).', 'woocommerce' ),
					wc_format_decimal( $refund_amount, wc_get_price_decimals() ),
					wc_format_decimal( $calculated_total, wc_get_price_decimals() )
				),
				array( 'status' => 400 )
			);
		}

		// Over-refunding line items is allowed (goodwill), but the amount can never
		// exceed the order's remaining refundable amount. Reject up-front with a clear
		// 422 rather than relying on wc_create_refund's generic failure.
		$remaining_refundable = (float) $order->get_remaining_refund_amount();
		if ( NumberUtil::round( (float) $refund_amount, wc_get_price_decimals() ) > NumberUtil::round( $remaining_refundable, wc_get_price_decimals() ) ) {
			return new WP_Error(
				'refund_exceeds_remaining',
				sprintf(
					/* translators: %1$s: requested refund amount, %2$s: remaining refundable amount */
					__( 'Refund amount (%1$s) exceeds the remaining refundable amount (%2$s).', 'woocommerce' ),
					wc_format_decimal( $refund_amount, wc_get_price_decimals() ),
					wc_format_decimal( $remaining_refundable, wc_get_price_decimals() )
				),
				array( 'status' => 422 )
			);
		}

		// Mirror the resolved values back onto the request so the pre_insert filter
		// below and any other downstream readers see the same internal-format
		// line_items and amount the legacy path exposes after
		// RestApiParameterUtil::adjust_create_refund_request_parameters().
		$request->set_param( 'line_items', $line_item_data );
		$request->set_param( 'amount', strval( $refund_amount ) );

		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $refund_amount,
				'reason'         => empty( $request['reason'] ) ? null : $request['reason'],
				'line_items'     => $line_item_data,
				'refund_payment' => is_bool( $request['api_refund'] ) ? $request['api_refund'] : true,
				'restock_items'  => is_bool( $request['api_restock'] ) ? $request['api_restock'] : true,
			)
		);

		if ( is_wp_error( $refund ) ) {
			return new WP_Error( 'cannot_create_refund', $refund->get_error_message(), array( 'status' => 400 ) );
		}

		if ( ! $refund ) {
			return new WP_Error( 'cannot_create_refund', __( 'Cannot create order refund.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $request['meta_data'] ) ) {
			MetaDataUtil::update( $request['meta_data'], $refund );
			$refund->save_meta_data();
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $refund   Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $refund, $request, true );
	}

	/**
	 * Get formatted item data.
	 * Invokes parents and then adds the proper Cost of Goods Sold information.
	 *
	 * @param  WC_Data $data_object WC_Data instance.
	 * @return array
	 * @since  9.9.0
	 */
	protected function get_formatted_item_data( $data_object ) {
		$data = parent::get_formatted_item_data( $data_object );
		if ( ! $this->cogs_is_enabled() ) {
			return $data;
		}

		if ( $data_object instanceof WC_Abstract_Order && $data_object->has_cogs() ) {
			$data['cost_of_goods_sold'] = array(
				'value' => $data_object->get_cogs_total_value(),
			);

			foreach ( $data['line_items'] as $key => $line_item ) {
				$cogs_value = $line_item['cogs_value'] ?? null;
				if ( ! is_null( $cogs_value ) ) {
					$data['line_items'][ $key ]['cost_of_goods_sold'] = array(
						'value' => $cogs_value,
					);
					unset( $data['line_items'][ $key ]['cogs_value'] );
				}
			}
		}
		return $data;
	}

	/**
	 * Get the refund schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['line_items']['items']['properties']['refund_total'] = array(
			'description' => __( 'Amount to refund for this line item. Tax-exclusive, with taxes supplied separately via refund_tax — except when compute_totals is true and refund_tax is omitted, in which case it is tax-inclusive. When compute_totals is true it may be omitted (or null) to have the server compute it from quantity.', 'woocommerce' ),
			'type'        => array( 'number', 'null' ),
			'context'     => array( 'edit' ),
		);

		$schema['properties']['line_items']['items']['properties']['taxes']['items']['properties']['refund_total'] = array(
			'description' => __( 'Amount that will be refunded for this tax.', 'woocommerce' ),
			'type'        => 'number',
			'context'     => array( 'edit' ),
			'readonly'    => true,
		);

		$schema['properties']['api_restock'] = array(
			'description' => __( 'When true, refunded items are restocked.', 'woocommerce' ),
			'type'        => 'boolean',
			'context'     => array( 'edit' ),
			'default'     => true,
		);

		$schema['properties']['compute_totals'] = array(
			'description' => __( 'When true, line items may omit refund_total and the server computes per-line refund amounts from quantities (tax-inclusive, using the order\'s stored unit prices and taxes, clamped to each line\'s remaining refundable amount), validating the request against the order\'s refund history. In this mode a refund_total supplied without refund_tax is treated as tax-inclusive, and amount (when provided) must be at least the computed line total and no more than the order\'s remaining refundable amount. Defaults to false, which preserves the pre-existing behavior of this endpoint.', 'woocommerce' ),
			'type'        => 'boolean',
			'context'     => array( 'edit' ),
			'default'     => false,
		);

		if ( $this->cogs_is_enabled() ) {
			$schema = $this->add_cogs_related_schema( $schema );
		}

		return $schema;
	}

	/**
	 * Add the Cost of Goods Sold related fields to the schema.
	 *
	 * @param array $schema The original schema.
	 * @return array The updated schema.
	 */
	private function add_cogs_related_schema( array $schema ): array {
		$schema['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Total value of the Cost of Goods Sold for the refund.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		$schema['properties']['line_items']['items']['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data. Only present for product refund line items.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Value of the Cost of Goods Sold for the refund item.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}
}
