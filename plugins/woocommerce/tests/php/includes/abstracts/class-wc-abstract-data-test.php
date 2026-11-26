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

	/**
	 * @testdox Should store a WC_DateTime object passed to a date setter as a date.
	 */
	public function test_set_date_prop_accepts_wc_datetime_object(): void {
		$order    = new WC_Order();
		$datetime = new WC_DateTime( '@1700000000', new DateTimeZone( 'UTC' ) );

		$order->set_date_created( $datetime );

		$this->assertInstanceOf( WC_DateTime::class, $order->get_date_created() );
		$this->assertSame( 1700000000, $order->get_date_created()->getTimestamp() );
	}

	/**
	 * @testdox Should not treat the WC_DateTime class name passed to a date setter as a date object.
	 */
	public function test_set_date_prop_does_not_treat_class_name_as_date_object(): void {
		$order = new WC_Order();

		$order->set_date_created( 'WC_DateTime' );

		$this->assertNotSame( 'WC_DateTime', $order->get_date_created(), 'The class name string must not be stored as the date' );
	}

	/**
	 * @testdox A listener that does not accept $staged refuses a direct stage call and does not run, and works unchanged on the saving path.
	 * @testWith ["woocommerce_test_staged_action"]
	 *           ["woocommerce_test_staged_filter"]
	 *
	 * @param string $hook Hook that the stage method fires.
	 */
	public function test_a_listener_without_staged_refuses_a_direct_stage_call( string $hook ): void {
		$object = $this->create_staging_object();
		$runs   = 0;
		add_filter(
			$hook,
			static function ( $value ) use ( &$runs ) {
				++$runs;
				return $value;
			},
			10,
			2
		);

		$object->set_value();
		$this->assertSame( 1, $runs, 'The listener runs on the saving path' );

		$this->expectException( WC_Data_Exception::class );
		try {
			$object->stage_value();
		} finally {
			$this->assertSame( 1, $runs, 'The listener did not run on the refused call' );
		}
	}

	/**
	 * @testdox A listener that accepts $staged gets true on a direct stage call and false on the saving path, and a filter returns the filtered value.
	 * @testWith ["woocommerce_test_staged_action", "value"]
	 *           ["woocommerce_test_staged_filter", "filtered"]
	 *
	 * @param string $hook     Hook that the stage method fires.
	 * @param string $returned Value that apply_staged_filters() returns.
	 */
	public function test_a_listener_with_staged_gets_true_then_false( string $hook, string $returned ): void {
		$object = $this->create_staging_object();
		$staged = array();
		add_filter(
			$hook,
			static function ( $value, $data_object, $is_staged ) use ( &$staged ) {
				$staged[] = $is_staged;
				return 'filtered';
			},
			10,
			3
		);

		$object->stage_value();
		$object->set_value();

		$this->assertSame( array( true, false ), $staged );
		$this->assertSame( array( $returned, $returned ), $object->filtered );
	}

	/**
	 * @testdox __return_true on a filter refuses a direct stage call: no callback is exempt.
	 */
	public function test_return_true_refuses_a_direct_stage_call(): void {
		$object = $this->create_staging_object();
		add_filter( 'woocommerce_test_staged_filter', '__return_true' );

		$this->expectException( WC_Data_Exception::class );
		$this->expectExceptionMessage( 'woocommerce_test_staged_filter hook does not support staged changes: __return_true.' );
		$object->stage_value();
	}

	/**
	 * @testdox The refusal names the hook, each callback that does not accept $staged, and the argument count to register with.
	 */
	public function test_the_refusal_names_the_hook_and_each_callback(): void {
		$object  = $this->create_staging_object();
		$closure = static function () {};
		add_action( 'woocommerce_test_staged_action', '__return_false' );
		add_action( 'woocommerce_test_staged_action', array( self::class, 'listener_without_staged' ), 10, 2 );
		add_action( 'woocommerce_test_staged_action', $closure );
		add_action( 'woocommerce_test_staged_action', array( self::class, 'listener_with_staged' ), 10, 3 );

		try {
			$object->stage_value();
			$this->fail( 'The direct stage call was not refused' );
		} catch ( WC_Data_Exception $e ) {
			$this->assertSame( 'woocommerce_data_staging_unsupported', $e->getErrorCode() );
			$this->assertStringContainsString(
				'woocommerce_test_staged_action hook does not support staged changes: __return_false, WC_Data_Test::listener_without_staged, a closure in plugins/woocommerce/tests/php/includes/abstracts/class-wc-abstract-data-test.php on line ' . ( new ReflectionFunction( $closure ) )->getStartLine() . '. Each of these callbacks needs to accept $staged as its last argument, registered with 3 accepted arguments.',
				$e->getMessage()
			);
		}
	}

	/**
	 * Listener that was written before $staged.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public static function listener_without_staged( $value ) {
		return $value;
	}

	/**
	 * Listener that accepts $staged.
	 *
	 * @param mixed   $value       Value.
	 * @param WC_Data $data_object Object.
	 * @param bool    $staged      Whether the object is staged.
	 * @return mixed
	 */
	public static function listener_with_staged( $value, $data_object, $staged ) {
		return $value;
	}

	/**
	 * Create an object with a stage method that fires woocommerce_test_staged_action and woocommerce_test_staged_filter,
	 * and a saving method that runs it through write_through().
	 *
	 * @return WC_Data
	 */
	private function create_staging_object(): WC_Data {
		return new class() extends WC_Data {
			/**
			 * Values that the filter returned.
			 *
			 * @var array
			 */
			public $filtered = array();

			/**
			 * Saving method.
			 */
			public function set_value(): void {
				$this->write_through(
					function () {
						$this->stage_value();
					}
				);
			}

			/**
			 * Stage method.
			 */
			public function stage_value(): void {
				$this->refuse_unstaged_override( 'set_value', 'stage_value' );
				$this->do_staged_action( 'woocommerce_test_staged_action', 'value', $this );
				$this->filtered[] = $this->apply_staged_filters( 'woocommerce_test_staged_filter', 'value', $this );
			}
		};
	}
}
