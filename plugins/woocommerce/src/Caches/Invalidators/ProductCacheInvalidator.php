<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches\Invalidators;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;

/**
 * Product cache invalidation handler.
 *
 * This class provides an 'invalidate' method that will invalidate
 * the internal WooCommerce cache for a given product, and will also
 * trigger a 'woocommerce_product_cache_invalidated' action that can be
 * listened to in order to invalidate any custom product caches.
 * The 'invalidate' method will be triggered by WooCommerce in response
 * to various product lifecycle events (like create, update, delete,
 * (un)trash) but can also be called directly by third-party code if needed.
 *
 * Example of hook usage by consumers:
 * ```php
 * add_action( 'woocommerce_product_cache_invalidated', function( $product_id, $operation, $context ) {
 *     // Clear your custom cache
 *     wp_cache_delete( 'my_cache_' . $product_id );
 * }, 10, 3 );
 * ```
 */
class ProductCacheInvalidator {

	/**
	 * Operation constants.
	 */
	const OPERATION_CREATE  = 'create';
	const OPERATION_UPDATE  = 'update';
	const OPERATION_DELETE  = 'delete';
	const OPERATION_TRASH   = 'trash';
	const OPERATION_UNTRASH = 'untrash';

	/**
	 * Initialize the invalidator and register hooks.
	 *
	 * @internal
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	final public function init(): void {
		$this->register_hooks();
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
		add_action( 'save_post_product', array( $this, 'handle_save_post_product' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'handle_delete_post' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'handle_trashed_post' ), 10, 2 );
		add_action( 'untrashed_post', array( $this, 'handle_untrashed_post' ), 10, 2 );

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
			add_action( 'woocommerce_attribute_updated', array( $this, 'handle_woocommerce_attribute_updated' ), 10, 3 );
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
	 * @internal
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 * @param bool     $update Whether this is an update or new post.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_save_post_product( int $post_id, $post, bool $update ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$operation = $update ? self::OPERATION_UPDATE : self::OPERATION_CREATE;

		$this->invalidate(
			$post_id,
			$operation,
			array(
				'hook'      => 'save_post_product',
				'post'      => $post,
				'operation' => $operation,
			)
		);
	}

	/**
	 * Handle the delete_post hook.
	 *
	 * @internal
	 *
	 * @param int           $post_id The post ID.
	 * @param \WP_Post|null $post The post object, or null if not provided.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_delete_post( int $post_id, $post = null ): void {
		if ( is_null( $post ) ) {
			$post = get_post( $post_id );
		}

		if ( ! $post ) {
			return;
		}

		if ( 'product_variation' === $post->post_type ) {
			$this->invalidate_variation_and_parent(
				$post_id,
				self::OPERATION_DELETE,
				$post->post_parent,
				$this->get_context_for_post_hook( 'delete_post', $post )
			);
		} elseif ( 'product' === $post->post_type ) {
			$this->invalidate( $post_id, self::OPERATION_DELETE, $this->get_context_for_post_hook( 'delete_post', $post ) );
		}
	}

	/**
	 * Get context array for post-related hooks.
	 *
	 * @param string        $hook The hook name.
	 * @param \WP_Post|null $post The post object, or null.
	 *
	 * @return array The context array.
	 */
	private function get_context_for_post_hook( string $hook, ?\WP_Post $post ): array {
		return array(
			'hook' => $hook,
			'post' => $post,
		);
	}

	/**
	 * Handle the trashed_post hook.
	 *
	 * @internal
	 *
	 * @param int    $post_id The post ID.
	 * @param string $previous_status The previous post status.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_trashed_post( int $post_id, string $previous_status ): void {
		$this->handle_trashed_or_untrashed_post( $post_id, $previous_status, 'trashed_post', self::OPERATION_TRASH );
	}

	/**
	 * Handle the untrashed_post hook.
	 *
	 * @internal
	 *
	 * @param int    $post_id The post ID.
	 * @param string $previous_status The previous post status.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_untrashed_post( int $post_id, string $previous_status ): void {
		$this->handle_trashed_or_untrashed_post( $post_id, $previous_status, 'untrashed_post', self::OPERATION_UNTRASH );
	}

	/**
	 * Handle the trashed_post and untrashed_post hooks.
	 *
	 * @internal
	 *
	 * @param int    $post_id The post ID.
	 * @param string $previous_status The previous post status.
	 * @param string $hook_name The name of the hook being handled.
	 * @param string $operation The operation being performed.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	private function handle_trashed_or_untrashed_post( int $post_id, string $previous_status, string $hook_name, string $operation ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		if ( 'product_variation' === $post->post_type ) {
			$this->invalidate_variation_and_parent(
				$post_id,
				$operation,
				$post->post_parent,
				$this->get_context_for_post_hook( $hook_name, $post )
			);
		} elseif ( 'product' === $post->post_type ) {
			$this->invalidate( $post_id, $operation, $this->get_context_for_post_hook( $hook_name, $post ) );
		}
	}

	/**
	 * Handle the woocommerce_new_product_variation hook.
	 *
	 * @internal
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_new_product_variation( int $variation_id, $variation ): void {
		$this->invalidate_variation_and_parent(
			$variation_id,
			self::OPERATION_CREATE,
			$variation->get_parent_id(),
			array(
				'hook'    => 'woocommerce_new_product_variation',
				'product' => $variation,
			)
		);
	}

	/**
	 * Handle the woocommerce_update_product_variation hook.
	 *
	 * @internal
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_update_product_variation( int $variation_id, $variation ): void {
		$this->invalidate_variation_and_parent(
			$variation_id,
			self::OPERATION_UPDATE,
			$variation->get_parent_id(),
			array(
				'hook'    => 'woocommerce_update_product_variation',
				'product' => $variation,
			)
		);
	}

	/**
	 * Handle the woocommerce_new_product hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_new_product( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_CREATE,
			array(
				'hook' => 'woocommerce_new_product',
			)
		);
	}

	/**
	 * Handle the woocommerce_update_product hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_update_product( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_UPDATE,
			array(
				'hook' => 'woocommerce_update_product',
			)
		);
	}

	/**
	 * Handle the woocommerce_before_delete_product hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_before_delete_product( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_DELETE,
			array(
				'hook' => 'woocommerce_before_delete_product',
			)
		);
	}

	/**
	 * Handle the woocommerce_trash_product hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_trash_product( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_TRASH,
			array(
				'hook' => 'woocommerce_trash_product',
			)
		);
	}

	/**
	 * Handle the woocommerce_before_delete_product_variation hook.
	 *
	 * @internal
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_before_delete_product_variation( int $variation_id ): void {
		$this->invalidate_variation_and_parent(
			$variation_id,
			self::OPERATION_DELETE,
			null,
			array( 'hook' => 'woocommerce_before_delete_product_variation' )
		);
	}

	/**
	 * Handle the woocommerce_trash_product_variation hook.
	 *
	 * @internal
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_trash_product_variation( int $variation_id ): void {
		$this->invalidate_variation_and_parent(
			$variation_id,
			self::OPERATION_TRASH,
			null,
			array( 'hook' => 'woocommerce_trash_product_variation' )
		);
	}

	/**
	 * Handle the woocommerce_updated_product_stock hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_updated_product_stock( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_UPDATE,
			array(
				'hook' => 'woocommerce_updated_product_stock',
			)
		);
	}

	/**
	 * Handle the woocommerce_updated_product_price hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_updated_product_price( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_UPDATE,
			array(
				'hook' => 'woocommerce_updated_product_price',
			)
		);
	}

	/**
	 * Handle the woocommerce_updated_product_sales hook.
	 *
	 * @internal
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_updated_product_sales( int $product_id ): void {
		$this->invalidate(
			$product_id,
			self::OPERATION_UPDATE,
			array(
				'hook' => 'woocommerce_updated_product_sales',
			)
		);
	}

	/**
	 * Handle the woocommerce_attribute_updated hook.
	 *
	 * @internal
	 *
	 * @param int    $id The attribute ID.
	 * @param array  $data The attribute data.
	 * @param string $old_slug The old attribute slug.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_attribute_updated( int $id, array $data, string $old_slug ): void {
		$taxonomy = wc_attribute_taxonomy_name( $data['attribute_name'] );
		$this->invalidate_products_with_attribute(
			$taxonomy,
			array(
				'hook'         => 'woocommerce_attribute_updated',
				'attribute_id' => $id,
				'taxonomy'     => $taxonomy,
				'old_slug'     => $old_slug,
				'new_slug'     => $data['attribute_name'],
			)
		);
	}

	/**
	 * Handle the woocommerce_attribute_deleted hook.
	 *
	 * @internal
	 *
	 * @param int    $id The attribute ID.
	 * @param string $name The attribute name.
	 * @param string $taxonomy The attribute taxonomy.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_attribute_deleted( int $id, string $name, string $taxonomy ): void {
		$this->invalidate_products_with_attribute(
			$taxonomy,
			array(
				'hook'         => 'woocommerce_attribute_deleted',
				'attribute_id' => $id,
				'taxonomy'     => $taxonomy,
			)
		);
	}

	/**
	 * Handle the woocommerce_updated_product_attribute_summary hook.
	 *
	 * @internal
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_woocommerce_updated_product_attribute_summary( int $variation_id ): void {
		$this->invalidate_variation_and_parent(
			$variation_id,
			self::OPERATION_UPDATE,
			null,
			array(
				'hook' => 'woocommerce_updated_product_attribute_summary',
			)
		);
	}

	/**
	 * Handle the edited_term hook.
	 *
	 * @internal
	 *
	 * @param int    $term_id The term ID.
	 * @param int    $tt_id The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function handle_edited_term( int $term_id, int $tt_id, string $taxonomy ): void {
		// Only handle product attribute taxonomies.
		if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		$this->invalidate_products_with_attribute(
			$taxonomy,
			array(
				'hook'     => 'edited_term',
				'term_id'  => $term_id,
				'taxonomy' => $taxonomy,
			)
		);
	}

	/**
	 * Invalidate a variation and its parent product.
	 *
	 * @param int      $variation_id The variation ID.
	 * @param string   $operation The operation for the variation.
	 * @param int|null $parent_id Optional parent product ID. If not provided, will be looked up.
	 * @param array    $context Context for the variation invalidation, MUST contain a 'hook' key.
	 *
	 * @return void
	 */
	private function invalidate_variation_and_parent( int $variation_id, string $operation, ?int $parent_id = null, array $context = array() ): void {
		$this->invalidate( $variation_id, $operation, $context );

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

		$this->invalidate(
			$parent_id,
			self::OPERATION_UPDATE,
			array(
				'hook'         => $context['hook'],
				'variation_id' => $variation_id,
			)
		);
	}

	/**
	 * Invalidate all products using a specific attribute taxonomy.
	 *
	 * @param string $taxonomy The attribute taxonomy slug.
	 * @param array  $context Context for the invalidation.
	 *
	 * @return void
	 */
	private function invalidate_products_with_attribute( string $taxonomy, array $context = array() ): void {
		global $wpdb;

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

		foreach ( $product_ids as $product_id ) {
			$this->invalidate( (int) $product_id, self::OPERATION_UPDATE, $context );
		}

		foreach ( $variation_ids as $variation_id ) {
			$this->invalidate_variation_and_parent( (int) $variation_id, self::OPERATION_UPDATE, null, $context );
		}
	}

	/**
	 * Invalidate product cache and notify listeners via WordPress action.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $operation The operation that triggered invalidation.
	 * @param array  $context Additional context about the invalidation. See hook documentation
	 *                        for possible context keys.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function invalidate( int $product_id, string $operation, array $context = array() ): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( "product_{$product_id}" );

		/**
		 * Fires when a product cache is invalidated.
		 *
		 * This action may fire multiple times for the same product during a single request
		 * if the product is modified multiple times. Consumers should implement their own
		 * deduplication logic if needed, using the context information provided.
		 *
		 * The list of possible values/keys below reflects what WooCommerce core currently provides,
		 * this can change in future WooCommerce versions.
		 * Third-party code triggering this action should follow the same conventions
		 * where applicable, but may also provide additional context keys as needed.
		 * Consumers should always check for key existence before using.
		 *
		 * @since 10.5.0
		 *
		 * @param int $product_id The product ID.
		 * @param string     $operation The operation that triggered the invalidation.
		 *                              Possible values:
		 *                              - 'create': Product created
		 *                              - 'update': Product updated (includes stock, price, sales, and attribute changes)
		 *                              - 'delete': Product deleted
		 *                              - 'trash': Product moved to trash
		 *                              - 'untrash': Product restored from trash
		 * @param array      $context Additional context about the invalidation. Possible keys:
		 *                           - 'hook' (string) - The WordPress/WooCommerce hook that triggered invalidation.
		 *                                              Present for hook-triggered invalidations.
		 *                           - 'function' (string) - The function or class::method that triggered invalidation
		 *                                              (e.g., 'WC_Product_Variable_Data_Store_CPT::sync_variation_names').
		 *                                              Present for direct calls from data stores instead of 'hook'.
		 *                           - 'post' (WP_Post) - The post object. Present for WordPress post hooks
		 *                                              (save_post_product, delete_post, trashed_post, untrashed_post).
		 *                           - 'product' (WC_Product) - The product object. Present for WooCommerce variation
		 *                                              hooks (woocommerce_new_product_variation,
		 *                                              woocommerce_update_product_variation) and some data store calls.
		 *                           - 'parent_id' (int) - Parent product ID. Present when a variation is invalidated
		 *                                              via direct data store calls.
		 *                           - 'variation_id' (int) - Variation ID. Present when the hook is triggered for a
		 *                                              parent product as a result of a variation change.
		 *                           - 'taxonomy' (string) - Attribute taxonomy (e.g., 'pa_color'). Present for
		 *                                              attribute-related invalidations.
		 *                           - 'term_id' (int) - Term ID. Present for attribute term updates (edited_term hook).
		 *                           - 'attribute_id' (int) - Attribute ID. Present for attribute update/delete operations.
		 *                           - 'old_slug' (string) - Previous attribute slug. Present for attribute renames.
		 *                           - 'new_slug' (string) - New attribute slug. Present for attribute renames.
		 */
		do_action( 'woocommerce_product_cache_invalidated', $product_id, $operation, $context );
	}
}
