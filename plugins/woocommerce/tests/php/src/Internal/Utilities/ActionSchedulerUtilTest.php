<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the Internal\Utilities\ActionSchedulerUtil class.
 */
class ActionSchedulerUtilTest extends WC_Unit_Test_Case {

	private const HOOK  = 'woocommerce_action_scheduler_util_test';
	private const GROUP = 'woocommerce_action_scheduler_util_test_group';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( self::HOOK );
		parent::tearDown();
	}

	/**
	 * @testdox `has_scheduled_action` should return false when no matching action exists.
	 */
	public function test_returns_false_when_nothing_is_scheduled(): void {
		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK ),
			'An unscheduled hook should not be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return true for a pending action.
	 */
	public function test_returns_true_for_a_pending_action(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'A pending action should be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return true for an in-progress action.
	 */
	public function test_returns_true_for_an_in_progress_action(): void {
		$action_id = as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );
		\ActionScheduler::store()->log_execution( $action_id );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'An in-progress action should be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false when only the group differs.
	 */
	public function test_returns_false_when_the_group_does_not_match(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), 'some_other_group' ),
			'An action in a different group should not match'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false when only the args differ.
	 */
	public function test_returns_false_when_the_args_do_not_match(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array( 'b' ), self::GROUP ),
			'An action scheduled with different args should not match'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should treat null args as a wildcard.
	 */
	public function test_null_args_match_any_args(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, null, self::GROUP ),
			'Null args should match an action scheduled with any args'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false once the action has been unscheduled.
	 */
	public function test_returns_false_after_the_action_is_unscheduled(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );
		as_unschedule_all_actions( self::HOOK, array(), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'An unscheduled action should no longer be reported as scheduled'
		);
	}

	/**
	 * @testdox `can_check_scheduled_actions` should be true when Action Scheduler is loaded.
	 */
	public function test_can_check_scheduled_actions_when_action_scheduler_is_loaded(): void {
		$this->assertTrue(
			ActionSchedulerUtil::can_check_scheduled_actions(),
			'Action Scheduler is loaded in the test suite, so scheduled-action checks should be possible'
		);
	}

	/**
	 * The helper falls back to `as_next_scheduled_action` when `as_has_scheduled_action` is missing.
	 * Neither that branch nor the false branch of `can_check_scheduled_actions` can be exercised here:
	 * Action Scheduler is always fully loaded in the test suite, and the function-mocking seam
	 * (CodeHacker) only rewrites files under `includes/`, not `src/`. So pin the property the fallback
	 * relies on instead: with the bundled Action Scheduler the two functions answer identically, so
	 * routing a call site through the fallback is a no-op for every store not running an ancient copy.
	 *
	 * @testdox The `as_next_scheduled_action` fallback agrees with `as_has_scheduled_action`.
	 * @dataProvider provider_fallback_equivalence
	 *
	 * @param array|null $scheduled_args Args to schedule the action with, or null to schedule nothing.
	 * @param array|null $queried_args   Args to query with.
	 * @param bool       $expected       Expected result.
	 */
	public function test_fallback_is_equivalent_to_the_preferred_function( ?array $scheduled_args, ?array $queried_args, bool $expected ): void {
		if ( null !== $scheduled_args ) {
			as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, $scheduled_args, self::GROUP );
		}

		$this->assertSame(
			$expected,
			(bool) as_has_scheduled_action( self::HOOK, $queried_args, self::GROUP ),
			'as_has_scheduled_action should report the expected result'
		);
		$this->assertSame(
			$expected,
			(bool) as_next_scheduled_action( self::HOOK, $queried_args, self::GROUP ),
			'The as_next_scheduled_action fallback should report the same result'
		);
	}

	/**
	 * Data provider for test_fallback_is_equivalent_to_the_preferred_function.
	 *
	 * @return array
	 */
	public function provider_fallback_equivalence(): array {
		return array(
			'nothing scheduled'     => array( null, array(), false ),
			'exact args match'      => array( array( 'a' ), array( 'a' ), true ),
			'args mismatch'         => array( array( 'a' ), array( 'b' ), false ),
			'null args as wildcard' => array( array( 'a' ), null, true ),
		);
	}
}
