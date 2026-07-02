<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductTransientsDeferrer;

/**
 * Tests for product transient deletion deferral.
 */
class ProductTransientsDeferrerTest extends \WC_Unit_Test_Case {

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

	/**
	 * @testdox Product transient deferral shutdown flush runs before deferred parent product sync.
	 * @see https://github.com/woocommerce/woocommerce/issues/65686
	 */
	public function test_shutdown_flush_runs_before_deferred_parent_product_sync(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();

		$parent = new \WC_Product_Variable();
		$parent->set_name( 'Issue 65686 variable product' );
		$parent->set_status( 'publish' );
		$parent->save();

		$existing_variation = $this->create_variation( $parent->get_id(), '11' );
		\WC_Product_Variable::sync( $parent->get_id() );

		$parent = wc_get_product( $parent->get_id() );
		$this->assertSame(
			array( $existing_variation->get_id() ),
			$parent->get_visible_children(),
			'Precondition: the parent children transient should contain only the existing variation.'
		);

		$deferrer = wc_get_container()->get( ProductTransientsDeferrer::class );
		$deferrer->start_deferring();
		$this->create_variation( $parent->get_id(), '66' );

		$product_sync_shutdown_priority = has_action( 'shutdown', array( \WC_Post_Data::class, 'do_deferred_product_sync' ) );
		$deferrer_shutdown_priority     = has_action( 'shutdown', array( $deferrer, 'handle_shutdown' ) );

		$this->assertNotFalse( $product_sync_shutdown_priority, 'Deferred product sync should be registered on shutdown.' );
		$this->assertNotFalse( $deferrer_shutdown_priority, 'Product transient deferral should be registered on shutdown.' );

		try {
			$shutdown_callbacks = array(
				array(
					'priority' => $product_sync_shutdown_priority,
					'order'    => 0,
					'callback' => array( \WC_Post_Data::class, 'do_deferred_product_sync' ),
				),
				array(
					'priority' => $deferrer_shutdown_priority,
					'order'    => 1,
					'callback' => array( $deferrer, 'handle_shutdown' ),
				),
			);

			usort(
				$shutdown_callbacks,
				static function ( array $a, array $b ): int {
					return $a['priority'] === $b['priority'] ? $a['order'] <=> $b['order'] : $a['priority'] <=> $b['priority'];
				}
			);

			foreach ( $shutdown_callbacks as $shutdown_callback ) {
				call_user_func( $shutdown_callback['callback'] );
			}
		} finally {
			$deferrer->stop_deferring();
			$wc_deferred_product_sync = array();
		}

		$parent_price_rows = get_post_meta( $parent->get_id(), '_price', false );
		$parent_price_rows = array_map( 'strval', $parent_price_rows );
		sort( $parent_price_rows, SORT_NUMERIC );

		$this->assertSame(
			array( '11', '66' ),
			$parent_price_rows,
			'Deferred parent sync should include variations created while product transient deletion was deferred.'
		);
	}

	/**
	 * Create a published variation with a fixed price.
	 *
	 * @param int    $parent_id Parent product ID.
	 * @param string $price Variation price.
	 * @return \WC_Product_Variation
	 */
	private function create_variation( int $parent_id, string $price ): \WC_Product_Variation {
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent_id );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( $price );
		$variation->set_price( $price );
		$variation->save();

		return $variation;
	}
}
