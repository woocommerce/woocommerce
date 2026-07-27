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
		// it directly lets the REST server respond with that status. Error codes are
		// intentionally identical to the wc/v4 preview endpoint (unprefixed) so
		// clients can share error handling across both API versions.
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
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
				'invalid_preview_request',
				__( 'The refund preview could not be generated due to an unexpected error.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Refund preview unexpected error on order %d: %s', $order->get_id(), $e->getMessage() ),
				array( 'source' => 'wc-rest-refunds' )
			);
			return new WP_Error(
				'unexpected_preview_error',
				__( 'An unexpected error occurred while generating the refund preview.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// Reject a non-positive aggregate total up front. A refund of only a negative
		// discount line, or a product plus discount that nets to zero, would otherwise
		// preview successfully and then fail at create time.
		if ( (float) $preview['total'] <= 0 ) {
			return new WP_Error(
				'invalid_refund_amount',
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
				'preview_exceeds_max_refundable',
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
		 * @since 11.1.0
		 *
		 * @param array           $preview Preview data (breakdown, subtotal, tax, total, max_refundable).
		 * @param WC_Order        $order   The order the refund preview was computed for.
		 * @param WP_REST_Request $request The request.
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
	 * Prepares one object for create or update operation.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
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
			'description' => __( 'Amount that will be refunded for this line item (excluding taxes).', 'woocommerce' ),
			'type'        => 'number',
			'context'     => array( 'edit' ),
			'readonly'    => true,
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
