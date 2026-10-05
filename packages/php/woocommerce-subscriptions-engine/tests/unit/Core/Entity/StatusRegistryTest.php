<?php
/**
 * Unit tests for the WordPress-free StatusRegistry.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry
 */
class StatusRegistryTest extends TestCase {

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	public function test_all_returns_exactly_the_engine_defaults_with_nothing_registered(): void {
		$this->assertSame(
			array( 'active', 'on-hold', 'pending-cancellation', 'cancelled', 'expired' ),
			StatusRegistry::get_all( StatusRegistry::KIND_CONTRACT )
		);
		$this->assertSame(
			array( 'pending', 'processing', 'billed', 'failed', 'cancelled' ),
			StatusRegistry::get_all( StatusRegistry::KIND_CYCLE )
		);
	}

	public function test_registered_statuses_append_after_the_defaults_in_registration_order(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'awaiting-shipment' );

		$this->assertSame(
			array_merge( ContractStatus::get_defaults(), array( 'paused-by-merchant', 'awaiting-shipment' ) ),
			StatusRegistry::get_all( StatusRegistry::KIND_CONTRACT )
		);
	}

	public function test_is_registered_covers_defaults_and_registrations_only(): void {
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );

		$this->assertTrue( StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, CycleStatus::BILLED ) );
		$this->assertTrue( StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, 'disputed' ) );
		$this->assertFalse( StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, 'never-registered' ) );
	}

	public function test_re_registering_a_default_or_a_registered_slug_is_a_no_op(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, ContractStatus::ACTIVE );
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );

		$this->assertSame(
			array_merge( ContractStatus::get_defaults(), array( 'paused-by-merchant' ) ),
			StatusRegistry::get_all( StatusRegistry::KIND_CONTRACT )
		);
	}

	public function test_kinds_are_independent(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );

		$this->assertTrue( StatusRegistry::is_registered( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' ) );
		$this->assertFalse( StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, 'paused-by-merchant' ) );
		$this->assertSame( CycleStatus::get_defaults(), StatusRegistry::get_all( StatusRegistry::KIND_CYCLE ) );
	}

	/**
	 * @dataProvider provide_invalid_slugs
	 *
	 * @param string $slug Invalid slug.
	 */
	public function test_register_rejects_an_invalid_slug( string $slug ): void {
		$this->expectException( InvalidArgumentException::class );

		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, $slug );
	}

	/**
	 * @dataProvider provide_invalid_slugs
	 *
	 * @param string $slug Invalid slug.
	 */
	public function test_is_valid_slug_rejects_an_invalid_slug( string $slug ): void {
		$this->assertFalse( StatusRegistry::is_valid_slug( $slug ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_invalid_slugs(): array {
		return array(
			'empty'           => array( '' ),
			'uppercase'       => array( 'Active' ),
			'underscore'      => array( 'on_hold' ),
			'leading hyphen'  => array( '-x' ),
			'trailing hyphen' => array( 'x-' ),
			'double hyphen'   => array( 'a--b' ),
			'space'           => array( 'with space' ),
			'markup'          => array( '<b>' ),
			'over 20 chars'   => array( str_repeat( 'a', 21 ) ),
			'trailing LF'     => array( "paused\n" ),
			'trailing CRLF'   => array( "paused\r\n" ),
		);
	}

	/**
	 * @dataProvider provide_valid_edge_slugs
	 *
	 * @param string $slug Valid slug.
	 */
	public function test_register_accepts_a_valid_edge_case_slug( string $slug ): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, $slug );

		$this->assertTrue( StatusRegistry::is_valid_slug( $slug ) );
		$this->assertTrue( StatusRegistry::is_registered( StatusRegistry::KIND_CONTRACT, $slug ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_valid_edge_slugs(): array {
		return array(
			'single char'       => array( 'a' ),
			'digits and hyphen' => array( 'a1-b2' ),
			'exactly 20 chars'  => array( str_repeat( 'a', 20 ) ),
		);
	}

	public function test_register_rejects_an_unknown_kind(): void {
		$this->expectException( InvalidArgumentException::class );

		StatusRegistry::register( 'plan', 'draft' );
	}

	public function test_all_rejects_an_unknown_kind(): void {
		$this->expectException( InvalidArgumentException::class );

		StatusRegistry::get_all( 'plan' );
	}

	public function test_is_registered_rejects_an_unknown_kind(): void {
		$this->expectException( InvalidArgumentException::class );

		StatusRegistry::is_registered( 'plan', 'draft' );
	}

	public function test_reset_clears_registrations_but_keeps_the_defaults(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );

		StatusRegistry::reset();

		$this->assertSame( ContractStatus::get_defaults(), StatusRegistry::get_all( StatusRegistry::KIND_CONTRACT ) );
		$this->assertSame( CycleStatus::get_defaults(), StatusRegistry::get_all( StatusRegistry::KIND_CYCLE ) );
		$this->assertFalse( StatusRegistry::is_registered( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' ) );
	}
}
