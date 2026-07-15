<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\RequestCache;
use RuntimeException;
use WC_Unit_Test_Case;

/**
 * Tests for the RequestCache class.
 */
class RequestCacheTest extends WC_Unit_Test_Case {

	private const CACHE_GROUP       = 'woocommerce_request_cache_test';
	private const OTHER_CACHE_GROUP = 'woocommerce_request_cache_test_other';

	/**
	 * The System Under Test.
	 *
	 * @var RequestCache
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( RequestCache::class );
		$this->sut->clear_group( self::CACHE_GROUP );
		$this->sut->clear_group( self::OTHER_CACHE_GROUP );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->sut->clear_group( self::CACHE_GROUP );
		$this->sut->clear_group( self::OTHER_CACHE_GROUP );

		parent::tearDown();
	}

	/**
	 * @testdox Get and set preserve falsey values.
	 *
	 * @dataProvider falsey_values_provider
	 *
	 * @param mixed $value The value to cache.
	 */
	public function test_get_and_set_preserve_falsey_values( $value ): void {
		$this->assertTrue( $this->sut->set( 'falsey', $value, self::CACHE_GROUP ) );

		$found  = false;
		$actual = $this->sut->get( 'falsey', self::CACHE_GROUP, $found );

		$this->assertTrue( $found, 'A cached falsey value should be reported as found.' );
		$this->assertSame( $value, $actual, 'The cached falsey value should be returned unchanged.' );

		$resolver_calls = 0;
		$resolver       = static function () use ( &$resolver_calls, $value ) {
			++$resolver_calls;
			return $value;
		};

		$first  = $this->sut->remember( 'remembered-falsey', self::CACHE_GROUP, $resolver );
		$second = $this->sut->remember( 'remembered-falsey', self::CACHE_GROUP, $resolver );

		$this->assertSame( $value, $first, 'Remember should return the resolved falsey value.' );
		$this->assertSame( $value, $second, 'Remember should return the cached falsey value.' );
		$this->assertSame( 1, $resolver_calls, 'Remember should not resolve a cached falsey value again.' );
	}

	/**
	 * Falsey cache values.
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function falsey_values_provider(): array {
		return array(
			'false'        => array( false ),
			'null'         => array( null ),
			'zero'         => array( 0 ),
			'empty string' => array( '' ),
			'empty array'  => array( array() ),
		);
	}

	/**
	 * @testdox Remember resolves a value once and returns it for later reads.
	 */
	public function test_remember_resolves_value_once(): void {
		$resolver_calls = 0;
		$resolver       = static function () use ( &$resolver_calls ): array {
			++$resolver_calls;
			return array( 'provider' => 'example' );
		};

		$first  = $this->sut->remember( 'providers', self::CACHE_GROUP, $resolver );
		$second = $this->sut->remember( 'providers', self::CACHE_GROUP, $resolver );

		$this->assertSame( $first, $second, 'Remember should return the cached value.' );
		$this->assertSame( 1, $resolver_calls, 'Remember should call the resolver once.' );
	}

	/**
	 * @testdox Remember retries a resolver after an exception.
	 */
	public function test_remember_does_not_cache_exceptions(): void {
		$resolver_calls = 0;

		try {
			$this->sut->remember(
				'unstable',
				self::CACHE_GROUP,
				static function () use ( &$resolver_calls ): array {
					++$resolver_calls;
					throw new RuntimeException( 'Unable to resolve.' );
				}
			);
			$this->fail( 'The resolver exception should be rethrown.' );
		} catch ( RuntimeException $error ) {
			$this->assertSame( 'Unable to resolve.', $error->getMessage() );
		}

		$result = $this->sut->remember(
			'unstable',
			self::CACHE_GROUP,
			static function () use ( &$resolver_calls ): array {
				++$resolver_calls;
				return array( 'resolved' => true );
			}
		);

		$this->assertSame( array( 'resolved' => true ), $result );
		$this->assertSame( 2, $resolver_calls, 'The failed resolution should not be cached.' );
	}

	/**
	 * @testdox Clear invalidates one group without changing another group.
	 */
	public function test_clear_group_only_invalidates_target_group(): void {
		$this->sut->set( 'shared-key', 'first', self::CACHE_GROUP );
		$this->sut->set( 'shared-key', 'second', self::OTHER_CACHE_GROUP );

		$this->assertTrue( $this->sut->clear_group( self::CACHE_GROUP ) );

		$found = true;
		$this->assertFalse( $this->sut->get( 'shared-key', self::CACHE_GROUP, $found ) );
		$this->assertFalse( $found, 'The cleared group should no longer contain the key.' );

		$other_found = false;
		$this->assertSame( 'second', $this->sut->get( 'shared-key', self::OTHER_CACHE_GROUP, $other_found ) );
		$this->assertTrue( $other_found, 'Clearing one group should not invalidate another group.' );
	}

	/**
	 * @testdox Delete removes one key without changing other keys.
	 */
	public function test_delete_only_removes_target_key(): void {
		$this->sut->set( 'first', 'one', self::CACHE_GROUP );
		$this->sut->set( 'second', 'two', self::CACHE_GROUP );

		$this->assertTrue( $this->sut->delete( 'first', self::CACHE_GROUP ) );

		$first_found = true;
		$this->assertFalse( $this->sut->get( 'first', self::CACHE_GROUP, $first_found ) );
		$this->assertFalse( $first_found, 'The deleted key should not be found.' );

		$second_found = false;
		$this->assertSame( 'two', $this->sut->get( 'second', self::CACHE_GROUP, $second_found ) );
		$this->assertTrue( $second_found, 'Deleting one key should not change another key.' );
	}
}
