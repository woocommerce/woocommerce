<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Caching\WPCacheEngine;

/**
 * Tests for the ObjectCache class.
 */
class ObjectCacheTest extends \WC_Unit_Test_Case {

	/**
	 * The object to be tested.
	 *
	 * @var ObjectCache
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {

		// phpcs:disable Squiz.Commenting

		$this->sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			private $random_string_index = 0;

			protected function get_random_string(): string {
				$this->random_string_index++;
				return 'random_' . $this->random_string_index;
			}

			protected function get_object_id( $object ) {
				return null;
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
				return null;
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->sut->flush();
	}

	/**
	 * @testdox 'get_object_type' returns the object type defined by the cache class.
	 */
	public function test_get_object_type() {
		$this->assertEquals( 'the_type', $this->sut->get_object_type() );
	}

	/**
	 * @testdox The constructor of a class that declares an invalid object type throws an exception.
	 */
	public function test_class_with_invalid_get_object_type() {
		$this->expectException( CacheException::class );
		$class_name = InvalidObjectCacheClass::class;
		$message    = "Class $class_name returns an empty value for get_object_type";
		$this->expectExceptionMessage( $message );

		new InvalidObjectCacheClass();
	}

	/**
	 * @testdox 'set' throws an exception if null is passed for caching.
	 */
	public function test_try_set_null_object() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Can't cache a null value" );

		$this->sut->set( null );
	}

	/**
	 * @testdox 'set' throws an exception if a value that is neither an array nor an object is passed for caching.
	 */
	public function test_try_set_non_object_or_array_object() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Can't cache a non-object, non-array value" );

		$this->sut->set( 1234 );
	}

	/**
	 * @testdox 'set' throws an exception if a non-integer, non-string value is passed as the id for caching.
	 */
	public function test_try_set_non_int_or_string_id() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Object id must be an int, a string, or null for 'set'" );

		$this->sut->set( array( 'foo' ), array( 1, 2 ) );
	}

	/**
	 * @testdox 'set' throws an exception if an invalid expiration value is passed.
	 *
	 * @testWith [0]
	 *           [-2]
	 *           [9999999]
	 *
	 * @param int $expiration The expiration value to test.
	 */
	public function test_try_set_invalid_expiration_value( int $expiration ) {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( 'Invalid expiration value, must be ObjectCache::DEFAULT_EXPIRATION or a value between 1 and ObjectCache::MAX_EXPIRATION' );

		$this->sut->set( array( 'foo' ), 'the_id', $expiration );
	}

	/**
	 * @testdox 'set' caches the passed object under the passed id as expected.
	 */
	public function test_set_new_object_with_id_caches_with_expected_key() {
		$object = array( 'foo' );
		$result = $this->sut->set( $object, 'the_id' );

		$this->assertTrue( $result );

		$expected_prefix = \WC_Cache_Helper::get_cache_prefix( 'the_type' );
		$key             = $expected_prefix . 'the_id';

		$this->assertEquals( $object, wp_cache_get( $key, 'the_type' ) );
	}

	/**
	 * @testdox Successive calls to 'set' use the same cache key prefix.
	 */
	public function test_setting_two_objects_result_in_same_prefix() {
		$object_1 = array( 'foo' );
		$this->sut->set( $object_1, 'the_id_1' );
		$object_2 = array( 1, 2, 3, 4 );
		$this->sut->set( $object_2, 9999 );

		$prefix = \WC_Cache_Helper::get_cache_prefix( 'the_type' );

		$key_1 = $prefix . 'the_id_1';
		$this->assertEquals( $object_1, wp_cache_get( $key_1, 'the_type' ) );
		$key_2 = $prefix . '9999';
		$this->assertEquals( $object_2, wp_cache_get( $key_2, 'the_type' ) );
	}

	/**
	 * @testdox 'set' throws an exception if no object id is passed and the class doesn't implement 'get_object_id'.
	 */
	public function test_set_null_id_without_id_retrieval_implementation() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Null id supplied and the cache class doesn't implement get_object_id" );

		$this->sut->set( array( 'foo' ) );
	}

	/**
	 * @testdox 'set' uses the id returned by 'get_object_id' if no object id is passed.
	 */
	public function test_set_null_id_with_id_retrieval_implementation() {
		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_object_id( $object ) {
				return $object['id'] + 1;
			}

			protected function get_random_string(): string {
				return 'random';
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
				return null;
			}
		};

		// phpcs:enable Squiz.Commenting

		$sut->set( array( 'id' => 1234 ) );

		$this->assertEquals( array( 'id' => 1234 ), $sut->get( '1235' ) );
	}

	/**
	 * @testdox 'update_if_cached' does nothing if no object is cached with the passed (or obtained) id.
	 */
	public function test_update_if_cached_does_nothing_for_not_cached_id() {
		$id = 1234;
		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_object_id( $object ) {
				return $object['id'];
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
				return null;
			}
		};

		// phpcs:enable Squiz.Commenting

		$result = $sut->update_if_cached( array( 'id' => 1234 ), $id );
		$this->assertFalse( $result );
		$this->assertEmpty( $sut->get( $id ) );
	}

	/**
	 * @testdox 'update_if_cached' updates an already cached object the same way as 'set'.
	 */
	public function test_update_if_cached_updates_already_cached_object() {
		$id = 1234;
		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_object_id( $object ) {
				return $object['id'];
			}

			protected function get_random_string(): string {
				return 'random';
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
				return null;
			}
		};

		// phpcs:enable Squiz.Commenting

		$sut->set( array( 'id' => 1234 ), $id );
		$this->assertEquals( array( 'id' => 1234 ), $sut->get( $id ) );

		$new_value = array(
			'id'  => 1234,
			'foo' => 'bar',
		);
		$result    = $sut->update_if_cached( $new_value, $id );
		$this->assertTrue( $result );
		$this->assertEquals( $new_value, $sut->get( $id ) );
	}

	/**
	 * @testdox 'update_if_cached' throws an exception if no object id is passed and the class doesn't implement 'get_object_id'.
	 */
	public function test_update_if_cached_null_id_without_id_retrieval_implementation() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Null id supplied and the cache class doesn't implement get_object_id" );

		$this->sut->update_if_cached( array( 'foo' ) );
	}

	/**
	 * @testdox 'set' throws an exception if 'validate' returns errors.
	 *
	 * @testWith [1]
	 *           [2]
	 *
	 * @param int $errors_count How many errors will 'validate' return.
	 */
	public function test_set_with_custom_serialization_that_returns_errors( int $errors_count ) {
		$exception = null;
		$errors    = 1 === $errors_count ? array( 'Foo failed' ) : array( 'Foo failed', 'Bar failed' );
		$object    = array( 'foo' );

		// phpcs:disable Squiz.Commenting

		$sut = new class($errors) extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			private $errors;

			public function __construct( $errors ) {
				$this->errors = $errors;
			}

			protected function validate( $object ): array {
				return $this->errors;
			}

			protected function get_from_datastore( $id ) {
				return null;
			}

			protected function get_object_id( $object ) {
				return $object['id'];
			}
		};

		// phpcs:enable Squiz.Commenting

		try {
			$sut->set( $object, 1234 );
		} catch ( CacheException $thrown ) {
			$exception = $thrown;
		}

		$expected_message = 'Object validation/serialization failed';
		if ( 1 === $errors_count ) {
			$expected_message .= ': Foo failed';
		}
		$this->assertEquals( $expected_message, $exception->getMessage() );
		$this->assertEquals( $errors, $exception->get_errors() );
	}

	/**
	 * @testdox 'get' throws an exception if a non-integer, non-string id is passed.
	 *
	 * @testWith [null]
	 *           [[1,2]]
	 *
	 * @param mixed $id The id to test.
	 * @return void
	 */
	public function test_try_get_non_int_or_string_key( $id ) {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Object id must be an int or a string for 'get'" );

		$this->sut->get( $id );
	}

	/**
	 * @testdox 'get' throws an exception if an invalid expiration value is passed.
	 *
	 * @testWith [0]
	 *           [-2]
	 *           [9999999]
	 *
	 * @param int $expiration The expiration value to test.
	 */
	public function test_try_get_with_invalid_expiration_value( int $expiration ) {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( 'Invalid expiration value, must be ObjectCache::DEFAULT_EXPIRATION or a value between 1 and ObjectCache::MAX_EXPIRATION' );

		$this->sut->get( 'the_id', $expiration );
	}

	/**
	 * @testdox 'get' retrieves a previously cached object by id as expected.
	 */
	public function test_try_getting_previously_cached_object() {
		$object = array( 'foo' );
		$this->sut->set( $object, 'the_id' );

		$result = $this->sut->get( 'the_id' );
		$this->assertEquals( $object, $result );
	}

	/**
	 * @testdox 'get' returns null if there's no object cached under the passed id.
	 */
	public function test_try_getting_not_cached_object() {
		$result = $this->sut->get( 'NOT_CACHED' );
		$this->assertNull( $result );
	}

	/**
	 * @testdox 'get' uses the passed object retrieval callback if there's no object cached under the passed id, and caches the object retrieved.
	 */
	public function test_try_getting_not_cached_object_with_callback() {
		$callback = function( $id ) {
			return array( 'id' => $id );
		};

		$result = $this->sut->get( 'the_id', ObjectCache::DEFAULT_EXPIRATION, $callback );

		$expected = array( 'id' => 'the_id' );
		$this->assertEquals( $expected, $result );
		$this->assertEquals( $expected, $this->sut->get( 'the_id' ) );
	}

	/**
	 * @testdox 'remove' removes a cached object and returns true, or returns false if there's no cached object under the passed id.
	 */
	public function test_remove() {
		$this->sut->set( array( 'foo' ), 'the_id_1' );
		$this->sut->set( array( 'bar' ), 'the_id_2' );

		$result_1 = $this->sut->remove( 'the_id_1' );
		$result_2 = $this->sut->remove( 'the_id_X' );

		$this->assertTrue( $result_1 );
		$this->assertFalse( $result_2 );

		$this->assertFalse( $this->sut->is_cached( 'the_id_1' ) );
		$this->assertTrue( $this->sut->is_cached( 'the_id_2' ) );
	}

	/**
	 * @testdox 'flush' deletes the stored cache key prefix, effectively rendering the cached objects inaccessible.
	 */
	public function test_flush() {
		$this->sut->set( array( 'foo' ), 'the_id' );

		$current_prefix_key = \WC_Cache_Helper::get_cache_prefix( 'the_type' );
		$this->sut->flush();
		$this->assertFalse( $this->sut->is_cached( 'the_id' ) );
		$expected_new_prefix = \WC_Cache_Helper::get_cache_prefix( 'the_type' );
		$this->assertNotEquals( $current_prefix_key, $expected_new_prefix );

		$this->sut->set( array( 'bar' ), 'the_id_2' );

		$this->assertEquals( $expected_new_prefix, \WC_Cache_Helper::get_cache_prefix( 'the_type' ) );
		$this->assertFalse( $this->sut->is_cached( 'the_id' ) );
		$this->assertTrue( $this->sut->is_cached( 'the_id_2' ) );
	}

	/**
	 * @testdox A custom cache engine instance can be used by overriding 'get_cache_engine_instance'.
	 */
	public function test_custom_cache_engine_via_protected_method() {
		$engine = new WPCacheEngine();

		// phpcs:disable Squiz.Commenting
		$sut = new class($engine) extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			private $engine;

			public function __construct( $engine ) {
				$this->engine = $engine;
				parent::__construct();
			}

			protected function get_cache_engine_instance(): CacheEngine {
				return $this->engine;
			}

			protected function get_object_id( $object ) {
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
			}
		};
		// phpcs:enable Squiz.Commenting

		$object = array( 'foo' );
		$sut->set( $object, 'the_id' );

		$this->assertEquals( $object, $sut->get( 'the_id' ) );
	}

	/**
	 * @testdox A custom cache engine instance can be used via 'wc_object_cache_get_engine' filter.
	 */
	public function test_custom_cache_engine_via_hook() {
		$engine                  = new class() extends WPCacheEngine {};
		$engine_passed_to_filter = null;
		$cache_passed_to_filter  = null;

		$sut = new class() extends ObjectCache {
			// phpcs:disable Squiz.Commenting
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_object_id( $object ) {
			}

			protected function validate( $object ): ?array {
				return null;
			}

			protected function get_from_datastore( $id ) {
			}
			// phpcs:enable Squiz.Commenting
		};

		add_filter(
			'wc_object_cache_get_engine',
			function( $old_engine, $cache ) use ( $engine, &$engine_passed_to_filter, &$cache_passed_to_filter ) {
				$engine_passed_to_filter = $old_engine;
				$cache_passed_to_filter  = $cache;
				return $engine;
			},
			2,
			10
		);

		$object = array( 'foo' );
		$sut->set( $object, 'the_id' );

		$this->assertEquals( $object, $sut->get( 'the_id' ) );

		$this->assertEquals( $engine_passed_to_filter, wc_get_container()->get( WPCacheEngine::class ) );
		$this->assertEquals( $cache_passed_to_filter, $sut );
	}
}
