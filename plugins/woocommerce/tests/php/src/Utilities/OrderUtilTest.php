<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A collection of tests for the string utility class.
 */
class OrderUtilTest extends \WC_Unit_Test_Case {
	/**
	 * @var bool
	 */
	protected $prev_cot_state;

	/**
	 * Store the COT state before the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->prev_cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Restore the COT state after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot( $this->prev_cot_state );
		parent::tearDown();
	}

	/**
	 * @testdox `get_table_for_orders` should return the name of the posts table when HPOS is not in use.
	 */
	public function test_get_table_for_orders_posts() {
		global $wpdb;

		OrderHelper::toggle_cot( false );

		$table_name = OrderUtil::get_table_for_orders();
		$this->assertEquals( $wpdb->posts, $table_name );
	}

	/**
	 * @testdox `get_table_for_orders` should return the name of the orders table when HPOS is in use.
	 */
	public function test_get_table_for_orders_hpos() {
		global $wpdb;

		OrderHelper::toggle_cot( true );

		$table_name = OrderUtil::get_table_for_orders();
		$this->assertEquals( "{$wpdb->prefix}wc_orders", $table_name );
	}

	/**
	 * @testdox `get_table_for_order_meta` should return the name of the postmeta table when HPOS is not in use.
	 */
	public function test_get_table_for_order_meta_posts() {
		global $wpdb;

		OrderHelper::toggle_cot( false );

		$table_name = OrderUtil::get_table_for_order_meta();
		$this->assertEquals( $wpdb->postmeta, $table_name );
	}

	/**
	 * @testdox `get_table_for_order_meta` should return the name of the orders meta table when HPOS is in use.
	 */
	public function test_get_table_for_order_meta_hpos() {
		global $wpdb;

		OrderHelper::toggle_cot( true );

		$table_name = OrderUtil::get_table_for_order_meta();
		$this->assertEquals( "{$wpdb->prefix}wc_orders_meta", $table_name );
	}
}
