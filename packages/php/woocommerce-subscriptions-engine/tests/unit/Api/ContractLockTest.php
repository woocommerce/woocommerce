<?php
/**
 * Unit tests for the opaque ContractLock and the lock exceptions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractLockState;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractLockState
 */
class ContractLockTest extends TestCase {

	private const TOKEN = '0123456789abcdef0123456789abcdef';

	/**
	 * A lock built in the repository's scope, as the engine builds it, so no notice is raised
	 * (unit tests load no WordPress).
	 */
	private function issue(): ContractLock {
		$factory = Closure::bind(
			static function ( string $token ): ContractLock {
				return new ContractLock( 7, 'hold', $token );
			},
			null,
			ContractRepository::class
		);

		return $factory( self::TOKEN );
	}

	/**
	 * Seconds of trust left on a lock state.
	 *
	 * @param ContractLockState $state The state.
	 */
	private function trust_left( ContractLockState $state ): float {
		$property = new ReflectionProperty( ContractLockState::class, 'trusted_until' );
		$property->setAccessible( true );
		$until = $property->getValue( $state );
		$this->assertIsFloat( $until );
		$now = hrtime();
		$this->assertIsArray( $now );

		return $until - ( $now[0] + $now[1] / 1e9 );
	}

	/**
	 * @testdox The only public methods are the constructor, the accessors, the engine-only state, release, and the methods that refuse cloning and serialization.
	 */
	public function test_public_surface(): void {
		$names = array();
		foreach ( ( new ReflectionClass( ContractLock::class ) )->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			$names[] = $method->getName();
		}
		sort( $names );

		$this->assertSame(
			array( '__clone', '__construct', '__debugInfo', '__serialize', '__unserialize', 'get_contract_id', 'get_operation', 'get_state', 'release', 'serialize', 'unserialize' ),
			$names
		);
	}

	/**
	 * @testdox The lock exposes its contract and operation and no public method returns any other string.
	 */
	public function test_no_public_method_returns_the_token(): void {
		$lock = $this->issue();

		$this->assertSame( 7, $lock->get_contract_id() );
		$this->assertSame( 'hold', $lock->get_operation() );

		$string_methods = array();
		foreach ( ( new ReflectionClass( ContractLock::class ) )->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			$type = $method->getReturnType();
			if ( $type instanceof ReflectionNamedType && 'string' === $type->getName() ) {
				$string_methods[] = $method->getName();
			}
		}

		$this->assertSame( array( 'get_operation' ), $string_methods );
	}

	/**
	 * @testdox Debug output never carries the token.
	 */
	public function test_debug_output_omits_the_token(): void {
		$lock = $this->issue();

		ob_start();
		var_dump( $lock ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
		$dumped = (string) ob_get_clean();

		$this->assertStringNotContainsString( self::TOKEN, print_r( $lock, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertStringNotContainsString( self::TOKEN, $dumped );
		$this->assertStringContainsString( 'hold', $dumped );
	}

	/**
	 * @testdox A lock cannot be serialized.
	 */
	public function test_serialize_throws(): void {
		$this->expectException( LogicException::class );

		serialize( $this->issue() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	/**
	 * @testdox A crafted serialized lock cannot be unserialized.
	 */
	public function test_unserialize_of_a_crafted_payload_throws(): void {
		$class   = ContractLock::class;
		$payload = sprintf( 'O:%d:"%s":1:{s:11:"contract_id";i:7;}', strlen( $class ), $class );

		$this->expectException( LogicException::class );

		unserialize( $payload ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
	}

	/**
	 * @testdox A crafted legacy C: payload, which skips __unserialize(), cannot be unserialized either.
	 */
	public function test_unserialize_of_a_crafted_legacy_payload_throws(): void {
		$class   = ContractLock::class;
		$payload = sprintf( 'C:%d:"%s":0:{}', strlen( $class ), $class );

		$this->expectException( LogicException::class );

		unserialize( $payload ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
	}

	/**
	 * @testdox A lock cannot be cloned.
	 */
	public function test_clone_throws(): void {
		$lock = $this->issue();

		$this->expectException( LogicException::class );

		$copy = clone $lock;
		unset( $copy );
	}

	/**
	 * @testdox A stamp trusts the lock for at most 60 s, less when it ends sooner, and not at all with 60 s or less left.
	 */
	public function test_trust_window_follows_the_last_stamp(): void {
		$state = new ContractLockState( self::TOKEN );
		$this->assertTrue( $state->is_trusted() );
		$this->assertLessThanOrEqual( 60.0, $this->trust_left( $state ) );
		$this->assertGreaterThan( 59.0, $this->trust_left( $state ) );

		$state->stamp( 90 );
		$this->assertLessThanOrEqual( 30.0, $this->trust_left( $state ) );
		$this->assertTrue( $state->is_trusted() );

		$state->stamp( 60 );
		$this->assertFalse( $state->is_trusted() );

		$state->stamp( 0 );
		$this->assertFalse( $state->is_trusted() );

		$state->stamp( ContractLock::TTL_SECONDS );
		$this->assertLessThanOrEqual( 60.0, $this->trust_left( $state ) );
	}

	/**
	 * @testdox A lock is near expiry by the local estimate when 120 s or less remain.
	 */
	public function test_near_expiry_estimate(): void {
		$state = new ContractLockState( self::TOKEN );
		$this->assertFalse( $state->is_near_expiry() );

		$state->stamp( 120 );
		$this->assertTrue( $state->is_near_expiry() );

		$state->stamp( 200 );
		$this->assertFalse( $state->is_near_expiry() );
	}

	/**
	 * @testdox A released lock is never trusted, even after a stamp.
	 */
	public function test_a_released_lock_is_never_trusted(): void {
		$state = new ContractLockState( self::TOKEN );
		$state->mark_released();

		$this->assertTrue( $state->is_released() );
		$this->assertFalse( $state->is_trusted() );

		$state->stamp( ContractLock::TTL_SECONDS );
		$this->assertFalse( $state->is_trusted() );
	}

	/**
	 * @testdox The extend allowance is recorded once used.
	 */
	public function test_extend_allowance_flag(): void {
		$state = new ContractLockState( self::TOKEN );
		$this->assertFalse( $state->has_extended() );

		$state->mark_extended();
		$this->assertTrue( $state->has_extended() );
	}

	/**
	 * @testdox ContractLockedException carries the holder's label and expiry, never a token.
	 */
	public function test_locked_exception_exposes_the_holder(): void {
		$until     = new DateTimeImmutable( '2026-10-15 12:10:00', new DateTimeZone( 'UTC' ) );
		$exception = new ContractLockedException( 7, 'renewal', $until );

		$this->assertSame( 7, $exception->get_contract_id() );
		$this->assertSame( 'renewal', $exception->get_operation() );
		$this->assertSame( $until, $exception->get_locked_until() );
		$this->assertStringContainsString( 'renewal', $exception->getMessage() );
		$this->assertStringContainsString( '7', $exception->getMessage() );

		$unknown = new ContractLockedException( 7, null, null );
		$this->assertNull( $unknown->get_operation() );
		$this->assertNull( $unknown->get_locked_until() );
	}
}
