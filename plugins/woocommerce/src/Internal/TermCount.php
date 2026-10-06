<?php
/**
 * TermCount class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates WooCommerce-specific product term-count consistency.
 *
 * Product term counts can become stale when product visibility, stock state,
 * inventory settings, product type, or taxonomy hierarchy changes. This service is
 * the central integration point for incrementally consolidating the immediate and
 * deferred recount triggers for those mutations. Existing WooCommerce term-count
 * functions remain responsible for calculating and persisting category, tag, brand,
 * and ancestor counts.
 *
 * @since 11.2.0
 *
 * @internal
 */
class TermCount {
	/**
	 * Class initialization, executed when the class is resolved by the container.
	 *
	 * @since 11.2.0
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'get_term', array( $this, 'handle_get_term' ), 10, 2 );
		add_action( 'set_object_terms', array( $this, 'handle_set_object_terms' ), 10, 6 );
		add_action( 'deleted_term_relationships', array( $this, 'handle_deleted_term_relationships' ), 10, 3 );
	}

	/**
	 * Override a single product term's count to take catalog visibility into account.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 *
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 * @return WP_Term
	 */
	public function handle_get_term( $term, $taxonomy ) {
		if ( is_admin() || wp_doing_ajax() || ! $term instanceof WP_Term ) {
			return $term;
		}

		/**
		 * Filter which product taxonomies should have their term counts overridden to take catalog visibility into account.
		 *
		 * @since 2.1.0
		 *
		 * @param array $valid_taxonomies List of taxonomy slugs.
		 */
		$valid_taxonomies = apply_filters( 'woocommerce_change_term_counts', array( 'product_cat', 'product_tag', 'product_brand' ) );
		if ( ! is_array( $valid_taxonomies ) || ! in_array( $taxonomy, $valid_taxonomies, true ) ) {
			return $term;
		}

		$count       = get_term_meta( $term->term_id, 'product_count_' . $term->taxonomy, true );
		$term->count = '' !== $count ? absint( $count ) : 0;

		return $term;
	}

	/**
	 * Recounts product terms after count-affecting visibility relationships are added.
	 *
	 * Removals are recounted by handle_deleted_term_relationships(), which runs before
	 * the set_object_terms hook during wp_set_object_terms().
	 *
	 * @since 11.2.0
	 *
	 * @internal
	 *
	 * @param int               $object_id  Object ID.
	 * @param array<int|string> $terms      An array of object term IDs or slugs.
	 * @param array<int|string> $tt_ids     An array of term taxonomy IDs.
	 * @param string            $taxonomy   Taxonomy slug.
	 * @param bool              $append     Whether to append new terms to the old terms.
	 * @param array<int|string> $old_tt_ids The old array of term taxonomy IDs.
	 */
	public function handle_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
		$object_id = absint( $object_id );

		if ( 'product_visibility' !== $taxonomy || 0 === $object_id || ! is_array( $tt_ids ) || ! is_array( $old_tt_ids ) ) {
			return;
		}

		$new_tt_ids      = $this->normalize_ids( $tt_ids );
		$old_tt_ids      = $this->normalize_ids( $old_tt_ids );
		$counting_tt_ids = $this->get_count_affecting_visibility_term_taxonomy_ids();

		$removed_tt_ids = $append ? array() : array_diff( $old_tt_ids, $new_tt_ids );

		// When the removed term taxonomy IDs include any count-affecting visibility terms, the recount will be handled by handle_deleted_term_relationships().
		if ( ! empty( array_intersect( $removed_tt_ids, $counting_tt_ids ) ) ) {
			return;
		}

		$added_tt_ids = $append ? $new_tt_ids : array_diff( $new_tt_ids, $old_tt_ids );

		if ( ! empty( array_intersect( $added_tt_ids, $counting_tt_ids ) ) ) {
			_wc_recount_terms_by_product( $object_id );
		}
	}

	/**
	 * Recounts product terms after count-affecting visibility relationships are deleted.
	 *
	 * @since 11.2.0
	 *
	 * @internal
	 *
	 * @param int               $object_id Object ID.
	 * @param array<int|string> $tt_ids    Deleted term taxonomy IDs.
	 * @param string            $taxonomy  Taxonomy slug.
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
			_wc_recount_terms_by_product( $object_id );
		}
	}

	/**
	 * Gets visibility term taxonomy IDs that affect WooCommerce product counts.
	 *
	 * @return list<int>
	 */
	private function get_count_affecting_visibility_term_taxonomy_ids(): array {
		/**
		 * Product visibility term taxonomy IDs.
		 *
		 * @var array $visibility_term_ids
		 */
		$visibility_term_ids = wc_get_product_visibility_term_ids();
		$counting_tt_ids     = array( $visibility_term_ids['exclude-from-catalog'] ?? 0 );

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$counting_tt_ids[] = $visibility_term_ids[ ProductStockStatus::OUT_OF_STOCK ] ?? 0;
		}

		return $this->normalize_ids( $counting_tt_ids );
	}

	/**
	 * Normalizes arbitrary values to a list of positive integer IDs.
	 *
	 * @param array<int|string> $ids Values to normalize.
	 * @return list<int>
	 */
	private function normalize_ids( array $ids ): array {
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}
}
