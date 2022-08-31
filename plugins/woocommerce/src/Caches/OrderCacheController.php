<?php

namespace Automattic\WooCommerce\Caches;

/**
 * A class to control the usage of the orders cache.
 */
class OrderCacheController {

	public const ORDERS_CACHE_USAGE_ENABLED_OPTION = 'woocommerce_orders_cache_enabled';

	public const ORDERS_CACHE_USAGE_ENABLED_BACKUP_OPTION = 'woocommerce_orders_cache_enabled_backup';

	/**
	 * The orders cache to use.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrderCache $order_cache The order cache engine to use.
	 */
	final public function init( OrderCache $order_cache ) {
		$this->order_cache = $order_cache;
	}

	/**
	 * Set the value of the order cache usage setting.
	 *
	 * @param bool $enabled True if the order cache should be used, false if not.
	 */
	public function set_orders_cache_usage( bool $enabled ): void {
		$this->order_cache->flush();
		update_option( self::ORDERS_CACHE_USAGE_ENABLED_OPTION, $enabled ? 'yes' : 'no' );
	}

	/**
	 * Get the value of the order cache usage setting.
	 *
	 * @return bool True if order cache usage setting is currently enabled, false if not.
	 */
	public function orders_cache_usage_is_enabled(): bool {
		return 'yes' === get_option( self::ORDERS_CACHE_USAGE_ENABLED_OPTION );
	}

	/**
	 * Temporarily disable the order cache if it's enabled.
	 *
	 * If the cache is enabled when this method is executed,
	 * a flag is stored indicating that, and then the cache is disabled.
	 */
	public function temporarily_disable_orders_cache_usage(): void {
		$this->order_cache->flush();
		if ( ! $this->orders_cache_usage_is_enabled() ) {
			return;
		}

		update_option( self::ORDERS_CACHE_USAGE_ENABLED_BACKUP_OPTION, 'yes' );

		$this->set_orders_cache_usage( false );
	}

	/**
	 * Check if the order cache has been temporarily disabled.
	 *
	 * @return bool True if the order cache is currently temporarily disabled.
	 */
	public function orders_cache_usage_is_temporarly_disabled(): bool {
		return ! $this->orders_cache_usage_is_enabled() && false !== get_option( self::ORDERS_CACHE_USAGE_ENABLED_BACKUP_OPTION );
	}

	/**
	 * Restore the order cache usage that had been temporarily disabled.
	 *
	 * If the flag set by temporarily_disable_orders_cache_usage is found,
	 * the order cache usage is enabled and the flag is deleted.
	 */
	public function maybe_restore_orders_cache_usage(): void {
		$this->order_cache->flush();
		if ( false !== get_option( self::ORDERS_CACHE_USAGE_ENABLED_BACKUP_OPTION ) ) {
			$this->set_orders_cache_usage( 'yes' );
			delete_option( self::ORDERS_CACHE_USAGE_ENABLED_BACKUP_OPTION );
		}
	}
}
