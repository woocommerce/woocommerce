<?php
/**
 * Unit tests for the registry-backed ContractStatus helpers.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus
 */
class ContractStatusTest extends TestCase {

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( ContractStatus::is_registered( ContractStatus::ACTIVE ) );
		$this->assertFalse( ContractStatus::is_registered( 'nonsense' ) );
	}

	public function test_defaults_lists_the_five_engine_slugs(): void {
		$this->assertSame(
			array( 'active', 'on-hold', 'pending-cancellation', 'cancelled', 'expired' ),
			ContractStatus::get_defaults()
		);
	}

	public function test_all_equals_the_defaults_with_nothing_registered(): void {
		$this->assertSame( ContractStatus::get_defaults(), ContractStatus::get_all() );
	}

	public function test_an_extension_registered_status_is_listed_and_valid(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );

		$all = ContractStatus::get_all();

		$this->assertSame( 'paused-by-merchant', end( $all ) );
		$this->assertTrue( ContractStatus::is_registered( 'paused-by-merchant' ) );
	}

	public function test_a_cycle_registration_does_not_make_a_contract_status_valid(): void {
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );

		$this->assertFalse( ContractStatus::is_registered( 'disputed' ) );
	}

	public function test_the_retired_transition_api_is_gone(): void {
		foreach ( array( 'is_transition_allowed', 'can_transition', 'assert_transition_allowed', 'is_terminal' ) as $method ) {
			$this->assertFalse( method_exists( ContractStatus::class, $method ), "ContractStatus::{$method}() should not exist." );
		}
	}

	public function test_is_valid_checks_the_slug_format_only(): void {
		$this->assertTrue( ContractStatus::is_valid( ContractStatus::PENDING_CANCELLATION ) );
		$this->assertTrue( ContractStatus::is_valid( 'legacy-x' ), 'Well-formed but unregistered.' );
		$this->assertFalse( ContractStatus::is_registered( 'legacy-x' ) );
		$this->assertFalse( ContractStatus::is_valid( 'On Hold' ) );
		$this->assertFalse( ContractStatus::is_valid( "active\n" ) );
		$this->assertFalse( ContractStatus::is_valid( str_repeat( 'a', 21 ) ) );
	}
}
