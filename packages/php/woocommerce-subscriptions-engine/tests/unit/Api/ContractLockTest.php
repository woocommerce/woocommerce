<?php
/**
 * Unit tests for the opaque ContractLock and the lock exceptions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\DuplicateContractException;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\DuplicateContractException
 */
class ContractLockTest extends TestCase {

	private const TOKEN = '0123456789abcdef0123456789abcdef';

	private function issue( int $trust_seconds = ContractLock::TTL_SECONDS ): ContractLock {
		return ContractLock::issue( 7, 'hold', self::TOKEN, $trust_seconds );
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
	 * @testdox A lock cannot be cloned.
	 */
	public function test_clone_throws(): void {
		$lock = $this->issue();

		$this->expectException( LogicException::class );

		$copy = clone $lock;
		unset( $copy );
	}

	/**
	 * @testdox A lock with a full TTL ahead is trusted; within the margin it is not.
	 */
	public function test_trust_follows_the_remaining_time(): void {
		$lock = $this->issue();
		$this->assertTrue( $lock->is_trusted() );

		$lock->trust_for( 60 );
		$this->assertFalse( $lock->is_trusted() );

		$lock->trust_for( 0 );
		$this->assertFalse( $lock->is_trusted() );

		$lock->trust_for( ContractLock::TTL_SECONDS );
		$this->assertTrue( $lock->is_trusted() );
	}

	/**
	 * @testdox A released lock is never trusted, even after a trust refresh.
	 */
	public function test_a_released_lock_is_never_trusted(): void {
		$lock = $this->issue();
		$lock->mark_released();

		$this->assertTrue( $lock->is_released() );
		$this->assertFalse( $lock->is_trusted() );

		$lock->trust_for( ContractLock::TTL_SECONDS );
		$this->assertFalse( $lock->is_trusted() );
	}

	/**
	 * @testdox The extend allowance is recorded once used.
	 */
	public function test_extend_allowance_flag(): void {
		$lock = $this->issue();
		$this->assertFalse( $lock->has_extended() );

		$lock->mark_extended();
		$this->assertTrue( $lock->has_extended() );
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

	/**
	 * @testdox DuplicateContractException carries the existing contract id and the key.
	 */
	public function test_duplicate_exception_exposes_the_existing_contract(): void {
		$exception = new DuplicateContractException( 42, 'order:9' );

		$this->assertSame( 42, $exception->get_existing_contract_id() );
		$this->assertSame( 'order:9', $exception->get_creation_key() );
		$this->assertStringContainsString( '42', $exception->getMessage() );
	}
}
