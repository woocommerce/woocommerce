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
	 * The override is new in 11.1.0 even though the parent method is not, hence
	 * the tag per the convention for public methods.
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

		// The shared engine runs the whole pipeline: normalize, validate, build,
		// and the aggregate guards. Its WP_Errors carry their HTTP status in the
		// error data and use unprefixed codes (the engine is convention neutral);
		// they are prefixed here at the v3 boundary so this endpoint follows the
		// `woocommerce_rest_*` convention of the rest of the v3 surface.
		$preview = $this->get_data_utils()->compute_refund_preview_or_error( $order, $request['line_items'], 'wc-rest-refunds' );

		if ( is_wp_error( $preview ) ) {
			return $this->prefix_error_code( $preview );
		}

		$preview = $this->add_preview_additional_fields( $preview, $request );

		$response = rest_ensure_response( $preview );

		/**
		 * Filters the refund preview response before it is returned, following the
		 * `woocommerce_rest_prepare_*` family contract. The preview is advisory:
		 * the create path re-validates independently, so filtered values cannot
		 * bypass the creation guards.
		 *
		 * @param WP_REST_Response $response The preview response. Its data carries
		 *                                   breakdown, subtotal, tax, total, max_refundable.
		 * @param WC_Order         $order    The order the refund preview was computed for.
		 * @param WP_REST_Request  $request  The request.
		 *
		 * @since 11.1.0
		 */
		return apply_filters( 'woocommerce_rest_prepare_order_refund_preview', $response, $order, $request );
	}

	/**
	 * Populate fields registered for the preview object type into a response.
	 *
	 * The stock add_additional_fields_to_object() resolves the object type from
	 * this controller's item schema (`order_refund`), so it would populate the
	 * wrong field set; the preview publishes its schema as
	 * `order_refund_preview` and must populate the fields registered for that
	 * type. Mirrors core's `_fields` handling: callbacks for fields the request
	 * excludes are not executed, so extension callbacks do not run for
	 * responses that will not carry their field. Runs before the response
	 * filter so filters see the complete payload.
	 *
	 * @param array           $preview Preview response data.
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	private function add_preview_additional_fields( array $preview, $request ): array {
		$additional_fields = $this->get_additional_fields( 'order_refund_preview' );

		if ( empty( $additional_fields ) ) {
			return $preview;
		}

		$fields_for_response = $this->get_preview_fields_for_response( $request );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( empty( $field_options['get_callback'] ) || ! is_callable( $field_options['get_callback'] ) ) {
				continue;
			}

			if ( ! in_array( $field_name, $fields_for_response, true ) ) {
				continue;
			}

			$preview[ $field_name ] = call_user_func( $field_options['get_callback'], $preview, $field_name, $request, 'order_refund_preview' );
		}

		return $preview;
	}

	/**
	 * Get the preview fields a request asks for, mirroring core's
	 * get_fields_for_response() against the preview schema instead of the
	 * controller's item schema.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string[]
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	private function get_preview_fields_for_response( $request ): array {
		$schema     = $this->get_public_preview_schema();
		$properties = isset( $schema['properties'] ) && is_array( $schema['properties'] ) ? $schema['properties'] : array();

		// For back-compat, include any registered field with an empty schema, as
		// core's get_fields_for_response() does: without a schema the field never
		// reaches the published properties, but its callback must still run.
		foreach ( $this->get_additional_fields( 'order_refund_preview' ) as $field_name => $field_options ) {
			if ( is_null( $field_options['schema'] ) ) {
				$properties[ $field_name ] = $field_options;
			}
		}

		$fields = array_map( 'strval', array_keys( $properties ) );

		if ( ! isset( $request['_fields'] ) || empty( $request['_fields'] ) ) {
			return $fields;
		}

		$requested_fields = array_map(
			static function ( $field ): string {
				return trim( (string) $field );
			},
			wp_parse_list( $request['_fields'] )
		);

		if ( 0 === count( $requested_fields ) ) {
			return $fields;
		}

		return array_values(
			array_filter(
				$fields,
				function ( string $field ) use ( $requested_fields ): bool {
					return rest_is_field_included( $field, $requested_fields );
				}
			)
		);
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

		// Like the sibling v3 schema getters: fields registered via
		// register_rest_field() must appear in the published schema.
		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the argument schema for the preview route's line_items parameter.
	 *
	 * Shared with the wc/v4 preview endpoint (including the line_item_id key
	 * naming) so clients can send the same payload to both API versions and
	 * the accepted shape cannot drift between them.
	 *
	 * Note the two deliberate differences from this controller's create
	 * endpoint: the preview keys lines by `line_item_id` where the create
	 * uses `id`, and the preview's `refund_total` is tax-inclusive where the
	 * create's classic `refund_total` is net with taxes supplied separately
	 * via `refund_tax` (the compute_totals create shares the preview's
	 * tax-inclusive semantics).
	 *
	 * @return array
	 */
	private function get_preview_line_items_arg_schema() {
		return $this->get_data_utils()->get_preview_line_items_arg_schema();
	}

	/**
	 * Normalize one compute_totals line item to the shared engine's shape.
	 *
	 * Maps the create endpoint's public `id` key to the engine's `line_item_id`
	 * and validates/normalizes the scalar types. The REST schema cannot validate
	 * the line_items subtree (the property is readonly for backward
	 * compatibility), so without this check malformed values such as an array
	 * refund_total would reach the calculation engine and fail with a TypeError
	 * instead of a 400 response. Uses the same error codes as the engine's own
	 * validation, and casts numeric strings to their proper types.
	 *
	 * @param array $line_item Line item in the public request shape (id keys).
	 * @return array|WP_Error The normalized line item, or WP_Error on an invalid type.
	 *
	 * @since 11.1.0
	 */
	private function normalize_line_item( array $line_item ) {
		// The create endpoint documents `id`; the shared engine and the preview
		// endpoint key lines by `line_item_id`, and either form is accepted here.
		// A payload carrying both is rejected: silently preferring one could
		// refund and restock a different line than the client intended.
		if ( isset( $line_item['id'], $line_item['line_item_id'] ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_line_item', __( 'Specify the line item with either id or line_item_id, not both.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( isset( $line_item['id'] ) ) {
			$line_item['line_item_id'] = $line_item['id'];
			unset( $line_item['id'] );
		}

		// IDs must be whole numbers (rest_is_integer): silently truncating a
		// fractional id such as 123.5 to 123 would target a different line or
		// tax bucket than requested.
		if ( isset( $line_item['line_item_id'] ) ) {
			if ( ! rest_is_integer( $line_item['line_item_id'] ) ) {
				return new WP_Error( 'woocommerce_rest_invalid_line_item', __( 'Line item id must be an integer.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['line_item_id'] = (int) $line_item['line_item_id'];
		}

		if ( isset( $line_item['quantity'] ) ) {
			if ( ! rest_is_integer( $line_item['quantity'] ) ) {
				return new WP_Error( 'woocommerce_rest_invalid_quantity', __( 'Quantity must be a whole number.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['quantity'] = (int) $line_item['quantity'];
		}

		if ( isset( $line_item['refund_total'] ) ) {
			if ( ! is_numeric( $line_item['refund_total'] ) ) {
				return new WP_Error( 'woocommerce_rest_invalid_refund_total', __( 'refund_total must be a number.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$line_item['refund_total'] = (float) $line_item['refund_total'];
		}

		if ( isset( $line_item['refund_tax'] ) ) {
			if ( ! is_array( $line_item['refund_tax'] ) ) {
				return new WP_Error( 'woocommerce_rest_invalid_line_item', __( 'refund_tax must be an array of objects with id and refund_total.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			foreach ( $line_item['refund_tax'] as $index => $tax ) {
				if ( ! is_array( $tax ) || ! isset( $tax['id'], $tax['refund_total'] ) || ! rest_is_integer( $tax['id'] ) || ! is_numeric( $tax['refund_total'] ) ) {
					return new WP_Error( 'woocommerce_rest_invalid_line_item', __( 'refund_tax entries must be objects with an integer id and a numeric refund_total.', 'woocommerce' ), array( 'status' => 400 ) );
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
	private function get_data_utils(): DataUtils {
		return wc_get_container()->get( DataUtils::class );
	}

	/**
	 * Prefix a shared-engine error code with `woocommerce_rest_`.
	 *
	 * DataUtils emits unprefixed codes (the wc/v4 convention). The wc/v3 surface
	 * uses `woocommerce_rest_*`, so errors crossing into a v3 response are
	 * renamed at this boundary. Codes that already carry the prefix pass through
	 * unchanged, and the message and data (including the HTTP status) are kept.
	 * An unprefixed engine error whose data carries no HTTP status is backfilled
	 * with 400, the same default the wc/v4 envelope applies, so it is not served
	 * as a 500. An already-prefixed code returns untouched above and so misses
	 * that backfill, which leaves no gap: every prefixed error reaching this
	 * endpoint is built by normalize_line_item() with an explicit status.
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

		// Every DataUtils error site attaches array data; the guard below is here
		// so a non-array payload cannot turn the $data['status'] write into a
		// fatal. Replacing such a payload rather than nesting it mirrors the wc/v4
		// envelope, which likewise reads a status only out of array data and drops
		// the rest, so both versions answer an identical error identically.
		$data = $error->get_error_data();
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 400;
		}

		return new WP_Error( 'woocommerce_rest_' . $code, $error->get_error_message(), $data );
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
	 * unless an explicit amount override is supplied. Validation follows the same
	 * rules as the wc/v4 creation endpoint; error codes are prefixed with
	 * `woocommerce_rest_` at this v3 boundary like the rest of the v3 surface.
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

		// Normalize each line to the engine's schema shape and validate value
		// types here: the REST layer cannot, because the line_items schema
		// property is readonly for backward compatibility, so its args are not
		// registered.
		$line_items = array();
		foreach ( (array) ( $request['line_items'] ?? array() ) as $line_item ) {
			if ( ! is_array( $line_item ) ) {
				return new WP_Error( 'woocommerce_rest_invalid_line_item', __( 'Each line item must be an object.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$line_item = $this->normalize_line_item( $line_item );
			if ( is_wp_error( $line_item ) ) {
				return $line_item;
			}

			$line_items[] = $line_item;
		}

		// The shared engine runs the whole creation preparation: fill missing
		// refund totals, validate against the order's refund history, convert to
		// the internal wc_create_refund() format, resolve the amount, and apply
		// the aggregate guards. Its WP_Errors carry their HTTP status in the
		// error data and use unprefixed codes; they are prefixed here at the v3
		// boundary like every other error the endpoint returns.
		$prepared = $this->get_data_utils()->prepare_refund_creation_or_error(
			$order,
			$line_items,
			$request->has_param( 'amount' ),
			$request['amount'],
			'wc-rest-refunds'
		);

		if ( is_wp_error( $prepared ) ) {
			return $this->prefix_error_code( $prepared );
		}

		$line_item_data = $prepared['line_items'];
		$refund_amount  = $prepared['amount'];

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

		// Same code and status as the legacy path above so a wc_create_refund
		// failure looks identical to clients regardless of the compute_totals flag.
		if ( is_wp_error( $refund ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', $refund->get_error_message(), array( 'status' => 500 ) );
		}

		if ( ! $refund ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', __( 'Cannot create order refund, please try again.', 'woocommerce' ), array( 'status' => 500 ) );
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

		$schema['properties']['compute_totals'] = array(
			'description' => __( 'When true, the server computes per-line refund amounts from quantities using the order\'s stored prices and taxes, validating the request against the order\'s refund history. Defaults to false, which preserves the pre-existing behavior of this endpoint.', 'woocommerce' ),
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
