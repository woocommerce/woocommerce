<?php
/**
 * Class WC_Data file.
 *
 * @package WooCommerce\Tests\Abstracts
 */

/**
 * Class WC_Data.
 */
class WC_Data_Test extends WC_Unit_Test_Case {

	/**
	 * Test that create is called on data store when new object is saved.
	 */
	public function test_data_store_called_on_save() {
		$data_store = $this->getMockBuilder( WC_Object_Data_Store_Interface::class )->getMock();
		$data_store->expects( $this->once() )
			->method( 'create' )
			->with(
			   $this->isInstanceOf( WC_Data::class )
			);
		$data_store->expects( $this->once() )
			->method( 'update' )
			->with(
			   $this->isInstanceOf( WC_Data::class )
			);
		$data_store->expects( $this->once() )
			->method( 'delete' )
			->with(
			   $this->isInstanceOf( WC_Data::class )
			);

		$data_object = new class( $data_store ) extends WC_Data {
			public function __construct( $data_store ) {
				$this->data_store = $data_store;
			}
		};
		$data_object->save();
		$data_object->set_id( 1 );
		$data_object->save();
		$data_object->delete();
	}

	/**
	 * Test that cache is used when reading meta data.
	 */
	public function test_meta_data_cache() {
		$raw_meta_data = [];
		$data_store = $this->getMockBuilder( WC_Data_Store_WP::class )->getMock();
		$data_store->expects( $this->once() )
			->method( 'filter_raw_meta_data' )
			->with(
			   $this->isInstanceOf( WC_Data::class ),
			   $raw_meta_data
			);
		$data_store->expects( $this->once() )
			->method( 'read_meta' )
			->with(
			   $this->isInstanceOf( WC_Data::class )
			)
			->willReturn( $raw_meta_data );

		$data_object = new class( $data_store ) extends WC_Data {
			protected $cache_group = 'object_name';
			public function __construct( $data_store ) {
				$this->id = 1;
				$this->data_store = $data_store;
			}
		};
		$meta_data = $data_object->get_meta_data();
		$this->assertEquals( [], $meta_data );
		$data_object->read_meta_data();
	}
}
