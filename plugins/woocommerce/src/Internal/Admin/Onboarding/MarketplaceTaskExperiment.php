<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WooCommerce\Admin\Experimental_Abtest;

/**
 * ExPlat A/B test for the title and position of the Marketplace ("extend-store") task in the "extended" task list.
 *
 * @since 11.3.0
 */
final class MarketplaceTaskExperiment {
	/**
	 * ExPlat experiment name.
	 */
	public const EXPERIMENT_NAME = 'woocommerce_marketplace_task_202611';

	/**
	 * Current title and position.
	 */
	public const CONTROL = 'control';

	/**
	 * Title "Add payments, shipping and marketing extensions", current position.
	 */
	public const COPY_PAYMENTS_SHIPPING_MARKETING = 'copy_payments_shipping_marketing';

	/**
	 * Title "Browse free and paid extensions", current position.
	 */
	public const COPY_FREE_AND_PAID = 'copy_free_and_paid';

	/**
	 * Current title, task moved to the top of the "extended" list.
	 */
	public const FIRST_POSITION = 'first_position';

	/**
	 * ID of the task under test.
	 */
	private const TASK_ID = 'extend-store';

	/**
	 * Transient set after a failed ExPlat request, so the next requests skip the call for a while.
	 */
	private const BACKOFF_TRANSIENT = 'woocommerce_marketplace_task_experiment_backoff';

	/**
	 * 2027-03-01 00:00 UTC. After this, ExPlat is never asked, so stores that don't update stop calling it.
	 */
	private const END_TIMESTAMP = 1803859200;

	/**
	 * Variation fetched from ExPlat (or its transient) during the current request.
	 *
	 * @var string|null
	 */
	private ?string $variation = null;

	/**
	 * Get the variation for this request, or control when ExPlat shouldn't be asked.
	 *
	 * @since 11.3.0
	 *
	 * @param Task $task The Marketplace task.
	 * @return string One of the variation constants.
	 */
	public function get_variation( Task $task ): string {
		if ( null === $this->variation && $this->should_request_assignment( $task ) ) {
			$this->variation = $this->request_variation();
		}

		return $this->variation ?? self::CONTROL;
	}

	/**
	 * Move the Marketplace task to the top of the "extended" list when the site is in the first_position variation.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $task_list Task list from TaskLists, which external code can filter, so it may not be a TaskList.
	 * @return void
	 */
	public function maybe_move_task_first( $task_list ): void {
		if ( ! $task_list instanceof TaskList || 'extended' !== $task_list->get_list_id() ) {
			return;
		}

		$task = $task_list->get_task( self::TASK_ID );
		if ( ! $task instanceof Task || self::FIRST_POSITION !== $this->get_variation( $task ) ) {
			return;
		}

		$other_tasks      = array_filter(
			$task_list->tasks,
			fn( $other_task ) => $other_task !== $task
		);
		$task_list->tasks = array_merge( array( $task ), array_values( $other_tasks ) );
	}

	/**
	 * Whether to ask ExPlat for an assignment, which logs an exposure.
	 *
	 * @param Task $task The Marketplace task.
	 * @return bool
	 */
	private function should_request_assignment( Task $task ): bool {
		if ( $this->has_ended() || 'yes' !== get_option( 'woocommerce_allow_tracking' ) || $task->is_dismissed() ) {
			return false;
		}

		// Without a tk_ai cookie Experimental_Abtest fails before making a request, so skip it here rather than back off.
		if ( '' === $this->get_anon_id() || get_transient( self::BACKOFF_TRANSIENT ) ) {
			return false;
		}

		if ( $task->is_complete() ) {
			return false;
		}

		$task_list = TaskLists::get_list( $task->get_parent_id() );
		return ! $task_list || $task_list->is_visible();
	}

	/**
	 * Whether the end date has passed. Reads the time through LegacyProxy so tests can change it.
	 *
	 * @return bool
	 */
	private function has_ended(): bool {
		return wc_get_container()->get( LegacyProxy::class )->call_function( 'time' ) >= self::END_TIMESTAMP;
	}

	/**
	 * Get the Tracks anonymous ID from the tk_ai cookie.
	 *
	 * @return string Empty when the cookie is not set.
	 */
	private function get_anon_id(): string {
		return isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
	}

	/**
	 * Fetch the assignment from ExPlat (or its transient cache), mapping anything unexpected to control.
	 *
	 * @return string
	 */
	private function request_variation(): string {
		$abtest = new Experimental_Abtest( $this->get_anon_id(), 'woocommerce', true );

		try {
			$variation = $abtest->get_variation( self::EXPERIMENT_NAME );
		} catch ( \Exception $e ) {
			// Outside production, get_variation() throws when the request fails instead of returning control.
			$variation = self::CONTROL;
		}

		// Experimental_Abtest only caches successful responses, so without a backoff every page load would retry the blocking request.
		if ( empty( get_transient( 'abtest_variation_' . self::EXPERIMENT_NAME ) ) ) {
			set_transient( self::BACKOFF_TRANSIENT, 1, HOUR_IN_SECONDS );
		}

		$known = array( self::COPY_PAYMENTS_SHIPPING_MARKETING, self::COPY_FREE_AND_PAID, self::FIRST_POSITION );

		return in_array( $variation, $known, true ) ? $variation : self::CONTROL;
	}
}
