<?php

namespace Automattic\WooCommerce\Caches;

/**
 * A class to control the usage of the orders cache.
 */
class OrderCacheController {

	public const ORDERS_CACHE_USAGE_ENABLED_OPTION = 'woocommerce_orders_cache_enabled';

	/**
	 * The orders cache to use.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * The backup value of the cache usage option, stored while the cache is temporarily disabled.
	 *
	 * @var null|string
	 */
	private $orders_cache_usage_backup = null;

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
	 * @param bool $enable True if the order cache should be used, false if not.
	 * @throws \Exception Attempt to enable the orders cache usage while it's temporarily disabled.
	 */
	public function set_orders_cache_usage( bool $enable ): void {
		if ( $enable && $this->orders_cache_usage_is_temporarly_disabled() ) {
			throw new \Exception( "OrderCacheController::set_orders_cache_usage: cache usage can't be enabled while it's temporarily disabled, run maybe_restore_orders_cache_usage first." );
		}

		$this->order_cache->flush();
		update_option( self::ORDERS_CACHE_USAGE_ENABLED_OPTION, $enable ? 'yes' : 'no' );
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
	 * A backup option is created with the current value of the cache usage enable setting,
	 * and then the setting is disabled. If the backup option already exists nothing is done.
	 * The original setting value can be restored with maybe_restore_orders_cache_usage.
	 */
	public function temporarily_disable_orders_cache_usage(): void {
		$this->order_cache->flush();
		if ( $this->orders_cache_usage_is_temporarly_disabled() ) {
			return;
		}

		$this->orders_cache_usage_backup = get_option( self::ORDERS_CACHE_USAGE_ENABLED_OPTION );

		$this->set_orders_cache_usage( false );
	}

	/**
	 * Check if the order cache has been temporarily disabled.
	 *
	 * @return bool True if the order cache is currently temporarily disabled.
	 */
	public function orders_cache_usage_is_temporarly_disabled(): bool {
		return null !== $this->orders_cache_usage_backup;
	}

	/**
	 * Get the value of the order cache usage enable backup option.
	 *
	 * @return bool|null True if the option has value 'yes', false if it has value 'no', null if the option doesn't exist.
	 */
	public function orders_cache_usage_backup_value(): ?bool {
		return $this->orders_cache_usage_backup;
	}

	/**
	 * Restore the order cache usage that had been temporarily disabled.
	 *
	 * The cache usage enable setting is set to the value of the backup option
	 * that had been created by temporarily_disable_orders_cache_usage,
	 * then the backup option is deleted.
	 * If the backup option doesn't exist nothing is done.
	 */
	public function maybe_restore_orders_cache_usage(): void {
		$this->order_cache->flush();
		if ( null !== $this->orders_cache_usage_backup ) {
			update_option( self::ORDERS_CACHE_USAGE_ENABLED_OPTION, $this->orders_cache_usage_backup );
			$this->orders_cache_usage_backup = null;
		}
	}
}
