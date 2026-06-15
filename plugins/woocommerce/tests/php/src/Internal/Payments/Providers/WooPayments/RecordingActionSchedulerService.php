<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsActionSchedulerService;

/**
 * Recording scheduler test double.
 */
class RecordingActionSchedulerService extends WooPaymentsActionSchedulerService {

	/**
	 * Scheduled jobs.
	 *
	 * @var array<int,array{hook:string,args:array<string,mixed>}>
	 */
	public array $scheduled_jobs = array();

	/**
	 * Record a scheduled job.
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $args Action args.
	 */
	public function schedule_job( string $hook, array $args = array() ): void {
		$this->scheduled_jobs[] = array(
			'hook' => $hook,
			'args' => $args,
		);
	}
}
