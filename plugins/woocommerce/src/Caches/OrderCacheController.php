<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * A class to control the usage of the orders cache.
 */
class OrderCacheController {

	use AccessiblePrivateMethods;

	const FEATURE_NAME = 'orders_cache';

	/**
	 * The orders cache to use.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * The orders cache to use.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * The backup value of the cache usage enable status, stored while the cache is temporarily disabled.
	 *
	 * @var null|bool
	 */
	private $orders_cache_usage_backup = null;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		self::add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'handle_feature_enable_changed' ), 10, 2 );
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrderCache         $order_cache The order cache engine to use.
	 * @param FeaturesController $features_controller The features controller to use.
	 */
	final public function init( OrderCache $order_cache, FeaturesController $features_controller ) {
		$this->order_cache         = $order_cache;
		$this->features_controller = $features_controller;
	}

	/**
	 * Handler for the feature enable changed action, when the orders cache is enabled or disabled it flushes it.
	 *
	 * @param string $feature_id The id of the feature whose enable status changed.
	 * @param bool   $enabled Whether the feature has been enabled or disabled.
	 * @return void
	 */
	private function handle_feature_enable_changed( string $feature_id, bool $enabled ): void {
		if ( self::FEATURE_NAME === $feature_id ) {
			$this->order_cache->flush();
		}
	}

	/**
	 * Set the value of the order cache usage setting.
	 *
	 * @param bool $enable True if the order cache should be used, false if not.
	 * @throws \Exception Attempt to enable the orders cache usage while it's temporarily disabled.
	 */
	public function set_orders_cache_usage( bool $enable ): void {
		$this->features_controller->change_feature_enable( $enable );
		$this->orders_cache_usage_backup = null;
	}

	/**
	 * Get the value of the order cache usage setting.
	 *
	 * @return bool True if order cache usage setting is currently enabled, false if not.
	 */
	public function orders_cache_usage_is_enabled(): bool {
		return ! $this->orders_cache_usage_is_temporarly_disabled() && $this->features_controller->feature_is_enabled( self::FEATURE_NAME );
	}

	/**
	 * Temporarily disable the order cache if it's enabled.
	 *
	 * This is a purely in-memory operation: a variable is created with the value
	 * of the current enable status for the feature, and this variable
	 * is checked by orders_cache_usage_is_enabled. In the next request the
	 * feature will be again enabled or not depending on how the feature is set.
	 */
	public function temporarily_disable_orders_cache_usage(): void {
		if ( $this->orders_cache_usage_is_temporarly_disabled() ) {
			return;
		}

		$this->orders_cache_usage_backup = $this->orders_cache_usage_is_enabled();
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
	 * Restore the order cache usage that had been temporarily disabled.
	 */
	public function maybe_restore_orders_cache_usage(): void {
		$this->orders_cache_usage_backup = null;
	}
}
