<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches\Invalidators;

/**
 * Interface for cache invalidators.
 *
 * Implementations of this interface are responsible for invalidating caches
 * for specific entity types (products, orders, etc.).
 */
interface CacheInvalidatorInterface {

	/**
	 * Invalidate cache for a specific entity.
	 *
	 * @param int|string $entity_id The ID of the entity to invalidate.
	 * @param string     $operation The operation that triggered the invalidation (e.g., 'create', 'update', 'delete').
	 * @param mixed      $context Optional additional context about the invalidation.
	 *
	 * @return void
	 */
	public function invalidate( $entity_id, string $operation, $context = null ): void;
}
