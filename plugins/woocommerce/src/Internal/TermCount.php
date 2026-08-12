<?php
/**
 * TermCount class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Proxies\LegacyProxy;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains WooCommerce-specific product term counts after taxonomy changes.
 *
 * WooCommerce excludes some products from category, tag, and brand counts based on
 * product visibility. WordPress does not automatically refresh those custom counts
 * when visibility relationships are removed directly, so this service detects
 * count-affecting relationship deletions and delegates to WooCommerce's existing
 * recount logic.
 *
 * @since 11.1.0
 *
 * @internal
 */
class TermCount {
	/**
	 * Proxy for WordPress and legacy WooCommerce functions.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @since 11.1.0
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Proxy for WordPress and legacy WooCommerce functions.
	 */
	final public function init( LegacyProxy $legacy_proxy ): void {
		$this->legacy_proxy = $legacy_proxy;

		add_action( 'deleted_term_relationships', array( $this, 'handle_deleted_term_relationships' ), 10, 3 );
	}

	/**
	 * Recounts product terms after count-affecting visibility relationships are deleted.
	 *
	 * @since 11.1.0
	 *
	 * @internal
	 *
	 * @param mixed $object_id Object ID.
	 * @param mixed $tt_ids    Deleted term taxonomy IDs.
	 * @param mixed $taxonomy  Taxonomy slug.
	 */
	public function handle_deleted_term_relationships( $object_id, $tt_ids, $taxonomy ): void {
		$object_id = absint( $object_id );

		if ( 'product_visibility' !== $taxonomy || 0 === $object_id || ! is_array( $tt_ids ) ) {
			return;
		}

		if (
			! empty(
				array_intersect(
					$this->normalize_ids( $tt_ids ),
					$this->get_count_affecting_visibility_term_taxonomy_ids()
				)
			)
		) {
			$this->legacy_proxy->call_function( '_wc_recount_terms_by_product', $object_id );
		}
	}

	/**
	 * Gets visibility term taxonomy IDs that affect WooCommerce product counts.
	 *
	 * @return list<int>
	 */
	private function get_count_affecting_visibility_term_taxonomy_ids(): array {
		if ( 'yes' !== $this->legacy_proxy->call_function( 'get_option', 'woocommerce_hide_out_of_stock_items' ) ) {
			return array();
		}

		/**
		 * Product visibility term taxonomy IDs.
		 *
		 * @var array<string, int> $visibility_term_ids
		 */
		$visibility_term_ids = $this->legacy_proxy->call_function( 'wc_get_product_visibility_term_ids' );

		return $this->normalize_ids( array( $visibility_term_ids[ ProductStockStatus::OUT_OF_STOCK ] ?? 0 ) );
	}

	/**
	 * Normalizes arbitrary values to a list of positive integer IDs.
	 *
	 * @param array<mixed> $ids Values to normalize.
	 * @return list<int>
	 */
	private function normalize_ids( array $ids ): array {
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}
}
