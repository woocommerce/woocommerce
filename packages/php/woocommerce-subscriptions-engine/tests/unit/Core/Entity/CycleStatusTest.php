<?php
/**
 * Unit tests for the registry-backed CycleStatus value object.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\AssertsInvalidInput;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus
 */
class CycleStatusTest extends TestCase {

	use AssertsInvalidInput;

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	public function test_get_all_returns_exactly_the_known_statuses(): void {
		$this->assertSame(
			array(
				CycleStatus::PENDING,
				CycleStatus::PROCESSING,
				CycleStatus::BILLED,
				CycleStatus::FAILED,
				CycleStatus::CANCELLED,
			),
			CycleStatus::get_all()
		);
	}

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( CycleStatus::is_registered( CycleStatus::PENDING ) );
		$this->assertTrue( CycleStatus::is_registered( CycleStatus::PROCESSING ) );
		$this->assertTrue( CycleStatus::is_registered( CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_registered( CycleStatus::FAILED ) );
		$this->assertTrue( CycleStatus::is_registered( CycleStatus::CANCELLED ) );
		$this->assertFalse( CycleStatus::is_registered( 'nonsense' ) );
	}

	public function test_constructor_wraps_a_default_status(): void {
		$this->assertSame( CycleStatus::PENDING, ( new CycleStatus( CycleStatus::PENDING ) )->get_value() );
	}

	/**
	 * Registration is checked where a cycle status is written, not when the value is built,
	 * so a stored status an unregistered extension wrote can still be loaded.
	 */
	public function test_constructor_wraps_a_well_formed_unregistered_status(): void {
		$this->assertSame( 'legacy-x', ( new CycleStatus( 'legacy-x' ) )->get_value() );
	}

	/**
	 * @dataProvider malformed_slugs
	 *
	 * @param string $slug Malformed slug.
	 */
	public function test_constructor_rejects_a_malformed_slug( string $slug ): void {
		$this->assert_invalid_input(
			static function () use ( $slug ): void {
				new CycleStatus( $slug );
			},
			ValidationErrorCode::INVALID_VALUE,
			'status'
		);
	}

	/**
	 * Strings that are not well-formed status slugs.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function malformed_slugs(): array {
		return array(
			'empty'            => array( '' ),
			'uppercase'        => array( 'Pending' ),
			'double hyphen'    => array( 'on--hold' ),
			'trailing newline' => array( "pending\n" ),
			'markup'           => array( '<b>x</b>' ),
			'over 20 chars'    => array( str_repeat( 'a', 21 ) ),
		);
	}

	public function test_is_valid_checks_the_slug_format_only(): void {
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::PENDING ) );
		$this->assertTrue( CycleStatus::is_valid( 'legacy-x' ), 'Well-formed but unregistered.' );
		$this->assertFalse( CycleStatus::is_registered( 'legacy-x' ) );
	}

	/**
	 * @dataProvider malformed_slugs
	 *
	 * @param string $slug Malformed slug.
	 */
	public function test_is_valid_rejects_a_malformed_slug( string $slug ): void {
		$this->assertFalse( CycleStatus::is_valid( $slug ) );
	}

	public function test_is_registered_is_scoped_to_cycle_registrations(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'disputed' );
		$this->assertFalse( CycleStatus::is_registered( 'disputed' ) );

		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );
		$this->assertTrue( CycleStatus::is_registered( 'disputed' ) );
	}

	public function test_the_retired_transition_api_and_factories_are_gone(): void {
		foreach ( array( 'is_transition_allowed', 'can_transition', 'assert_transition_allowed', 'can_transition_to', 'transition_to', 'from', 'stored', 'pending', 'processing', 'billed', 'failed', 'cancelled', 'all', 'defaults' ) as $method ) {
			$this->assertFalse( method_exists( CycleStatus::class, $method ), "CycleStatus::{$method}() should not exist." );
		}
	}
}
