<?php
/**
 * Notes tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\NotesUnavailableException;
use Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * Class WC_Admin_Tests_Notes
 */
class WC_Admin_Tests_Notes extends WC_Unit_Test_Case {

	/**
	 * If the "admin-note" data store exists, the data store should be
	 * loaded and returned.
	 */
	public function test_loads_data_store_if_exists() {
		$this->assertInstanceOf( \WC_Data_Store::class, Notes::load_data_store() );
	}

	/**
	 * If the "admin-note" data store does not exist, a custom
	 * exception should be thrown.
	 */
	public function test_exception_is_thrown_if_data_store_does_not_exist() {
		add_filter( 'woocommerce_data_stores', '__return_empty_array' );
		$this->expectException( NotesUnavailableException::class );
		Notes::load_data_store();
		remove_filter( 'woocommerce_data_stores', '__return_empty_array' );
	}

}
