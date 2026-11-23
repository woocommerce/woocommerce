<?php
/**
 * OrderLineMetaValidator class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\MetaDataUtil;
use WC_REST_Exception;
use WP_Error;
use WP_REST_Request;

/**
 * Validates the line payloads of the order endpoints (`line_items`, `shipping_lines`, `fee_lines`),
 * for every REST API version.
 *
 * Class OrderLineMetaValidator
 *
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders
 */
class OrderLineMetaValidator {

	/**
	 * Meta key that WC_Data::update_meta_data() diverts to the order item's set_taxes(), which runs
	 * the value through maybe_unserialize(). Only serialized values are rejected.
	 *
	 * Line, fee and shipping items all carry a `taxes` data prop, so reading an existing item promotes
	 * `_taxes` to an internal meta key and both `taxes` and `_taxes` divert to the tax setter.
	 */
	private const GUARDED_META_KEY = 'taxes';

	/**
	 * Validates an order line request argument (`line_items`, `shipping_lines` or `fee_lines`).
	 *
	 * @since 11.1.0
	 *
	 * @param mixed                                 $value   Value of the argument.
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @param string                                $param   Name of the argument.
	 * @return true|WP_Error Error when a line posts a serialized value under the guarded key.
	 */
	public static function validate_request_arg( $value, $request, $param ) {
		$valid = rest_validate_request_arg( $value, $request, $param );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		if ( ! is_array( $value ) ) {
			return true;
		}

		foreach ( $value as $line ) {
			if ( is_array( $line ) && self::has_serialized_meta_value( $line['meta_data'] ?? null ) ) {
				return new WP_Error(
					'woocommerce_rest_invalid_order_item_meta_key',
					self::get_serialized_meta_value_error_message(),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Rejects a serialized value under the guarded meta key while preparing an order line.
	 *
	 * Covers requests that skip request argument validation, such as the ones the batch endpoint builds.
	 *
	 * @since 11.1.0
	 *
	 * @param array $meta_data `meta_data` payload from the request. Cast at the call site, which can receive a non-array.
	 * @throws WC_REST_Exception When the payload carries a serialized value under the guarded key.
	 */
	public static function assert_no_serialized_meta_value( array $meta_data ): void {
		if ( self::has_serialized_meta_value( $meta_data ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_order_item_meta_key', esc_html( self::get_serialized_meta_value_error_message() ), 400 );
		}
	}

	/**
	 * Checks whether a `meta_data` payload carries a serialized value under the guarded meta key.
	 *
	 * @param mixed $meta_data Raw `meta_data` value from the request.
	 * @return bool
	 */
	private static function has_serialized_meta_value( $meta_data ): bool {
		if ( ! is_array( $meta_data ) ) {
			return false;
		}

		foreach ( MetaDataUtil::normalize( $meta_data ) as $meta ) {
			// update_meta_data() resolves the setter from ltrim( $key, '_' ), so `_taxes` diverts too.
			if ( ! is_string( $meta['key'] ) || self::GUARDED_META_KEY !== ltrim( $meta['key'], '_' ) ) {
				continue;
			}

			// Keep scanning: the payload can repeat the key, and every entry reaches the setter.
			if ( is_serialized( $meta['value'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the error message for a rejected payload.
	 *
	 * @return string
	 */
	private static function get_serialized_meta_value_error_message(): string {
		return sprintf(
			/* translators: %s: order item meta key. */
			__( 'The "%s" order line meta key cannot hold a serialized value.', 'woocommerce' ),
			self::GUARDED_META_KEY
		);
	}
}
