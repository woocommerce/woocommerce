<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;
use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WooCommerce actions to register cache invalidation.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class CacheController implements RegisterHooksInterface {
	const CACHE_GROUP = 'filter_data';

	/**
	 * Transient key for the integer counter used to enforce the cache-entry cap.
	 *
	 * @since 10.8.0
	 */
	const CACHE_ENTRY_COUNT_TRANSIENT = 'wc_filter_data_entry_count';

	/**
	 * Instance of TaxonomyHierarchyData.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Initialize dependencies.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @param TaxonomyHierarchyData $taxonomy_hierarchy_data Instance of TaxonomyHierarchyData.
	 * @return void
	 */
	final public function init( TaxonomyHierarchyData $taxonomy_hierarchy_data ): void {
		$this->taxonomy_hierarchy_data = $taxonomy_hierarchy_data;
	}

	/**
	 * Hook into actions and filters.
	 */
	public function register() {
		if ( ! $this->need_cleanup() ) {
			return;
		}

		add_action( 'woocommerce_after_product_object_save', array( $this, 'invalidate_filter_data_cache' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'invalidate_filter_data_cache' ) );
		add_action( 'transition_post_status', array( $this, 'handle_transition_post_status' ), 10, 3 );

		// Clear taxonomy hierarchy cache when terms change.
		add_action( 'created_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );

		// Clear taxonomy hierarchy cache when term meta (like 'order') is added or updated.
		add_action( 'added_term_meta', array( $this, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10, 4 );
		add_action( 'updated_term_meta', array( $this, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10, 4 );
		add_action( 'deleted_term_meta', array( $this, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10, 4 );
	}

	/**
	 * Invalidate all cache under filter data group.
	 *
	 * Also resets the entry-count counter so the cap starts fresh.
	 *
	 * @since 10.8.0 Resets CACHE_ENTRY_COUNT_TRANSIENT on invalidation.
	 * @since 11.1.0 Fences object-cache prefix rotation with distinct transient generations.
	 */
	public function invalidate_filter_data_cache(): void {
		set_transient( self::CACHE_GROUP . '-transient-version', time() . '-' . wp_generate_uuid4() );
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
		set_transient( self::CACHE_GROUP . '-transient-version', time() . '-' . wp_generate_uuid4() );
		delete_transient( self::CACHE_ENTRY_COUNT_TRANSIENT );
	}

	/**
	 * Handle the transition_post_status hook.
	 *
	 * @internal
	 *
	 * @param string $new_status New post status.
	 * @param string $old_status Old post status.
	 * @param mixed  $post       Post object.
	 */
	public function handle_transition_post_status( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$was_published = 'publish' === $old_status;
		$is_published  = 'publish' === $new_status;

		if ( $was_published === $is_published ) {
			return;
		}

		$this->invalidate_filter_data_cache();
	}

	/**
	 * Clear taxonomy hierarchy cache when terms are created, updated, or deleted.
	 *
	 * @param int    $term_id          Term ID.
	 * @param int    $term_taxonomy_id Term taxonomy ID.
	 * @param string $taxonomy         Taxonomy slug.
	 */
	public function clear_taxonomy_hierarchy_cache( $term_id, $term_taxonomy_id, $taxonomy ) {
		// Only clear cache for hierarchical taxonomies.
		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$this->taxonomy_hierarchy_data->clear_cache( $taxonomy );
		}
	}

	/**
	 * Clear taxonomy hierarchy cache when term meta is updated.
	 * This handles the case when categories are reordered (updates 'order' meta).
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $term_id    Term ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 */
	public function clear_taxonomy_hierarchy_cache_on_meta_update( $meta_id, $term_id, $meta_key, $meta_value ): void {
		// Only clear cache when the 'order' meta key is updated (used for menu ordering).
		if ( 'order' !== $meta_key ) {
			return;
		}

		$term = get_term( $term_id );
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		$this->taxonomy_hierarchy_data->clear_cache( $term->taxonomy );
	}

	/**
	 * Delete all filter data transients.
	 */
	public function delete_filter_data_transients(): void {
		if ( ! $this->need_cleanup() ) {
			return;
		}

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_wc_filter_data_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_wc_filter_data_' ) . '%'
			)
		);
	}

	/**
	 * Check if the filter data cache should be cleaned up.
	 * If the cache group is not set, it means that the store is not using
	 * the product filters and we don't need to register the hooks.
	 *
	 * @return bool
	 */
	public function need_cleanup() {
		return ! empty( get_transient( self::CACHE_GROUP . '-transient-version' ) );
	}
}
