<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductTransientsDeferrer;

/**
 * Tests for product transient deletion deferral.
 */
class ProductTransientsDeferrerTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Shutdown fallback flushes product transients before deferred parent product sync.
	 */
	public function test_shutdown_fallback_runs_before_deferred_product_sync() {
		$deferrer = wc_get_container()->get( ProductTransientsDeferrer::class );

		try {
			$deferrer->start_deferring();

			$sync_priority = has_action( 'shutdown', array( 'WC_Post_Data', 'do_deferred_product_sync' ) );
			$priority      = has_action( 'shutdown', array( $deferrer, 'handle_shutdown' ) );

			$this->assertNotFalse( $priority );
			$this->assertNotFalse( $sync_priority );
			$this->assertLessThan( $sync_priority, $priority );

			$deferrer->stop_deferring();
			$this->assertFalse( has_action( 'shutdown', array( $deferrer, 'handle_shutdown' ) ) );
		} finally {
			$deferrer->stop_deferring();
		}
	}

	/**
	 * @testdox Product transient deferral coalesces repeated deletions until the outermost deferral ends.
	 */
	public function test_deferral_coalesces_repeated_deletions_until_outermost_stop() {
		$deleted_ids = array();
		$track_hook  = static function ( $product_id ) use ( &$deleted_ids ) {
			$deleted_ids[] = (int) $product_id;
		};

		$deferrer = wc_get_container()->get( ProductTransientsDeferrer::class );
		add_action( 'woocommerce_delete_product_transients', $track_hook );

		try {
			$deferrer->start_deferring();
			$deferrer->start_deferring();

			wc_delete_product_transients( 123 );
			wc_delete_product_transients( 123 );
			wc_delete_product_transients( 456 );
			$this->assertSame( array(), $deleted_ids );

			$deferrer->stop_deferring();
			$this->assertSame( array(), $deleted_ids );

			$deferrer->stop_deferring();
		} finally {
			remove_action( 'woocommerce_delete_product_transients', $track_hook );
			$deferrer->stop_deferring();
		}

		$this->assertSame( array( 123, 456 ), $deleted_ids );
	}
}
