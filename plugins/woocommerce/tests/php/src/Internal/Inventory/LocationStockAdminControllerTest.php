<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Internal\Inventory\InventoryController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
use WC_Product;

require_once __DIR__ . '/LocationStockTestCase.php';

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockAdminController
 */
class LocationStockAdminControllerTest extends LocationStockTestCase {

	/**
	 * @testdox Should render a POS stock field for variation-managed stock.
	 */
	public function test_variation_location_stock_field_renders_for_variation_managed_stock(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertStringContainsString( 'name="variable_inventory_location_stock[pos][0]"', $output );
		$this->assertStringContainsString( 'id="variable_inventory_stock_pos0"', $output );
		$this->assertStringContainsString( 'value="6"', $output );
	}

	/**
	 * @testdox Should not render a variation POS stock field when the feature is disabled.
	 */
	public function test_variation_location_stock_field_is_gated_by_feature_flag(): void {
		$variation = $this->create_variation_with_own_stock();
		update_option( InventoryController::FEATURE_OPTION, 'no' );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertSame( '', $output );
	}

	/**
	 * @testdox Should render a variation POS stock field when stock is parent-managed.
	 */
	public function test_variation_location_stock_field_renders_for_parent_managed_stock(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertStringContainsString( 'name="variable_inventory_location_stock[pos][0]"', $output );
		$this->assertStringContainsString( 'id="variable_inventory_stock_pos0"', $output );
		$this->assertStringContainsString( 'value="0"', $output );
		$this->assertStringNotContainsString( 'value="4"', $output );
	}

	/**
	 * @testdox Should render the variation's own saved POS stock value before variation stock management is re-enabled.
	 */
	public function test_variation_location_stock_field_uses_variation_record_for_parent_managed_stock(): void {
		$variation = $this->create_variation_with_own_stock();
		$parent    = wc_get_product( $variation->get_parent_id() );
		$this->assertInstanceOf( WC_Product::class, $parent );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 30 );

		$parent->set_manage_stock( true );
		$parent->save();
		$variation->set_manage_stock( false );
		$variation->save();

		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );
		$variation_id = $variation->get_id();
		$this->clear_product_cache( $variation );
		$variation = wc_get_product( $variation_id );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertEquals( 4, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertStringContainsString( 'value="30"', $output );
		$this->assertStringNotContainsString( 'value="4"', $output );
	}

	/**
	 * @testdox Should save POS stock for variation-managed stock without changing Core stock.
	 */
	public function test_save_variation_location_stock_field_updates_pos_stock_only(): void {
		$variation     = $this->create_variation_with_own_stock();
		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->admin_controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 8, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 11, wc_get_product( $variation->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should ignore posted variation POS stock when the feature is disabled.
	 */
	public function test_save_variation_location_stock_field_is_gated_by_feature_flag(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		update_option( InventoryController::FEATURE_OPTION, 'no' );

		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->admin_controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should not save hidden variation POS stock when stock is parent-managed.
	 */
	public function test_save_variation_location_stock_field_ignores_parent_managed_variations(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );

		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->admin_controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 4, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 4, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
	}
}
