<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Caching\ObjectCache;

/**
 * Tests for the CacheException class.
 */
class CacheExceptionTest extends \WC_Unit_Test_Case {

	/**
	 * An instance of ObjectCache to be used as the $thrower parameter for the tested class constructor.
	 *
	 * @var ObjectCache
	 */
	private $thrower;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		// phpcs:disable Squiz.Commenting
		$this->thrower = new class() extends ObjectCache {
			public function get_object_type(): string {
				return 'the_type';
			}

			protected function get_object_id( $object ) {
			}

			protected function validate( $object ): ?array {
			}

			protected function get_from_datastore( $id ) {
			}
		};
		// phpcs:enable Squiz.Commenting
	}

	/**
	 * @testdox 'toString' includes the error message if only one single error is passed to constructor.
	 */
	public function test_to_string_with_id() {
		$exception = new CacheException( 'Something failed', $this->thrower, 1234 );

		$expected = 'CacheException: [the_type, id: 1234]: Something failed';
		$this->assertEquals( $expected, $exception->__toString() );
		$this->assertEquals( $expected, (string) $exception );
	}

	/**
	 * @testdox 'toString' doesn't include any of the passed error messages if more than one is passed to constructor.
	 */
	public function test_to_string_without_id() {
		$exception = new CacheException( 'Something failed', $this->thrower );

		$expected = 'CacheException: [the_type]: Something failed';
		$this->assertEquals( $expected, $exception->__toString() );
		$this->assertEquals( $expected, (string) $exception );
	}

	/**
	 * @testdox 'get_errors' returns an empty string if no errors are passed to constructor.
	 */
	public function test_not_passing_errors() {
		$exception = new CacheException( 'Something failed', $this->thrower );
		$this->assertEquals( array(), $exception->get_errors() );
	}

	/**
	 * @testdox There's a 'get_' method for all the parameters that can be passed to constructor.
	 */
	public function test_passing_all_parameters() {
		$message  = 'Something failed';
		$id       = 1234;
		$errors   = array( 'foo', 'bar' );
		$code     = 5678;
		$previous = new \Exception();

		$exception = new CacheException( $message, $this->thrower, 1234, $errors, $code, $previous );

		$this->assertEquals( $message, $exception->getMessage() );
		$this->assertEquals( $this->thrower, $exception->get_thrower() );
		$this->assertEquals( $id, $exception->get_cached_id() );
		$this->assertEquals( $errors, $exception->get_errors() );
		$this->assertEquals( $previous, $exception->getPrevious() );
	}
}
