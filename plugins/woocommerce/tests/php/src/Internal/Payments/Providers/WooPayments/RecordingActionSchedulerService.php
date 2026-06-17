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
	 * @var array<int,array{hook:string,args:array<string,mixed>,timestamp?:int}>
	 */
	public array $scheduled_jobs = array();

	/**
	 * Record a scheduled job.
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $args Action args.
	 * @param int|null            $timestamp Optional scheduled timestamp.
	 */
	public function schedule_job( string $hook, array $args = array(), ?int $timestamp = null ): void {
		$job = array(
			'hook' => $hook,
			'args' => $args,
		);

		if ( null !== $timestamp ) {
			$job['timestamp'] = $timestamp;
		}

		$this->scheduled_jobs[] = $job;
	}
}
