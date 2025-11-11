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
		add_action( 'woocommerce_after_product_object_save', array( $this, 'clear_filter_data_cache' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'clear_filter_data_cache' ) );

		// Clear taxonomy hierarchy cache when terms change.
		add_action( 'created_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_taxonomy_hierarchy_cache' ), 10, 3 );
	}

	/**
	 * Invalidate all cache under filter data group.
	 */
	public function clear_filter_data_cache() {
		WC_Cache_Helper::get_transient_version( self::CACHE_GROUP, true );
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
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
}
