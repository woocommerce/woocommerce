<?php
/**
 * Tests for the DatabaseUtil utility.
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
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

	/**
	 * @test Test that we are able to create FTS index on order address table.
	 */
	public function test_create_fts_index_order_address_table() {
		$db = wc_get_container()->get( DataSynchronizer::class );
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$db->create_database_tables();
		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		if ( ! $this->sut->fts_index_on_order_item_table_exists() ) {
			$this->sut->create_fts_index_order_address_table();
		}
		$this->assertTrue( $this->sut->fts_index_on_order_address_table_exists() );
	}

	/**
	 * @test Test that we are able to create FTS index on order item table.
	 */
	public function test_create_fts_index_order_item_table() {
		if ( ! $this->sut->fts_index_on_order_item_table_exists() ) {
			$this->sut->create_fts_index_order_item_table();
		}
		$this->assertTrue( $this->sut->fts_index_on_order_item_table_exists() );
	}
}
