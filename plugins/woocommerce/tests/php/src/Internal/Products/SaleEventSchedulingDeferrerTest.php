<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Products;

use Automattic\WooCommerce\Internal\Products\SaleEventSchedulingDeferrer;
use WC_Helper_Product;
use WC_Product;

/**
 * Tests for sale event scheduling deferral.
 */
class SaleEventSchedulingDeferrerTest extends \WC_Unit_Test_Case {

	/**
	 * Get the deferrer, with anything queued by earlier work already flushed.
	 *
	 * @return SaleEventSchedulingDeferrer
	 */
	private function get_flushed_deferrer(): SaleEventSchedulingDeferrer {
		$deferrer = wc_get_container()->get( SaleEventSchedulingDeferrer::class );
		$deferrer->flush();

		return $deferrer;
	}

	/**
	 * Create a product that has a future sale window, and clear the events queued while creating it.
	 *
	 * @param SaleEventSchedulingDeferrer $deferrer The deferrer to flush once the product is saved.
	 * @return WC_Product
	 */
	private function create_product_on_future_sale( SaleEventSchedulingDeferrer $deferrer ): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		$product->save();

		$deferrer->flush();

		// Start from a clean slate so the assertions only see what the test itself triggers.
		as_unschedule_all_actions( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' );

		return $product;
	}

	/**
	 * Whether a start-of-sale action is currently scheduled for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function has_scheduled_sale_start( int $product_id ): bool {
		return false !== as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product_id ), 'woocommerce-sales' );
	}

	/**
	 * @testdox Queued products are not rescheduled until the queue is flushed.
	 */
	public function test_queued_products_are_not_rescheduled_until_flush() {
		$deferrer = $this->get_flushed_deferrer();
		$product  = $this->create_product_on_future_sale( $deferrer );

		$deferrer->queue_product( $product->get_id() );

		$this->assertFalse(
			$this->has_scheduled_sale_start( $product->get_id() ),
			'Queueing a product should not reschedule it straight away.'
		);

		$deferrer->flush();

		$this->assertTrue(
			$this->has_scheduled_sale_start( $product->get_id() ),
			'Flushing the queue should reschedule the product.'
		);
	}

	/**
	 * @testdox The queue flushes itself once enough distinct products accumulate, without waiting for shutdown.
	 */
	public function test_queue_flushes_itself_once_the_pending_limit_is_reached() {
		$deferrer = $this->get_flushed_deferrer();
		$product  = $this->create_product_on_future_sale( $deferrer );

		$deferrer->queue_product( $product->get_id() );

		/*
		 * Fill the queue with IDs that resolve to no product. Rescheduling them is a no-op, and they
		 * push the real product up to the pending limit so the automatic flush can be observed. The
		 * count lands exactly on the limit, so nothing is left queued for a later test to inherit.
		 */
		$absent_id_base = $product->get_id() + 100000;
		for ( $offset = 0; $offset < 99; $offset++ ) {
			$deferrer->queue_product( $absent_id_base + $offset );
		}

		$this->assertTrue(
			$this->has_scheduled_sale_start( $product->get_id() ),
			'A long run of queued products should flush without waiting for shutdown.'
		);
	}

	/**
	 * @testdox Flushing an empty queue leaves the already scheduled actions alone.
	 */
	public function test_flushing_an_empty_queue_is_a_no_op() {
		$deferrer = $this->get_flushed_deferrer();
		$product  = $this->create_product_on_future_sale( $deferrer );

		$deferrer->queue_product( $product->get_id() );
		$deferrer->flush();

		$scheduled_action_id = as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' );
		$this->assertNotFalse( $scheduled_action_id, 'The product should be scheduled before the empty flush.' );

		/*
		 * Rescheduling always unschedules first, so a flush that did any work would replace the
		 * pending action with a new one. An unchanged action ID means nothing ran.
		 */
		$deferrer->flush();

		$this->assertSame(
			$scheduled_action_id,
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'Flushing an empty queue should leave the scheduled action untouched.'
		);
	}

	/**
	 * @testdox The shutdown flush runs after other shutdown callbacks, so their meta writes are not stranded.
	 */
	public function test_shutdown_flush_runs_after_other_shutdown_callbacks() {
		$deferrer = $this->get_flushed_deferrer();

		// Queueing anything registers the shutdown flush; the ID does not need to resolve to a product.
		$deferrer->queue_product( PHP_INT_MAX );

		$flush_priority = has_action( 'shutdown', array( $deferrer, 'handle_shutdown' ) );
		$this->assertNotFalse( $flush_priority, 'Queueing a product should register the shutdown flush.' );

		/*
		 * WC_Post_Data::do_deferred_product_sync saves products during shutdown, which can write
		 * sale-date meta and queue more work. Flushing before it runs would strand that work with
		 * nothing left to process it.
		 */
		$deferred_sync_priority = has_action( 'shutdown', array( 'WC_Post_Data', 'do_deferred_product_sync' ) );
		$this->assertNotFalse( $deferred_sync_priority, 'WC_Post_Data should register its deferred product sync.' );

		$this->assertGreaterThan(
			$deferred_sync_priority,
			$flush_priority,
			'The flush must run after shutdown callbacks that can write sale-date meta.'
		);

		$deferrer->flush();
	}
}
