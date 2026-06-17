<?php
/**
 * WooPaymentsActionSchedulerService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * WooPayments-compatible Action Scheduler wrapper.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsActionSchedulerService {

	/**
	 * Canonical WooPayments Action Scheduler group.
	 *
	 * @var string
	 */
	const GROUP_ID = 'woocommerce_payments';

	/**
	 * Schedule a single action unless the same hook/args/group is already pending.
	 *
	 * @since 11.0.0
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $args      Action args.
	 * @param int|null            $timestamp Scheduled timestamp. Defaults to now.
	 */
	public function schedule_job( string $hook, array $args = array(), ?int $timestamp = null ): void {
		if ( $this->has_pending_action( $hook, $args ) ) {
			return;
		}

		as_schedule_single_action( $timestamp ?? time(), $hook, $args, self::GROUP_ID );
	}

	/**
	 * Tell whether the same hook/args/group is already pending.
	 *
	 * This intentionally excludes running actions so a currently executing
	 * failed-event fetch can schedule the next page when the provider reports
	 * more events.
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $args Action args.
	 * @return bool
	 */
	private function has_pending_action( string $hook, array $args ): bool {
		$actions = as_get_scheduled_actions(
			array(
				'hook'     => $hook,
				'args'     => $args,
				'group'    => self::GROUP_ID,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
				'orderby'  => 'none',
			)
		);

		return ! empty( $actions );
	}
}
