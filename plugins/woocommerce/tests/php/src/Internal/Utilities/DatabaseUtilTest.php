<?php
/**
 * Tests for the DatabaseUtil utility.
 */

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

/**
 * Tests relating to DatabaseUtil.
 */
class DatabaseUtilTest extends WC_Unit_Test_Case {

	/**
	 * @var DatabaseUtil
	 */
	private $sut;

	/**
	 * Set-up subject under test.
	 */
	public function set_up() {
		$this->sut = wc_get_container()->get( DatabaseUtil::class );
		parent::set_up();
	}

	/**
	 * @testdox Test that get_index_columns() will default to return the primary key index columns.
	 */
	public function test_get_index_columns_returns_primary_index_by_default() {
		global $wpdb;

		$this->assertEquals(
			array( 'product_id' ),
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup' )
		);
	}

	/**
	 * @testdox Test that get_index_columns() will return an array containing all columns of a multi-column index.
	 */
	public function test_get_index_columns_returns_multicolumn_index() {
		global $wpdb;

		$this->assertEquals(
			array( 'min_price', 'max_price' ),
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup', 'min_max_price' )
		);
	}

	/**
	 * @testdox Test that giving a non-existent index name to get_index_columns() will return an empty array.
	 */
	public function test_get_index_columns_returns_empty_for_invalid_index() {
		global $wpdb;

		$this->assertEmpty(
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup', 'invalid_index_name' )
		);
	}
}
