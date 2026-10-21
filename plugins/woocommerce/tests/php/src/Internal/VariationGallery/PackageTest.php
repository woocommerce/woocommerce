<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\Migration;
use Automattic\WooCommerce\Internal\VariationGallery\Package;

/**
 * Tests for the variation gallery package bootstrap.
 */
class PackageTest extends \WC_Unit_Test_Case {

	/**
	 * Reset migration-related state before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_migration_state();
	}

	/**
	 * Reset migration-related state between tests so action queue and
	 * completion option don't leak across cases.
	 */
	public function tearDown(): void {
		$this->reset_migration_state();

		parent::tearDown();
	}

	/**
	 * Reset migration-related options and scheduled actions.
	 */
	private function reset_migration_state(): void {
		WC()->queue()->cancel_all(
			'woocommerce_run_update_callback',
			$this->get_migration_action_args(),
			'woocommerce-db-updates'
		);
		WC()->queue()->cancel_all(
			'woocommerce_run_update_callback',
			$this->get_unrelated_update_action_args(),
			'woocommerce-db-updates'
		);
		delete_option( Migration::COMPLETED_OPTION );
		delete_option( Package::ENABLE_OPTION_NAME );
	}

	/**
	 * @testdox is_enabled returns true now that the variation gallery is fully rolled out.
	 */
	public function test_is_enabled_returns_true(): void {
		$this->assertTrue( Package::is_enabled() );
	}

	/**
	 * @testdox is_enabled returns true for a former explicit opt-out.
	 */
	public function test_is_enabled_returns_true_for_former_explicit_opt_out(): void {
		update_option( Package::ENABLE_OPTION_NAME, 'no' );

		$this->assertTrue( Package::is_enabled() );
	}

	/**
	 * @testdox maybe_schedule_migration queues the migration.
	 */
	public function test_maybe_schedule_migration_queues_the_migration(): void {
		Package::maybe_schedule_migration();

		$this->assertNotNull(
			WC()->queue()->get_next(
				'woocommerce_run_update_callback',
				$this->get_migration_action_args(),
				'woocommerce-db-updates'
			)
		);
	}

	/**
	 * @testdox maybe_schedule_migration does not duplicate the migration when other DB updates are pending.
	 */
	public function test_maybe_schedule_migration_does_not_duplicate_existing_migration(): void {
		WC()->queue()->add(
			'woocommerce_run_update_callback',
			$this->get_unrelated_update_action_args(),
			'woocommerce-db-updates'
		);
		WC()->queue()->add(
			'woocommerce_run_update_callback',
			$this->get_migration_action_args(),
			'woocommerce-db-updates'
		);

		Package::maybe_schedule_migration();

		$scheduled = WC()->queue()->search(
			array(
				'hook'     => 'woocommerce_run_update_callback',
				'args'     => $this->get_migration_action_args(),
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
				'group'    => 'woocommerce-db-updates',
			),
			'ids'
		);

		$this->assertCount( 1, $scheduled );
	}

	/**
	 * @testdox maybe_schedule_migration does not queue after completion.
	 */
	public function test_maybe_schedule_migration_does_not_queue_after_completion(): void {
		update_option( Migration::COMPLETED_OPTION, time() );

		Package::maybe_schedule_migration();

		$this->assertNull(
			WC()->queue()->get_next(
				'woocommerce_run_update_callback',
				$this->get_migration_action_args(),
				'woocommerce-db-updates'
			)
		);
	}

	/**
	 * The action args expected for the migration callback.
	 *
	 * @return array<string, mixed>
	 */
	private function get_migration_action_args(): array {
		return array(
			'update_callback' => array( Migration::class, 'run' ),
		);
	}

	/**
	 * Stand-in action args for an unrelated DB update callback, used to
	 * verify the migration scheduler doesn't confuse other pending actions
	 * for its own.
	 *
	 * @return array<string, mixed>
	 */
	private function get_unrelated_update_action_args(): array {
		return array(
			'update_callback' => 'some_other_update_callback',
		);
	}
}
