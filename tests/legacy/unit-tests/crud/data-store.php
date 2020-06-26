<?php
/**
 * Data Store tests
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class for Data Store tests
 *
 * @since 3.0.0
 */
class WC_Tests_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure WC_Data_Store returns an exception if we try to load a data
	 * store that doesn't exist.
	 *
	 * @since 3.0.0
	 */
	public function test_invalid_store_throws_exception() {
		try {
			new WC_Data_Store( 'bogus' );
		} catch ( Exception $e ) {
			$this->assertEquals( $e->getMessage(), 'Invalid data store.' );
			return;
		}
		$this->fail( 'Invalid data store exception not correctly raised.' );
	}

	/**
	 * Make sure ::load throws an exception for invalid data stores
	 *
	 * @since 3.0.0
	 */
	public function test_invalid_store_load_throws_exception() {
		try {
			WC_Data_Store::load( 'does-not-exist' );
		} catch ( Exception $e ) {
			$this->assertEquals( $e->getMessage(), 'Invalid data store.' );
			return;
		}
		$this->fail( 'Invalid data store exception not correctly raised.' );
	}

	/**
	 * Make sure we can swap out stores.
	 *
	 * @since 3.0.0
	 */
	public function test_store_swap() {
		$this->load_dummy_store();

		$store = new WC_Data_Store( 'dummy' );
		$this->assertEquals( 'WC_Dummy_Data_Store_CPT', $store->get_current_class_name() );

		add_filter( 'woocommerce_dummy_data_store', array( $this, 'set_dummy_store' ) );

		$store = new WC_Data_Store( 'dummy' );
		$this->assertEquals( 'WC_Dummy_Data_Store_Custom_Table', $store->get_current_class_name() );

		add_filter( 'woocommerce_dummy_data_store', array( $this, 'set_default_dummy_store' ) );
	}

	/**
	 * Test to see if `first_second ``-> returns to `first` if unregistered.
	 *
	 * @since 3.0.0
	 */
	public function test_store_sub_type() {
		$this->load_dummy_store();

		$store = WC_Data_Store::load( 'dummy-sub' );
		$this->assertEquals( 'WC_Dummy_Data_Store_CPT', $store->get_current_class_name() );
	}

	/**
	 * Test WC_Data_Store::__call().
	 */
	public function test_data_store_method_overloading() {
		$this->load_dummy_store();
		$store = WC_Data_Store::load( 'dummy-sub' );
		$this->assertEquals(
			array( 'first param', 'second param', 'third param' ),
			$store->custom_method( 'first param', 'second param', 'third param', 'asdfsdf' )
		);
	}

	/* Helper Functions. */

	/**
	 * Loads two dummy data store classes that can be swapped out for each other. Adds to the `woocommerce_data_stores` filter.
	 *
	 * @since 3.0.0
	 */
	private function load_dummy_store() {
		include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/framework/class-wc-dummy-data-store.php';
		add_filter( 'woocommerce_data_stores', array( $this, 'add_dummy_data_store' ) );
	}

	/**
	 * Adds a default class for the 'dummy' data store.
	 *
	 * @param array $stores Loaded data stores.
	 * @return array Modified list of data stores.
	 * @since 3.0.0
	 */
	public function add_dummy_data_store( $stores ) {
		$stores['dummy'] = 'WC_Dummy_Data_Store_CPT';
		return $stores;
	}

	/**
	 * Helper function/filter to swap out the default dummy store for a different one.
	 *
	 * @param string $store Data store class name.
	 * @return string New data store class name.
	 * @since 3.0.0
	 */
	public function set_dummy_store( $store ) {
		return 'WC_Dummy_Data_Store_Custom_Table';
	}
}
