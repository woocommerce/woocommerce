<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Response Utils class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * ResponseUtils class.
 */
class ResponseUtils {

	/**
	 * Prepare an order for response.
	 *
	 * @param \WC_Order        $order Order data.
	 * @param \WP_REST_Request $request Request object.
	 * @param array            $fields Fields to include in the response.
	 * @return array
	 */
	public static function prepare_order_for_response( \WC_Order $order, \WP_REST_Request $request, array $fields ) {
		$dp             = is_null( $request['dp'] ) ? wc_get_price_decimals() : absint( $request['dp'] );
		$extra_fields   = array( 'currency_symbol', 'meta_data', 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines', 'refunds', 'payment_url', 'is_editable', 'needs_payment', 'needs_processing' );
		$format_decimal = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date    = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		// These fields are dependent on other fields.
		$dependent_fields = array(
			'date_created_gmt'   => 'date_created',
			'date_modified_gmt'  => 'date_modified',
			'date_completed_gmt' => 'date_completed',
			'date_paid_gmt'      => 'date_paid',
		);

		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Only fetch fields that we need.
		foreach ( $dependent_fields as $field_key => $dependency ) {
			if ( in_array( $field_key, $fields, true ) && ! in_array( $dependency, $fields, true ) ) {
				$fields[] = $dependency;
			}
		}

		$extra_fields   = array_intersect( $extra_fields, $fields );
		$format_decimal = array_intersect( $format_decimal, $fields );
		$format_date    = array_intersect( $format_date, $fields );

		$format_line_items = array_intersect( $format_line_items, $fields );

		$data = $order->get_base_data();

		// Add extra data as necessary.
		foreach ( $extra_fields as $field ) {
			switch ( $field ) {
				case 'currency_symbol':
					$data['currency_symbol'] = html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES );
					break;
				case 'meta_data':
					$data['meta_data'] = self::filter_internal_meta_keys( self::prepare_meta_data_for_response( $order->get_meta_data(), $request ) );
					break;
				case 'line_items':
					$data['line_items'] = $order->get_items( 'line_item' );
					break;
				case 'tax_lines':
					$data['tax_lines'] = $order->get_items( 'tax' );
					break;
				case 'shipping_lines':
					$data['shipping_lines'] = $order->get_items( 'shipping' );
					break;
				case 'fee_lines':
					$data['fee_lines'] = $order->get_items( 'fee' );
					break;
				case 'coupon_lines':
					$data['coupon_lines'] = $order->get_items( 'coupon' );
					break;
				case 'refunds':
					$data['refunds'] = array();
					foreach ( $order->get_refunds() as $refund ) {
						$data['refunds'][] = array(
							'id'     => $refund->get_id(),
							'reason' => $refund->get_reason() ? $refund->get_reason() : '',
							'total'  => '-' . wc_format_decimal( $refund->get_amount(), $dp ),
						);
					}
					break;
				case 'payment_url':
					$data['payment_url'] = $order->get_checkout_payment_url();
					break;
				case 'is_editable':
					$data['is_editable'] = $order->is_editable();
					break;
				case 'needs_payment':
					$data['needs_payment'] = $order->needs_payment();
					break;
				case 'needs_processing':
					$data['needs_processing'] = $order->needs_processing();
					break;
			}
		}

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $dp );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format the order status.
		$data['status'] = OrderUtil::remove_status_prefix( $data['status'] );

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( self::class, 'prepare_order_line_item_for_response' ), $data[ $key ], array_fill( 0, count( $data[ $key ] ), $request ) ) );
		}

		$data = array_intersect_key( $data, array_flip( $fields ) );

		ksort( $data );

		return $data;
	}

	/**
	 * Limit the contents of the meta_data property based on certain request parameters.
	 *
	 * Note that if both `include_meta` and `exclude_meta` are present in the request,
	 * `include_meta` will take precedence.
	 *
	 * @param array            $meta_data All of the meta data for an object.
	 * @param \WP_REST_Request $request   The request.
	 *
	 * @return array
	 */
	private static function prepare_meta_data_for_response( $meta_data, $request ) {
		$include = (array) $request['include_meta'];
		$exclude = (array) $request['exclude_meta'];

		if ( ! empty( $include ) ) {
			$meta_data = array_filter(
				$meta_data,
				function ( \WC_Meta_Data $item ) use ( $include ) {
					$data = $item->get_data();
					return in_array( $data['key'], $include, true );
				}
			);
		} elseif ( ! empty( $exclude ) ) {
			$meta_data = array_filter(
				$meta_data,
				function ( \WC_Meta_Data $item ) use ( $exclude ) {
					$data = $item->get_data();
					return ! in_array( $data['key'], $exclude, true );
				}
			);
		}

		// Ensure the array indexes are reset so it doesn't get converted to an object in JSON.
		return array_values( $meta_data );
	}

	/**
	 * Prepare an order line item for response.
	 *
	 * @param \WC_Order_Item   $item Order item data.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	private static function prepare_order_line_item_for_response( \WC_Order_Item $item, \WP_REST_Request $request ) {
		$data           = $item->get_data();
		$dp             = is_null( $request['dp'] ) ? wc_get_price_decimals() : absint( $request['dp'] );
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $dp );
			}
		}

		// Add SKU, PRICE, and IMAGE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']              = $item->get_product() ? $item->get_product()->get_sku() : null;
			$data['global_unique_id'] = $item->get_product() ? $item->get_product()->get_global_unique_id() : null;
			$data['price']            = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;

			$image_id      = $item->get_product() ? $item->get_product()->get_image_id() : 0;
			$data['image'] = array(
				'id'  => $image_id,
				'src' => $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '',
			);
		}

		// Add parent_name if the product is a variation.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$product = $item->get_product();

			if ( is_callable( array( $product, 'get_parent_data' ) ) ) {
				$data['parent_name'] = $product->get_title();
			} else {
				$data['parent_name'] = null;
			}
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => wc_format_decimal( $tax, $dp ),
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? wc_format_decimal( $data['taxes']['subtotal'][ $tax_rate_id ], $dp ) : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
			unset( $data['name'] );
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		// Expand meta_data to include user-friendly values.
		$formatted_meta_data = $item->get_all_formatted_meta_data( null );

		// Filter out product variations.
		if ( isset( $product ) && 'true' === $request['order_item_display_meta'] ) {
			$order_item_name   = $data['name'];
			$data['meta_data'] = array_filter(
				$data['meta_data'],
				function ( $meta ) use ( $product, $order_item_name ) {
					$display_value = wp_kses_post( rawurldecode( (string) $meta->value ) );

					// Skip items with values already in the product details area of the product name.
					if ( $product && $product->is_type( ProductType::VARIATION ) && wc_is_attribute_in_product_name( $display_value, $order_item_name ) ) {
						return false;
					}

					return true;
				}
			);
		}

		// Add additional applied coupon information.
		if ( $item instanceof \WC_Order_Item_Coupon ) {
			$temp_coupon = new \WC_Coupon();
			$coupon_info = $item->get_meta( 'coupon_info', true );
			if ( $coupon_info ) {
				$temp_coupon->set_short_info( $coupon_info );
			} else {
				$coupon_meta = $item->get_meta( 'coupon_data', true );
				if ( $coupon_meta ) {
					$temp_coupon->set_props( (array) $coupon_meta );
				}
			}

			$data['discount_type']  = $temp_coupon->get_discount_type();
			$data['nominal_amount'] = (float) $temp_coupon->get_amount();
			$data['free_shipping']  = $temp_coupon->get_free_shipping();
		}

		$data['meta_data'] = array_map(
			array( self::class, 'merge_meta_item_with_formatted_meta_display_attributes' ),
			$data['meta_data'],
			array_fill( 0, count( $data['meta_data'] ), $formatted_meta_data )
		);

		return $data;
	}

	/**
	 * Merge the `$formatted_meta_data` `display_key` and `display_value` attribute values into the corresponding
	 * {@link \WC_Meta_Data}. Returns the merged array.
	 *
	 * @param \WC_Meta_Data $meta_item           An object from {@link \WC_Order_Item::get_meta_data()}.
	 * @param array         $formatted_meta_data An object result from {@link \WC_Order_Item::get_all_formatted_meta_data}.
	 *  The keys are the IDs of {@link \WC_Meta_Data}.
	 *
	 * @return array
	 */
	private static function merge_meta_item_with_formatted_meta_display_attributes( $meta_item, $formatted_meta_data ) {
		$result = array(
			'id'            => $meta_item->id,
			'key'           => $meta_item->key,
			'value'         => $meta_item->value,
			'display_key'   => $meta_item->key,   // Default to original key, in case a formatted key is not available.
			'display_value' => $meta_item->value, // Default to original value, in case a formatted value is not available.
		);

		if ( array_key_exists( $meta_item->id, $formatted_meta_data ) ) {
			$formatted_meta_item = $formatted_meta_data[ $meta_item->id ];

			$result['display_key']   = wc_clean( $formatted_meta_item->display_key );
			$result['display_value'] = wc_clean( $formatted_meta_item->display_value );
		}

		return $result;
	}

	/**
	 * With HPOS, few internal meta keys such as _billing_address_index, _shipping_address_index are not considered internal anymore (since most internal keys were flattened into dedicated columns).
	 *
	 * This function helps in filtering out any remaining internal meta keys with HPOS is enabled.
	 *
	 * @param array $meta_data Order meta data.
	 * @return array Filtered order meta data.
	 */
	private static function filter_internal_meta_keys( $meta_data ) {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return $meta_data;
		}
		$cpt_hidden_keys = ( new \WC_Order_Data_Store_CPT() )->get_internal_meta_keys();
		$meta_data       = array_filter(
			$meta_data,
			function ( $meta ) use ( $cpt_hidden_keys ) {
				return ! in_array( $meta->key, $cpt_hidden_keys, true );
			}
		);
		return array_values( $meta_data );
	}
}
