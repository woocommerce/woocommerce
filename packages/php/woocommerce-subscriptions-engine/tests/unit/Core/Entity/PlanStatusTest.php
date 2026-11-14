<?php
/**
 * Unit tests for the registry-backed PlanStatus helpers.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus
 */
class PlanStatusTest extends TestCase {

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	public function test_defaults_lists_the_two_engine_slugs(): void {
		$this->assertSame( array( 'active', 'archived' ), PlanStatus::get_defaults() );
	}

	public function test_all_equals_the_defaults_with_nothing_registered(): void {
		$this->assertSame( PlanStatus::get_defaults(), PlanStatus::get_all() );
	}

	public function test_defaults_are_registered(): void {
		$this->assertTrue( PlanStatus::is_registered( PlanStatus::ACTIVE ) );
		$this->assertTrue( PlanStatus::is_registered( PlanStatus::ARCHIVED ) );
		$this->assertFalse( PlanStatus::is_registered( 'nonsense' ) );
	}

	public function test_an_extension_registered_status_is_listed_and_registered(): void {
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );

		$all = PlanStatus::get_all();

		$this->assertSame( 'seasonal', end( $all ) );
		$this->assertTrue( PlanStatus::is_registered( 'seasonal' ) );
	}

	public function test_a_contract_registration_does_not_make_a_plan_status_registered(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );

		$this->assertFalse( PlanStatus::is_registered( 'paused-by-merchant' ) );
	}

	public function test_is_valid_checks_the_slug_format_only(): void {
		$this->assertTrue( PlanStatus::is_valid( PlanStatus::ARCHIVED ) );
		$this->assertTrue( PlanStatus::is_valid( 'legacy-x' ), 'Well-formed but unregistered.' );
		$this->assertFalse( PlanStatus::is_registered( 'legacy-x' ) );
		$this->assertFalse( PlanStatus::is_valid( 'Archived' ) );
		$this->assertFalse( PlanStatus::is_valid( str_repeat( 'a', 21 ) ) );
	}
}
