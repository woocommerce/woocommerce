<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @package WooCommerce\RestApi
 * @since    2.6.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Internal\Utilities\Users;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\StringUtil;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Orders_V2_Controller
 */
class WC_REST_Orders_Controller extends WC_REST_Orders_V2_Controller {
	use CogsAwareTrait;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Calculate coupons.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param WP_REST_Request $request Request object.
	 * @param WC_Order        $order   Order data.
	 * @return bool
	 */
	protected function calculate_coupons( $request, $order ) {
		if ( ! isset( $request['coupon_lines'] ) ) {
			return false;
		}

		// Validate input and at the same time store the processed coupon codes to apply.

		$coupon_codes = array();
		$discounts    = new WC_Discounts( $order );

		$current_order_coupons      = array_values( $order->get_coupons() );
		$current_order_coupon_codes = array_map(
			function ( $coupon ) {
				return wc_strtolower( $coupon->get_code() );
			},
			$current_order_coupons
		);

		foreach ( $request['coupon_lines'] as $item ) {
			if ( ! empty( $item['id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_coupon_item_id_readonly', __( 'Coupon item ID is readonly.', 'woocommerce' ), 400 );
			}

			$coupon_code = ArrayUtil::get_value_or_default( $item, 'code' );
			if ( StringUtil::is_null_or_whitespace( $coupon_code ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon', __( 'Coupon code is required.', 'woocommerce' ), 400 );
			}

			$coupon_code = wc_format_coupon_code( wc_clean( $coupon_code ) );
			$coupon      = new WC_Coupon( $coupon_code );

			// Skip check if the coupon is already applied to the order, as this could wrongly throw an error for single-use coupons.
			if ( ! in_array( wc_strtolower( $coupon_code ), $current_order_coupon_codes, true ) ) {
				$check_result = $discounts->is_coupon_valid( $coupon );
				if ( is_wp_error( $check_result ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_' . $check_result->get_error_code(), $check_result->get_error_message(), 400 );
				}
			}

			$coupon_codes[] = $coupon_code;
		}

		// Remove all coupons first to ensure calculation is correct.
		foreach ( $order->get_items( 'coupon' ) as $existing_coupon ) {
			$order->remove_coupon( $existing_coupon->get_code() );
		}

		// Apply the coupons.
		foreach ( $coupon_codes as $new_coupon ) {
			$results = $order->apply_coupon( $new_coupon );

			if ( is_wp_error( $results ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_' . $results->get_error_code(), $results->get_error_message(), 400 );
			}
		}

		return true;
	}

	/**
	 * Prepare a single order for create or update.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'created_via':
						// Created via is only writable on order creation.
						if ( ! $creating ) {
							unset( $request[ $key ] );
						}
						break;
					case 'coupon_lines':
					case 'status':
						// Change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$this->remove_item( $order, $key, $item['id'] );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						break;
					default:
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @since 7.4.0
		 *
		 * @param WC_Data         $order    Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );
	}

	/**
	 * Create or update a line item, overridden to add COGS data as needed.
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Product
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = null ) {
		$prepared = parent::prepare_line_items( $posted, $action, $item );

		if ( ! $prepared->has_cogs() || ! $this->cogs_is_enabled() ) {
			return $prepared;
		}

		$cogs_value = $posted['cost_of_goods_sold']['value'] ?? null;
		if ( ! is_null( $cogs_value ) ) {
			$prepared->set_cogs_value( (float) $cogs_value );
		}

		return $prepared;
	}

	/**
	 * Wrapper method to remove order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param WC_Order $order     The order to remove the item from.
	 * @param string   $item_type The item type (from the request, not from the item, e.g. 'line_items' rather than 'line_item').
	 * @param int      $item_id   The ID of the item to remove.
	 *
	 * @return void
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function remove_item( WC_Order $order, string $item_type, int $item_id ): void {
		$item = $order->get_item( $item_id );

		if ( ! $item ) {
			throw new WC_REST_Exception(
				'woocommerce_rest_invalid_item_id',
				esc_html__( 'Order item ID provided is not associated with order.', 'woocommerce' ),
				400
			);
		}

		if ( 'line_items' === $item_type ) {
			require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
			wc_maybe_adjust_line_item_product_stock( $item, 0 );
		}

		/**
		 * Allow extensions be notified before the item is removed.
		 *
		 * @param WC_Order_Item $item The item object.
		 *
		 * @since 9.3.0.
		 */
		do_action( 'woocommerce_rest_remove_order_item', $item );

		$order->remove_item( $item_id );
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		$object = null;
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			// Make sure gateways are loaded so hooks from gateways fire on save/create.
			WC()->payment_gateways();

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// The customer must exist, and in a multisite context must be visible to the current user.
				if ( is_wp_error( Users::get_user_in_current_site( $request['customer_id'] ) ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}
			}

			if ( $creating ) {
				$object->set_created_via( ! empty( $request['created_via'] ) ? sanitize_text_field( wp_unslash( $request['created_via'] ) ) : 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$object->save();
				$object->calculate_totals();
			} else {
				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) || isset( $request['coupon_lines'] ) ) {
					$object->calculate_totals( true );
				}
			}

			// Set coupons.
			$this->calculate_coupons( $request, $object );

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$manual_update = isset( $request['manual_update'] ) ? $request['manual_update'] : false;
				$object->set_status( $request['status'], '', $manual_update );
			}

			$object->save();

			// Actions for after the order is saved.
			if ( true === $request['set_paid'] ) {
				if ( $creating || $object->needs_payment() ) {
					$object->payment_complete( $request['transaction_id'] );
				}
			}

			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			$data = $e->getErrorData();

			if ( $creating && $object && $object->get_id() ) {
				try {
					$object->set_status( 'checkout-draft' );
					$object->save();
					// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $_ ) {
					// We don't want a failure in changing the order status
					// to throw on itself, but we don't have anything meaningful
					// to do with this failure either.
				}

				$data['new_draft_order_id'] = $object->get_id();
			}

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $data );
		}
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		// This is needed to get around an array to string notice in WC_REST_Orders_V2_Controller::prepare_objects_query.
		$statuses = $request['status'];
		unset( $request['status'] );

		$args = parent::prepare_objects_query( $request );

		$args['post_status'] = array();
		foreach ( $statuses as $status ) {
			if ( in_array( $status, $this->get_order_statuses(), true ) ) {
				$args['post_status'][] = 'wc-' . $status;
			} elseif ( 'any' === $status ) {
				// Set status to "any" and short-circuit out.
				$args['post_status'] = 'any';
				break;
			} else {
				$args['post_status'][] = $status;
			}
		}

		// If created_via filter is provided, add it to query args.
		if ( ! empty( $request['created_via'] ) ) {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args['created_via'] = $request['created_via'];
			} else {
				$args['meta_query'][] = array(
					'key'     => '_created_via',
					'value'   => $request['created_via'],
					'compare' => 'IN',
				);
			}
		}

		// Put the statuses back for further processing (next/prev links, etc).
		$request['status'] = $statuses;

		return $args;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['created_via']['readonly'] = false;

		$schema['properties']['coupon_lines']['items']['properties']['discount']['readonly'] = true;

		$schema['properties']['manual_update'] = array(
			'default'     => false,
			'description' => __( 'Set the action as manual so that the order note registers as "added by user".', 'woocommerce' ),
			'type'        => 'boolean',
			'context'     => array( 'edit' ),
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
					'description' => __( 'Total value of the Cost of Goods Sold for the order.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		$schema['properties']['line_items']['items']['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data. Only present for product line items.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Value of the Cost of Goods Sold for the order item.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders which have specific statuses.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any', OrderStatus::TRASH ), $this->get_order_statuses() ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['created_via'] = array(
			'description'       => __( 'Limit result set to orders created via specific sources (e.g. checkout, admin).', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
			),
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => 'wp_parse_list',
		);

		return $params;
	}

	/**
	 * Core method to prepare a single order object for response
	 * (doesn't fire hooks, execute rest_ensure_response, or add links).
	 *
	 * @param  WC_Data         $order  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return array Prepared response data.
	 * @since  9.5.0
	 */
	protected function prepare_object_for_response_core( $order, $request ): array {
		$cogs_is_enabled = $this->cogs_is_enabled();

		$data = parent::prepare_object_for_response_core( $order, $request );

		if ( isset( $data['line_items'] ) ) {
			foreach ( $data['line_items'] as &$line_item_data ) {
				if ( isset( $line_item_data['cogs_value'] ) ) {
					if ( $cogs_is_enabled ) {
						$line_item_data['cost_of_goods_sold']['value'] = $line_item_data['cogs_value'];
					}
					unset( $line_item_data['cogs_value'] );
				}
			}
		}

		if ( $cogs_is_enabled ) {
			$data['cost_of_goods_sold']['total_value'] = $order->get_cogs_total_value();
		}

		return $data;
	}
}
