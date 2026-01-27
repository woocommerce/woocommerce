<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Product version string invalidation handler.
 *
 * This class provides an 'invalidate' method that will invalidate
 * the version string for a given product, which in turn invalidates
 * any cached REST API responses containing that product.
 */
class ProductVersionStringInvalidator {

	/**
	 * Default cache TTL in seconds for term/taxonomy entity lookups.
	 */
	const DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL = 300;

	/**
	 * Initialize the invalidator and register hooks.
	 *
	 * Hooks are only registered when both conditions are met:
	 * - The REST API caching feature is enabled
	 * - The backend caching setting is active
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	final public function init(): void {
		// We can't use FeaturesController::feature_is_enabled at this point
		// (before the 'init' action is triggered) because that would cause
		// "Translation loading for the woocommerce domain was triggered too early" warnings.
		if ( 'yes' !== get_option( 'woocommerce_feature_rest_api_caching_enabled' ) ) {
			return;
		}

		if ( 'yes' === get_option( 'woocommerce_rest_api_enable_backend_caching', 'no' ) ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register all product-related hooks.
	 *
	 * Registers ALL hooks (WordPress and WooCommerce) to ensure comprehensive coverage.
	 * This handles both standard data stores and custom implementations, as well as
	 * third-party plugins that may use direct SQL with manual hook firing.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// WordPress post hooks for products.
		add_action( 'save_post_product', array( $this, 'handle_save_post_product' ), 10, 1 );
		add_action( 'delete_post', array( $this, 'handle_delete_post' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'handle_trashed_post' ), 10, 1 );
		add_action( 'untrashed_post', array( $this, 'handle_untrashed_post' ), 10, 1 );

		// WooCommerce CRUD hooks for products.
		add_action( 'woocommerce_new_product', array( $this, 'handle_woocommerce_new_product' ), 10, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'handle_woocommerce_update_product' ), 10, 1 );
		add_action( 'woocommerce_before_delete_product', array( $this, 'handle_woocommerce_before_delete_product' ), 10, 1 );
		add_action( 'woocommerce_trash_product', array( $this, 'handle_woocommerce_trash_product' ), 10, 1 );

		// WooCommerce CRUD hooks for variations.
		add_action( 'woocommerce_new_product_variation', array( $this, 'handle_woocommerce_new_product_variation' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'handle_woocommerce_update_product_variation' ), 10, 2 );
		add_action( 'woocommerce_before_delete_product_variation', array( $this, 'handle_woocommerce_before_delete_product_variation' ), 10, 1 );
		add_action( 'woocommerce_trash_product_variation', array( $this, 'handle_woocommerce_trash_product_variation' ), 10, 1 );

		// SQL-level operation hooks.
		add_action( 'woocommerce_updated_product_stock', array( $this, 'handle_woocommerce_updated_product_stock' ), 10, 1 );
		add_action( 'woocommerce_updated_product_price', array( $this, 'handle_woocommerce_updated_product_price' ), 10, 1 );
		add_action( 'woocommerce_updated_product_sales', array( $this, 'handle_woocommerce_updated_product_sales' ), 10, 1 );

		// Attribute-related hooks (only for CPT data store).
		// These hooks use direct SQL queries that assume CPT storage.
		if ( $this->is_using_cpt_data_store() ) {
			add_action( 'woocommerce_attribute_updated', array( $this, 'handle_woocommerce_attribute_updated' ), 10, 2 );
			add_action( 'woocommerce_attribute_deleted', array( $this, 'handle_woocommerce_attribute_deleted' ), 10, 3 );
			add_action( 'woocommerce_updated_product_attribute_summary', array( $this, 'handle_woocommerce_updated_product_attribute_summary' ), 10, 1 );
			add_action( 'edited_term', array( $this, 'handle_edited_term' ), 10, 3 );
		}
	}

	/**
	 * Check if the product data store is CPT-based.
	 *
	 * @return bool True if using CPT data store, false otherwise.
	 */
	private function is_using_cpt_data_store(): bool {
		$data_store = \WC_Data_Store::load( 'product' );
		return $data_store->get_current_class_name() === 'WC_Product_Data_Store_CPT';
	}

	/**
	 * Handle the save_post_product hook.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_save_post_product( $post_id ): void {
		$post_id = (int) $post_id;

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->invalidate( $post_id );
	}

	/**
	 * Handle the delete_post hook.
	 *
	 * @param int           $post_id The post ID.
	 * @param \WP_Post|null $post The post object, or null if not provided.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_delete_post( $post_id, $post = null ): void {
		$post_id = (int) $post_id;

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( $post_id );
		}

		if ( ! $post ) {
			return;
		}

		if ( 'product_variation' === $post->post_type ) {
			$this->invalidate_variation_and_parent( $post_id, (int) $post->post_parent );
		} elseif ( 'product' === $post->post_type ) {
			$this->invalidate( $post_id );
		}
	}

	/**
	 * Handle the trashed_post hook.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_trashed_post( $post_id ): void {
		$this->handle_trashed_or_untrashed_post( (int) $post_id );
	}

	/**
	 * Handle the untrashed_post hook.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_untrashed_post( $post_id ): void {
		$this->handle_trashed_or_untrashed_post( (int) $post_id );
	}

	/**
	 * Handle the trashed_post and untrashed_post hooks.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	private function handle_trashed_or_untrashed_post( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		if ( 'product_variation' === $post->post_type ) {
			$this->invalidate_variation_and_parent( $post_id, $post->post_parent );
		} elseif ( 'product' === $post->post_type ) {
			$this->invalidate( $post_id );
		}
	}

	/**
	 * Handle the woocommerce_new_product_variation hook.
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_new_product_variation( $variation_id, $variation ): void {
		$variation_id = (int) $variation_id;
		$parent_id    = $variation instanceof \WC_Product ? $variation->get_parent_id() : null;
		$this->invalidate_variation_and_parent( $variation_id, $parent_id );
	}

	/**
	 * Handle the woocommerce_update_product_variation hook.
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_update_product_variation( $variation_id, $variation ): void {
		$variation_id = (int) $variation_id;
		$parent_id    = $variation instanceof \WC_Product ? $variation->get_parent_id() : null;
		$this->invalidate_variation_and_parent( $variation_id, $parent_id );
	}

	/**
	 * Handle the woocommerce_new_product hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_new_product( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_update_product hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_update_product( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_before_delete_product hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_before_delete_product( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_trash_product hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_trash_product( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_before_delete_product_variation hook.
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_before_delete_product_variation( $variation_id ): void {
		$this->invalidate_variation_and_parent( (int) $variation_id );
	}

	/**
	 * Handle the woocommerce_trash_product_variation hook.
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_trash_product_variation( $variation_id ): void {
		$this->invalidate_variation_and_parent( (int) $variation_id );
	}

	/**
	 * Handle the woocommerce_updated_product_stock hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_updated_product_stock( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_updated_product_price hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_updated_product_price( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_updated_product_sales hook.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_updated_product_sales( $product_id ): void {
		$this->invalidate( (int) $product_id );
	}

	/**
	 * Handle the woocommerce_attribute_updated hook.
	 *
	 * @param int   $id The attribute ID.
	 * @param array $data The attribute data.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_attribute_updated( $id, $data ): void {
		if ( ! is_array( $data ) || ! isset( $data['attribute_name'] ) ) {
			return;
		}

		$taxonomy = wc_attribute_taxonomy_name( $data['attribute_name'] );
		$this->invalidate_products_with_attribute( $taxonomy );
	}

	/**
	 * Handle the woocommerce_attribute_deleted hook.
	 *
	 * @param int    $id The attribute ID.
	 * @param string $name The attribute name.
	 * @param string $taxonomy The attribute taxonomy.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_attribute_deleted( $id, $name, $taxonomy ): void {
		if ( ! is_string( $taxonomy ) || '' === $taxonomy ) {
			return;
		}

		$this->invalidate_products_with_attribute( $taxonomy );
	}

	/**
	 * Handle the woocommerce_updated_product_attribute_summary hook.
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_updated_product_attribute_summary( $variation_id ): void {
		$this->invalidate_variation_and_parent( (int) $variation_id );
	}

	/**
	 * Handle the edited_term hook.
	 *
	 * @param int    $term_id The term ID.
	 * @param int    $tt_id The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 */
	public function handle_edited_term( $term_id, $tt_id, $taxonomy ): void {
		if ( ! is_string( $taxonomy ) ) {
			return;
		}

		// Only handle product attribute taxonomies.
		if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		$this->invalidate_products_with_term( (int) $tt_id );
	}

	/**
	 * Invalidate a variation and its parent product.
	 *
	 * @param int      $variation_id The variation ID.
	 * @param int|null $parent_id Optional parent product ID. If not provided, will be looked up.
	 *
	 * @return void
	 */
	private function invalidate_variation_and_parent( int $variation_id, ?int $parent_id = null ): void {
		$this->invalidate( $variation_id );

		if ( is_null( $parent_id ) ) {
			if ( $this->is_using_cpt_data_store() ) {
				$parent_id = wp_get_post_parent_id( $variation_id );
			} else {
				$variation = wc_get_product( $variation_id );
				$parent_id = $variation ? $variation->get_parent_id() : 0;
			}
		}

		if ( ! $parent_id ) {
			return;
		}

		$this->invalidate( $parent_id );
	}

	/**
	 * Invalidate all products and variations that have a specific term assigned.
	 *
	 * Uses the indexed wp_term_relationships table for efficient lookups.
	 * The list of entities associated with the term is cached for performance;
	 * the TTL can be customized via the 'woocommerce_version_string_invalidator_taxonomy_lookup_ttl' filter.
	 *
	 * @param int $tt_id The term taxonomy ID.
	 *
	 * @return void
	 */
	private function invalidate_products_with_term( int $tt_id ): void {
		global $wpdb;

		$cache_key  = 'wc_cache_inv_term_' . $tt_id;
		$entity_ids = wp_cache_get( $cache_key, 'woocommerce' );

		if ( false === $entity_ids ) {
			$entity_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT tr.object_id
					FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
					WHERE tr.term_taxonomy_id = %d
					AND p.post_type IN ('product', 'product_variation')",
					$tt_id
				)
			);

			/**
			 * Filters the cache TTL for queries that find entities associated with a term or taxonomy.
			 *
			 * These queries are used during cache invalidation to determine which entities
			 * (e.g., products, variations) need their cache cleared when a term or attribute changes.
			 *
			 * @since 10.5.0
			 *
			 * @param int    $ttl         Cache TTL in seconds. Default 300 (5 minutes).
			 * @param string $entity_type The type of entity being invalidated ('product').
			 */
			$ttl = apply_filters( 'woocommerce_version_string_invalidator_taxonomy_lookup_ttl', self::DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL, 'product' );
			wp_cache_set( $cache_key, $entity_ids, 'woocommerce', $ttl );
		}

		foreach ( $entity_ids as $entity_id ) {
			$post_type = get_post_type( (int) $entity_id );
			if ( 'product_variation' === $post_type ) {
				$this->invalidate_variation_and_parent( (int) $entity_id );
			} else {
				$this->invalidate( (int) $entity_id );
			}
		}
	}

	/**
	 * Invalidate all products using a specific attribute taxonomy.
	 *
	 * The list of entities associated with the taxonomy is cached for performance;
	 * the TTL can be customized via the 'woocommerce_version_string_invalidator_taxonomy_lookup_ttl' filter.
	 *
	 * @param string $taxonomy The attribute taxonomy slug.
	 *
	 * @return void
	 */
	private function invalidate_products_with_attribute( string $taxonomy ): void {
		global $wpdb;

		$cache_key = 'wc_cache_inv_attr_' . $taxonomy;
		$cached    = wp_cache_get( $cache_key, 'woocommerce' );

		if ( false === $cached ) {
			$product_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_product_attributes'
					AND meta_value LIKE %s",
					'%' . $wpdb->esc_like( 's:' . strlen( $taxonomy ) . ':"' . $taxonomy . '"' ) . '%'
				)
			);

			$variation_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = %s",
					'attribute_' . $taxonomy
				)
			);

			$cached = array(
				'product_ids'   => $product_ids,
				'variation_ids' => $variation_ids,
			);

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented above.
			$ttl = apply_filters( 'woocommerce_version_string_invalidator_taxonomy_lookup_ttl', self::DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL, 'product' );
			wp_cache_set( $cache_key, $cached, 'woocommerce', $ttl );
		}

		foreach ( $cached['product_ids'] as $product_id ) {
			$this->invalidate( (int) $product_id );
		}

		foreach ( $cached['variation_ids'] as $variation_id ) {
			$this->invalidate_variation_and_parent( (int) $variation_id );
		}
	}

	/**
	 * Invalidate a product version string.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function invalidate( int $product_id ): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( "product_{$product_id}" );
	}
}
