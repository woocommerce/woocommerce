<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Tracks stale object instances. The decentralized hooks architecture allows multiple modification routes in both
 * WooCommerce core and its extensions. This feature enables the identification of stale objects under those constraints.
 */
trait StaleObjectAttribution {
	/**
	 * Object instantiation timestamp.
	 *
	 * @var float|null
	 */
	private ?float $_woocommerce_instance_created_at = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Registry of persistence (update/delete) operations on the ID-level.
	 *
	 * @var array<string,float>
	 */
	private static array $_woocommerce_entity_persisted_at = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Tracks object instantiation timestamp.
	 *
	 * @return void
	 */
	private function _woocommerce_object_instantiated(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$this->_woocommerce_instance_created_at = microtime( true );
	}

	/**
	 * Tracks object persistence (update/delete) timestamp.
	 *
	 * Do not use this method for customization. Although it is public due to architectural constraints, improper use
	 * may result in stale instance usage, which is critical for functions such as product inventory management.
	 *
	 * @param int $entity_id Object ID.
	 * @return void
	 */
	public function _woocommerce_entity_persisted( int $entity_id ): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$object_type = $this instanceof \WC_Product ? 'product' : $this->object_type;
		self::$_woocommerce_entity_persisted_at[ $object_type . ':' . $entity_id ] = microtime( true );
	}

	/**
	 * Enables verification of whether the object instance is stale. If it is stale, the object must be re-created,
	 * as the current architecture does not support refreshing object attributes without replacing the object.
	 *
	 * @return bool
	 */
	public function _woocommerce_object_is_stale(): bool { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		// Unknown instantiation timestamp means freshness cannot be determined — treat as stale to fail safe.
		if ( null !== $this->_woocommerce_instance_created_at ) {
			$instance_timestamp  = (float) ( $this->_woocommerce_instance_created_at );
			$object_type         = $this instanceof \WC_Product ? 'product' : $this->object_type;
			$entity_persisted_at = (float) ( self::$_woocommerce_entity_persisted_at[ $object_type . ':' . $this->get_id() ] ?? null );

			return $instance_timestamp <= $entity_persisted_at;
		}

		return true;
	}

	/**
	 * Enables verification of whether the object instance is modified.
	 *
	 * @return bool
	 */
	public function _woocommerce_object_is_dirty(): bool { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$meta_data = $this->meta_data ?? array();
		$is_dirty  = ! empty( $this->changes );
		$is_dirty  = $is_dirty || ! empty( array_filter( $meta_data, static fn( $meta ) => ! $meta->id || ! empty( $meta->get_changes() ) ) );

		return $is_dirty;
	}
}
