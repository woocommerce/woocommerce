<?php
/**
 * Order line item validation for the REST API.
 *
 * @package WooCommerce\Internal\RestApi
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RestApi;

/**
 * Validation rules for line items sent to the REST API order endpoints, shared by every API version.
 */
class OrderLineItemValidator {

	/**
	 * Checks whether a line item is a custom item: one with no product, but a name and a total.
	 *
	 * @since 11.4.0
	 *
	 * @param array $line_item Line item data from the request.
	 * @return bool
	 */
	public static function is_custom_line_item( $line_item ): bool {
		$has_product_reference = ! empty( $line_item['product_id'] ) || ! empty( $line_item['variation_id'] ) || ! empty( $line_item['sku'] );
		$has_name              = isset( $line_item['name'] ) && is_string( $line_item['name'] ) && '' !== trim( $line_item['name'] );
		$has_total             = isset( $line_item['total'] ) && is_numeric( wc_format_decimal( $line_item['total'] ) );

		return ! $has_product_reference && $has_name && $has_total;
	}
}
