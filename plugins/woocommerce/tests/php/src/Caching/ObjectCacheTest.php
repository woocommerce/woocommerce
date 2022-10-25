<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Caching\WpCacheEngine;

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
	 * The cache engine to use.
	 *
	 * @var CacheEngine
	 */
	private $cache_engine;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$cache_engine       = new InMemoryObjectCacheEngine();
		$this->cache_engine = $cache_engine;

		$container = wc_get_container();
		$container->replace( WpCacheEngine::class, $cache_engine );
		$this->reset_container_resolutions();

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
		};

		// phpcs:enable Squiz.Commenting
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		delete_option( 'wp_object_cache_key_prefix_the_type' );
		remove_all_filters( 'wc_object_cache_get_engine' );
		remove_all_filters( 'woocommerce_after_serializing_the_type_for_caching' );
		remove_all_actions( 'woocommerce_after_removing_the_type_from_cache' );
		remove_all_actions( 'woocommerce_after_flushing_the_type_cache' );

		parent::tearDown();
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

		$this->sut->set( 'the_id', null );
	}

	/**
	 * @testdox 'set' throws an exception if a value that is neither an array nor an object is passed for caching.
	 */
	public function test_try_set_non_object_or_array_object() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Can't cache a non-object, non-array value" );

		$this->sut->set( 'the_id', 1234 );
	}

	/**
	 * @testdox 'set' throws an exception if a non-integer, non-string value is passed as the id for caching.
	 */
	public function test_try_set_non_int_or_string_id() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Object id must be an int, a string, or null for 'set'" );

		$this->sut->set( array( 1, 2 ), array( 'foo' ) );
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

		$this->sut->set( 'the_id', array( 'foo' ), $expiration );
	}

	/**
	 * @testdox 'set' returns false if the cache engine's caching method fails.
	 */
	public function try_set_when_cache_engine_fails() {
		$this->cache_engine->caching_succeeds = false;

		$result = $this->sut->set( 'the_id', array( 'foo' ) );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox 'set' caches the passed object under the passed id as expected.
	 */
	public function test_set_new_object_with_id_caches_with_expected_key() {
		$object = array( 'foo' );
		$result = $this->sut->set( 'the_id', $object );

		$this->assertTrue( $result );

		$expected_prefix = 'woocommerce_object_cache|the_type|random_1|';
		$this->assertEquals( $expected_prefix, get_option( 'wp_object_cache_key_prefix_the_type' ) );

		$key             = $expected_prefix . 'the_id';
		$expected_cached = array( 'data' => $object );
		$this->assertEquals( $expected_cached, $this->cache_engine->cache[ $key ] );
	}

	/**
	 * @testdox Successive calls to 'set' use the same cache key prefix.
	 */
	public function test_setting_two_objects_result_in_same_prefix() {
		$object_1 = array( 'foo' );
		$this->sut->set( 'the_id_1', $object_1 );
		$object_2 = array( 1, 2, 3, 4 );
		$this->sut->set( 9999, $object_2 );

		$prefix = 'woocommerce_object_cache|the_type|random_1|';

		$key_1           = $prefix . 'the_id_1';
		$expected_cached = array( 'data' => $object_1 );
		$this->assertEquals( $expected_cached, $this->cache_engine->cache[ $key_1 ] );
		$key_2           = $prefix . '9999';
		$expected_cached = array( 'data' => $object_2 );
		$this->assertEquals( $expected_cached, $this->cache_engine->cache[ $key_2 ] );
	}

	/**
	 * @testdox 'set' uses the default expiration value if no explicit value is passed.
	 */
	public function test_set_with_default_expiration() {
		$this->sut->set( 'the_id', array( 'foo' ) );
		$this->assertEquals( $this->sut->get_default_expiration_value(), $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'set' uses the explicitly passed expiration value.
	 */
	public function test_set_with_explicit_expiration() {
		$this->sut->set( 'the_id', array( 'foo' ), 1234 );
		$this->assertEquals( 1234, $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'set' throws an exception if no object id is passed and the class doesn't implement 'get_object_id'.
	 */
	public function test_set_null_id_without_id_retrieval_implementation() {
		$this->expectException( CacheException::class );
		$this->expectExceptionMessage( "Null id supplied and the cache class doesn't implement get_object_id" );

		$this->sut->set( null, array( 'foo' ) );
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
		};

		// phpcs:enable Squiz.Commenting

		$sut->set( null, array( 'id' => 1234 ) );

		$this->assertEquals( 'woocommerce_object_cache|the_type|random|1235', array_keys( $this->cache_engine->cache )[0] );
	}

	/**
	 * @testdox 'set' caches the value returned by 'serialize'.
	 */
	public function test_set_with_custom_serialization() {
		$object = array( 'foo' );

		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function serialize( $object ): array {
				return array( 'the_data' => $object );
			}
		};

		// phpcs:enable Squiz.Commenting

		$sut->set( 1234, $object );

		$cached   = array_values( $this->cache_engine->cache )[0];
		$expected = array( 'the_data' => $object );
		$this->assertEquals( $expected, $cached );
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
		};

		// phpcs:enable Squiz.Commenting

		try {
			$sut->set( 1234, $object );
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
		$this->sut->set( 'the_id', $object );

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
		$this->assertEquals( array( 'data' => $expected ), array_values( $this->cache_engine->cache )[0] );
		$this->assertEquals( $this->sut->get_default_expiration_value(), $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'get' uses the passed object retrieval callback if there's no object cached under the passed id, and caches the object retrieved using the passed expiration value.
	 */
	public function test_try_getting_not_cached_object_with_callback_and_explicit_expiration() {
		$expiration = 1234;

		$callback = function( $id ) {
			return array( 'id' => $id );
		};

		$result = $this->sut->get( 'the_id', $expiration, $callback );

		$expected = array( 'id' => 'the_id' );
		$this->assertEquals( $expected, $result );
		$this->assertEquals( array( 'data' => $expected ), array_values( $this->cache_engine->cache )[0] );
		$this->assertEquals( $expiration, $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'get' uses the 'get_from_datastore' method if there's no object cached under the passed id, and caches the object retrieved.
	 */
	public function test_try_getting_not_cached_object_get_from_datastore_implemented() {
		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_from_datastore( $id ) {
				return array( 'id' => $id );
			}
		};

		// phpcs:enable Squiz.Commenting

		$result = $sut->get( 'the_id' );

		$expected = array( 'id' => 'the_id' );
		$this->assertEquals( $expected, $result );
		$this->assertEquals( array( 'data' => $expected ), array_values( $this->cache_engine->cache )[0] );
		$this->assertEquals( $this->sut->get_default_expiration_value(), $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'get' uses the 'get_from_datastore' method if there's no object cached under the passed id, and caches the object retrieved using the passed expiration value.
	 */
	public function test_try_getting_not_cached_object_get_from_datastore_implemented_and_explicit_expiration() {
		$expiration = 1234;

		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_from_datastore( $id ) {
				return array( 'id' => $id );
			}
		};

		// phpcs:enable Squiz.Commenting

		$result = $sut->get( 'the_id', $expiration );

		$expected = array( 'id' => 'the_id' );
		$this->assertEquals( $expected, $result );
		$this->assertEquals( array( 'data' => $expected ), array_values( $this->cache_engine->cache )[0] );
		$this->assertEquals( $expiration, $this->cache_engine->last_expiration );
	}

	/**
	 * @testdox 'get' applies 'deserialize' to the object returned by the cache engine before returning it.
	 */
	public function test_custom_deserialization() {
		// phpcs:disable Squiz.Commenting

		$sut = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function deserialize( array $serialized ) {
				$object   = $serialized['data'];
				$object[] = 3;
				return $object;
			}
		};

		// phpcs:enable Squiz.Commenting

		$sut->set( 'the_id', array( 1, 2 ) );

		$result   = $sut->get( 'the_id' );
		$expected = array( 1, 2, 3 );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'remove' removes a cached object and returns true, or returns false if there's no cached object under the passed id.
	 */
	public function test_remove() {
		$this->sut->set( 'the_id_1', array( 'foo' ) );
		$this->sut->set( 'the_id_2', array( 'bar' ) );

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
		$this->sut->set( 'the_id', array( 'foo' ) );

		$this->sut->flush();
		$this->assertFalse( get_option( 'wp_object_cache_key_prefix_the_type' ) );

		$this->sut->set( 'the_id_2', array( 'bar' ) );

		$expected_new_prefix = 'woocommerce_object_cache|the_type|random_2|';
		$this->assertEquals( $expected_new_prefix, get_option( 'wp_object_cache_key_prefix_the_type' ) );
		$this->assertFalse( $this->sut->is_cached( 'the_id' ) );
		$this->assertTrue( $this->sut->is_cached( 'the_id_2' ) );
	}

	/**
	 * @testdox A custom cache engine instance can be used by overriding 'get_cache_engine_instance'.
	 */
	public function test_custom_cache_engine_via_protected_method() {
		$engine = new InMemoryObjectCacheEngine();

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
		};

		// phpcs:enable Squiz.Commenting

		$object = array( 'foo' );
		$sut->set( 'the_id', $object );

		$expected_cached = array( 'data' => $object );
		$this->assertEquals( $expected_cached, array_values( $engine->cache )[0] );
	}

	/**
	 * @testdox A custom cache engine instance can be used via 'wc_object_cache_get_engine' filter.
	 */
	public function test_custom_cache_engine_via_hook() {
		$engine                  = new InMemoryObjectCacheEngine();
		$engine_passed_to_filter = null;
		$cache_passed_to_filter  = null;

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
		$this->sut->set( 'the_id', $object );

		$expected_cached = array( 'data' => $object );
		$this->assertEquals( $expected_cached, array_values( $engine->cache )[0] );

		$this->assertEquals( $engine_passed_to_filter, wc_get_container()->get( WpCacheEngine::class ) );
		$this->assertEquals( $cache_passed_to_filter, $this->sut );
	}

	/**
	 * @testdox 'woocommerce_after_serializing_{type}_for_caching' allows to modify the serialized object before being cached.
	 */
	public function test_modifying_serialized_object_via_filter() {
		$object_passed_to_filter = null;
		$id_passed_to_filter     = null;

		add_filter(
			'woocommerce_after_serializing_the_type_for_caching',
			function( $data, $object, $id ) use ( &$object_passed_to_filter, &$id_passed_to_filter ) {
				$object_passed_to_filter = $object;
				$id_passed_to_filter     = $id;

				$data['foo'] = 'bar';
				return $data;
			},
			10,
			3
		);

		$object = array( 'fizz' );
		$this->sut->set( 'the_id', $object );

		$expected_cached = array(
			'data' => $object,
			'foo'  => 'bar',
		);
		$this->assertEquals( $expected_cached, array_values( $this->cache_engine->cache )[0] );

		$this->assertEquals( $object, $object_passed_to_filter );
		$this->assertEquals( 'the_id', $id_passed_to_filter );
	}

	/**
	 * @testdox 'woocommerce_after_deserializing_{type}_from_cache' allows to modify the deserialized object before it's returned by 'get'.
	 */
	public function test_modifying_deserialized_object_via_filter() {
		$object_passed_to_filter = null;
		$id_passed_to_filter     = null;
		$data_passed_to_filter   = null;

		$original_object    = array( 'foo' );
		$replacement_object = array( 'bar' );

		add_filter(
			'woocommerce_after_deserializing_the_type_from_cache',
			function( $object, $data, $id ) use ( &$object_passed_to_filter, &$id_passed_to_filter, &$data_passed_to_filter, $replacement_object ) {
				$object_passed_to_filter = $object;
				$id_passed_to_filter     = $id;
				$data_passed_to_filter   = $data;

				return $replacement_object;
			},
			10,
			3
		);

		$this->sut->set( 'the_id', $original_object );
		$retrieved_object = $this->sut->get( 'the_id' );

		$this->assertEquals( $replacement_object, $retrieved_object );

		$this->assertEquals( $original_object, $object_passed_to_filter );
		$this->assertEquals( array( 'data' => $original_object ), $data_passed_to_filter );
		$this->assertEquals( 'the_id', $id_passed_to_filter );
	}

	/**
	 * @testdox 'remove' triggers the 'woocommerce_after_removing_{type}_from_cache' action.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $operation_succeeds Whether the removal operation succeeds or not.
	 */
	public function test_action_triggered_on_object_removed_from_cache( bool $operation_succeeds ) {
		$id_passed_to_action     = null;
		$result_passed_to_action = null;

		add_action(
			'woocommerce_after_removing_the_type_from_cache',
			function( $id, $result ) use ( &$id_passed_to_action, &$result_passed_to_action ) {
				$id_passed_to_action     = $id;
				$result_passed_to_action = $result;
			},
			10,
			2
		);

		$this->sut->set( 'the_id', array( 'foo' ) );
		$this->sut->remove( $operation_succeeds ? 'the_id' : 'INVALID_ID' );

		$this->assertEquals( $operation_succeeds ? 'the_id' : 'INVALID_ID', $id_passed_to_action );
		$this->assertEquals( $operation_succeeds, $result_passed_to_action );
	}

	/**
	 * @testdox 'flush' triggers the 'woocommerce_after_flushing_{type}_cache' action.
	 */
	public function test_action_triggered_on_cache_flushed() {
		$cache_passed_to_action  = null;
		$engine_passed_to_action = null;

		add_action(
			'woocommerce_after_flushing_the_type_cache',
			function( $cache, $engine ) use ( &$cache_passed_to_action, &$engine_passed_to_action ) {
				$cache_passed_to_action  = $cache;
				$engine_passed_to_action = $engine;
			},
			10,
			2
		);

		$this->sut->flush();

		$this->assertEquals( $this->sut, $cache_passed_to_action );
		$this->assertEquals( $this->cache_engine, $engine_passed_to_action );
	}
}
