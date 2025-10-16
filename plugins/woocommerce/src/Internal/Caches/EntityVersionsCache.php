<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Entity versions vache class.
 *
 * Provides a generic mechanism for caching versions of mutable entities,
 * with built-in support for detecting and handling changes in products.
 */
class EntityVersionsCache {

	/**
	 * Is the entity versions cache enabled?
	 *
	 * @var bool|null
	 */
	private ?bool $is_enabled = null;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		add_action( 'woocommerce_new_product', array( $this, 'handle_product_changed' ), 10, 1 );
		add_action( 'woocommerce_new_product_variation', array( $this, 'handle_product_changed' ), 10, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'handle_product_changed' ), 10, 1 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'handle_product_changed' ), 10, 1 );
		add_action( 'woocommerce_delete_product', array( $this, 'handle_product_deleted' ), 10, 1 );
		add_action( 'woocommerce_trash_product', array( $this, 'handle_product_deleted' ), 10, 1 );
		add_action( 'woocommerce_untrash_product', array( $this, 'handle_product_deleted' ), 10, 1 );
	}

	/**
	 * Tells whether the entity versions cache is enabled or not.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		if ( ! is_null( $this->is_enabled ) ) {
			return $this->is_enabled;
		}

		/**
		 * Filter whether to enable the entity versions cache.
		 * REST API controllers will use the built-in output caching mechanism only if
		 * the entity versions cache is enabled.
		 *
		 * By default, the entity versions cache will be enabled only if an external
		 * object cache is configured in WordPress. Enabling it otherwise is not recommended
		 * (outside of development and testing scenarios) since cached entries would be stored
		 * directly in the options table, causing high stress in the database.
		 *
		 * @since 10.4.0
		 *
		 * @param bool   $use_output_cache Whether to use output cache. Default is the result of wp_using_ext_object_cache().
		 * @return bool True to enable output cache, false to disable.
		 */
		$this->is_enabled = apply_filters(
			'woocommerce_enable_entity_versions_cache',
			wp_using_ext_object_cache() ?? false
		);

		return $this->is_enabled;
	}

	/**
	 * Get the current version of an entity.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id Entity ID.
	 * @return string Entity version.
	 */
	public function get_entity_version( string $entity_type, int $entity_id ): string {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";
		$version        = get_transient( $transient_name );
		if ( false === $version ) {
			$version = $this->modify_entity_version( $entity_type, $entity_id );
		} else {
			// Refresh the transient lifetime.
			$this->store_entity_version( $entity_type, $entity_id, $version );
		}
		return $version;
	}

	/**
	 * Generate and store a new version for an entity.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @return string The new entity version.
	 */
	public function modify_entity_version( string $entity_type, int $entity_id ): string {
		$version = wp_generate_uuid4();
		$this->store_entity_version( $entity_type, $entity_id, $version );
		return $version;
	}

	/**
	 * Store the entity version in a transient with a filterable TTL.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param string $version     The version to store.
	 * @return bool True on success, false on failure.
	 */
	protected function store_entity_version( string $entity_type, int $entity_id, string $version ): bool {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";

		/**
		 * Filter the TTL for entity version cache.
		 *
		 * @param int    $ttl         Time to live in seconds. Default 1 hour.
		 * @param string $entity_type The type of the entity.
		 * @param int    $entity_id   The ID of the entity.
		 *
		 * @since 10.4.0
		 */
		$ttl = apply_filters( 'woocommerce_cached_entity_version_ttl', HOUR_IN_SECONDS, $entity_type, $entity_id );
		return set_transient( $transient_name, $version, $ttl );
	}

	/**
	 * Forget the version of an entity by deleting its transient.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @return bool True on success, false on failure.
	 */
	public function forget_entity_version( string $entity_type, int $entity_id ): bool {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";
		return delete_transient( $transient_name );
	}

	/**
	 * Handle product changes by modifying the entity version.
	 *
	 * @param int $product_id Product ID.
	 */
	public function handle_product_changed( int $product_id ): void {
		$this->handle_product_changed_or_deleted( $product_id, false );
	}

	/**
	 * Handle product deletions by forgetting the entity version.
	 *
	 * @param int $product_id Product ID.
	 */
	public function handle_product_deleted( int $product_id ): void {
		$this->handle_product_changed_or_deleted( $product_id, true );
	}

	/**
	 * Handle product changes or deletions by modifying or forgetting the entity version.
	 *
	 * @param int  $product_id  Product ID.
	 * @param bool $is_deletion Whether the product is being deleted.
	 */
	private function handle_product_changed_or_deleted( int $product_id, bool $is_deletion ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->modify_or_forget_product_version( $product_id, $is_deletion );

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		// If the product is variable we need to modify/delete the cache versions for the variations too.
		// On the other hand, if it's a variation, we need to modify/delete the parent's cached version.

		if ( $product->is_type( 'variable' ) ) {
			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$this->modify_or_forget_product_version( $variation_id, $is_deletion );
			}
		} elseif ( $product->is_type( 'variation' ) ) {
			$parent_id = $product->get_parent_id();
			if ( $parent_id ) {
				$this->modify_or_forget_product_version( $parent_id, $is_deletion );
			}
		}
	}

	/**
	 * Modify or forget the version of a product based on the action.
	 *
	 * @param int  $product_id Product ID.
	 * @param bool $delete     Whether to forget the version (true) or modify it (false).
	 */
	private function modify_or_forget_product_version( int $product_id, bool $delete ): void {
		if ( $delete ) {
			$this->forget_entity_version( 'product', $product_id );
		} else {
			$this->modify_entity_version( 'product', $product_id );
		}
	}
}
