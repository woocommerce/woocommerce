<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use WC_Helper_Product;

/**
 * Tests for StockManagementHelper
 */
class StockManagementHelperTests extends \WC_Unit_Test_Case {

	/**
	 * @var StockManagementHelper
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new StockManagementHelper();
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $this->sut );
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * @testdox get_managed_variations returns empty array for simple product
	 */
	public function test_get_managed_variations_returns_empty_array_for_simple_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( array(), $this->sut->get_managed_variations( $product ) );
	}

	/**
	 * @testdox get_managed_variations returns variations that inherit stock
	 */
	public function test_get_managed_variations_returns_variations_that_inherit_stock(): void {
		$variable           = WC_Helper_Product::create_variation_product();
		$managed_variations = array_filter(
			array_map(
				function ( $variation ) {
					$variation = \wc_get_product( $variation );
					if ( 'yes' !== $variation->get_manage_stock() ) {
						return $variation->get_id();
					}
					return null;
				},
				$variable->get_children()
			)
		);

		$this->assertEquals( $managed_variations, $this->sut->get_managed_variations( $variable ) );
	}

	/**
	 * @testdox get_managed_variations returns variations that inherit stock partially
	 */
	public function test_get_managed_variations_returns_variations_that_inherit_stock_partially(): void {
		$variable           = WC_Helper_Product::create_variation_product();
		$managed_variations = array_filter(
			array_map(
				function ( $variation ) {
					$variation = \wc_get_product( $variation );
					if ( 'yes' !== $variation->get_manage_stock() ) {
						return $variation->get_id();
					}
					return null;
				},
				$variable->get_children()
			)
		);

		// Set managed stock to yes.
		$variation = \wc_get_product( $managed_variations[0] );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();

		// Update managed variations.
		unset( $managed_variations[0] );
		$managed_variations = array_values( $managed_variations );

		$this->assertEquals( array(), $this->sut->get_managed_variations( $variation ) );
		$this->assertEquals( $managed_variations, $this->sut->get_managed_variations( $variable ) );
	}

	/**
	 * @testdox get_managed_variations returns empty array for variable with no children
	 */
	public function test_get_managed_variations_returns_empty_array_for_variable_with_no_children(): void {
		$variable = WC_Helper_Product::create_variation_product();

		foreach ( $variable->get_children() as $child_id ) {
			wp_delete_post( $child_id, true );
		}

		$this->assertEquals( array(), $this->sut->get_managed_variations( $variable ) );
	}

	/**
	 * @testdox get_managed_variations uses cache
	 */
	public function test_get_managed_variations_uses_cache(): void {
		$variable = WC_Helper_Product::create_variation_product();

		$result1 = $this->sut->get_managed_variations( $variable );

		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_manage_stock( ! $variation->get_manage_stock() );
		$variation->save();

		$result2 = $this->sut->get_managed_variations( $variable );

		$this->assertEquals( $result1, $result2 );
	}
}
