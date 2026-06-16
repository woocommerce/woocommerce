<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
use WC_Product;

require_once __DIR__ . '/LocationStockTestCase.php';

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\InventoryController
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockService
 */
class LocationStockServiceTest extends LocationStockTestCase {

	/**
	 * @testdox Should not change legacy _stock when setting POS stock.
	 */
	public function test_set_pos_location_stock_does_not_change_legacy_stock(): void {
		$product = $this->create_managed_stock_product();

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should not allow negative POS stock through the direct set path.
	 */
	public function test_set_pos_location_stock_clamps_negative_stock(): void {
		$product = $this->create_managed_stock_product();

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, -5 );

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should treat missing location stock meta as zero for managed products.
	 */
	public function test_missing_location_stock_meta_is_zero_for_managed_products(): void {
		$product = $this->create_managed_stock_product();

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertNull( $this->service->decrease_location_stock( $product, LocationStockService::LOCATION_POS, 1 ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should not decrease POS stock below zero.
	 */
	public function test_decrease_pos_location_stock_does_not_go_negative(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 1 );

		$this->assertNull( $this->service->decrease_location_stock( $product, LocationStockService::LOCATION_POS, 2 ) );
		$this->assertEquals( 1, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should leave POS stock unchanged when Core stock is edited.
	 */
	public function test_core_stock_edits_do_not_change_pos_location_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$product->set_stock_quantity( 20 );
		$product->save();

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 20, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the product modified date when POS stock is set.
	 */
	public function test_set_pos_location_stock_updates_product_modified_date(): void {
		$product         = $this->create_managed_stock_product();
		$modified_before = $this->set_product_modified_date_to_past( $product );

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$this->assert_product_modified_after( $product->get_id(), $modified_before );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the product modified date when POS stock is decreased.
	 */
	public function test_decrease_pos_location_stock_updates_product_modified_date(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$modified_before = $this->set_product_modified_date_to_past( $product );

		$this->service->decrease_location_stock( $product, LocationStockService::LOCATION_POS, 2 );

		$this->assert_product_modified_after( $product->get_id(), $modified_before );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the variation modified date when variation-managed POS stock changes.
	 */
	public function test_variation_managed_location_stock_updates_variation_modified_date(): void {
		$variation = $this->create_variation_with_own_stock();
		$parent_id = $variation->get_parent_id();
		$parent    = wc_get_product( $parent_id );
		$this->assertInstanceOf( WC_Product::class, $parent );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );

		$this->service->decrease_location_stock( $variation, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
		$this->assertSame( $parent_modified_before, $this->get_product_modified_timestamp( $parent_id ) );
	}

	/**
	 * @testdox Should update parent and variation modified dates when parent-managed variation POS stock changes.
	 */
	public function test_parent_managed_variation_location_stock_updates_parent_and_variation_modified_dates(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->service->decrease_location_stock( $variation, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should update parent-managed variation modified dates when parent POS stock changes.
	 */
	public function test_parent_location_stock_updates_parent_managed_variation_modified_dates(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 6 );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->service->decrease_location_stock( $parent, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should follow the variation when the variation manages stock.
	 */
	public function test_variation_location_stock_uses_variation_when_variation_manages_stock(): void {
		$variation = $this->create_variation_with_own_stock();

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 6, $this->service->get_location_stock( $variation->get_id(), LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should follow the parent product when the variation uses parent-managed stock.
	 */
	public function test_parent_managed_variation_location_stock_uses_parent_product(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 6, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should normalize configured locations and cap them at five.
	 */
	public function test_configured_locations_are_normalized_and_capped(): void {
		$this->configure_pos_locations(
			array(
				array(
					'slug'      => 'Register 1',
					'name'      => 'Main till',
					'address_1' => '1 Shop Street',
					'city'      => 'Cardiff',
					'country'   => 'GB',
				),
				array(
					'slug' => '',
					'name' => 'Mobile counter',
				),
				array(
					'slug' => 'register-3',
					'name' => 'Register 3',
				),
				array(
					'slug' => 'register-4',
					'name' => 'Register 4',
				),
				array(
					'slug' => 'register-5',
					'name' => 'Register 5',
				),
				array(
					'slug' => 'register-6',
					'name' => 'Register 6',
				),
			)
		);

		$locations = $this->service->get_locations();

		$this->assertCount( 5, $locations );
		$this->assertArrayHasKey( 'register-1', $locations );
		$this->assertArrayHasKey( 'mobile-counter', $locations );
		$this->assertArrayNotHasKey( 'register-6', $locations );
		$this->assertSame( 'Main till', $locations['register-1']['name'] );
		$this->assertSame( 'Mobile counter', $locations['mobile-counter']['name'] );
		$this->assertSame( '1 Shop Street', $locations['register-1']['address_1'] );
		$this->assertSame( '', $locations['register-1']['address_2'] );
		$this->assertSame( 'Cardiff', $locations['register-1']['city'] );
		$this->assertSame( 'GB', $locations['register-1']['country'] );
	}

	/**
	 * @testdox Should format an order's configured POS location address.
	 */
	public function test_order_location_address_uses_order_location_meta(): void {
		$this->configure_pos_locations(
			array(
				array(
					'slug'      => 'register-2',
					'name'      => 'Register 2',
					'address_1' => '1 Shop Street',
					'address_2' => 'Unit 2',
					'city'      => 'Cardiff',
					'state'     => 'South Glamorgan',
					'postcode'  => 'CF10 1AA',
					'country'   => 'Testland',
				),
			)
		);

		$order = $this->create_location_order_for_product( $this->create_managed_stock_product(), 1, 'register-2' );

		$this->assertSame(
			"1 Shop Street\nUnit 2\nCardiff, South Glamorgan, CF10 1AA\nTestland",
			$this->service->get_order_location_address( $order )
		);
	}
}
