<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Utility class for stock management related queries.
 */
class StockManagementHelper {

	/**
	 * Runtime cache for managed variations.
	 *
	 * @var array<int, array<int>>
	 */
	private array $managed_variations = array();

	/**
	 * Get a list of variations that inherit stock management from the parent.
	 *
	 * If the product is a variable product, we need sync the children that don't manage stock.
	 *
	 * @param WC_Product $product The product to check.
	 * @return array<int> Array of variation IDs that inherit stock management from the parent.
	 */
	public function get_managed_variations( WC_Product $product ): array {
		if ( ! $product->is_type( ProductType::VARIABLE ) ) {
			return array();
		}

		$product_id = $product->get_id();
		if ( isset( $this->managed_variations[ $product_id ] ) ) {
			return $this->managed_variations[ $product_id ];
		}

		$children = $product->get_children();
		if ( empty( $children ) ) {
			return array();
		}

		global $wpdb;

		$format           = array_fill( 0, count( $children ), '%d' );
		$query_in         = '(' . implode( ',', $format ) . ')';
		$managed_children = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '_manage_stock' AND meta_value != 'yes' AND post_id IN {$query_in}", $children ) ); // @codingStandardsIgnoreLine.

		$this->managed_variations[ $product_id ] = array_map( 'intval', $managed_children );

		return $this->managed_variations[ $product_id ];
	}
}
