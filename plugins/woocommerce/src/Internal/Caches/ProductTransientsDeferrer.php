<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Internal\Utilities\ProductUtil;

/**
 * Coalesces repeated product transient deletions during bulk write operations.
 *
 * @internal
 */
class ProductTransientsDeferrer {

	/**
	 * Priority for the shutdown safety-net hook.
	 *
	 * This must run before WC_Post_Data::do_deferred_product_sync() at priority 10
	 * so stale product transients are cleared before parent product sync reads them.
	 */
	private const SHUTDOWN_HOOK_PRIORITY = 0;

	/**
	 * The product utility instance.
	 *
	 * @var ProductUtil
	 */
	private ProductUtil $product_util;

	/**
	 * Nesting level of active deferrals.
	 *
	 * @var int
	 */
	private int $deferral_level = 0;

	/**
	 * Product IDs collected while deferral is active, as a set of id => true.
	 *
	 * @var array
	 */
	private array $deferred_product_ids = array();

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param ProductUtil $product_util The product utility instance.
	 * @return void
	 */
	final public function init( ProductUtil $product_util ): void {
		$this->product_util = $product_util;
	}

	/**
	 * Start deferring product transient deletions. Calls can be nested.
	 *
	 * @return void
	 */
	public function start_deferring(): void {
		++$this->deferral_level;
		if ( 1 === $this->deferral_level ) {
			add_action( 'shutdown', array( $this, 'handle_shutdown' ), self::SHUTDOWN_HOOK_PRIORITY );
		}
	}

	/**
	 * Stop deferring product transient deletions.
	 *
	 * When the outermost deferral ends, all collected product IDs are flushed.
	 *
	 * @return void
	 */
	public function stop_deferring(): void {
		if ( 0 === $this->deferral_level ) {
			return;
		}

		--$this->deferral_level;
		if ( 0 === $this->deferral_level ) {
			remove_action( 'shutdown', array( $this, 'handle_shutdown' ), self::SHUTDOWN_HOOK_PRIORITY );
			$this->flush();
		}
	}

	/**
	 * Record a product ID for deferred transient deletion, if deferral is active.
	 *
	 * @param int $product_id Product ID whose transients were requested to be deleted.
	 * @return bool True if the deletion was deferred, false if deferral is not active.
	 */
	public function maybe_defer_deletion( int $product_id ): bool {
		if ( 0 === $this->deferral_level ) {
			return false;
		}

		$this->deferred_product_ids[ $product_id ] = true;
		return true;
	}

	/**
	 * Handle the shutdown hook.
	 *
	 * Flushes pending deletions if deferral was not explicitly stopped.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_shutdown(): void {
		$this->deferral_level = 0;
		$this->flush();
	}

	/**
	 * Delete transients for all collected product IDs and reset the collection.
	 *
	 * @return void
	 */
	private function flush(): void {
		if ( empty( $this->deferred_product_ids ) ) {
			return;
		}

		$product_ids                = array_keys( $this->deferred_product_ids );
		$this->deferred_product_ids = array();
		$this->product_util->delete_product_transients_for_products( $product_ids );
	}
}
