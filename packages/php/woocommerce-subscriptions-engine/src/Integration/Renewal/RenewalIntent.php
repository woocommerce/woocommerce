<?php
/**
 * RenewalIntent - the seam between selection and processing: a resolved decision to bill a
 * specific cycle of a specific contract. A trigger (the batch {@see RenewalDispatcher} or the
 * admin `renew_now()` path) builds one from the cycle {@see RenewalSelector} chose;
 * {@see RenewalEngine::process()} consumes it.
 *
 * Carrying the target as an explicit value keeps `process()` free of any "which cycle"
 * logic - it bills exactly the count it is handed, so a future trigger (an admin retry, a
 * customer early renewal) is just a different trigger building a different intent over
 * the same processing path.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

defined( 'ABSPATH' ) || exit;

/**
 * A resolved decision to bill one cycle of one contract.
 */
final class RenewalIntent {

	/**
	 * Contract to bill.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * The chargeable cycle count to bill.
	 *
	 * @var int
	 */
	private $cycle_count;

	/**
	 * Build an intent to bill `$cycle_count` of `$contract_id`.
	 *
	 * @param int $contract_id Contract to bill.
	 * @param int $cycle_count The chargeable cycle count to bill.
	 */
	public function __construct( int $contract_id, int $cycle_count ) {
		$this->contract_id = $contract_id;
		$this->cycle_count = $cycle_count;
	}

	/**
	 * Contract to bill.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * The chargeable cycle count to bill.
	 */
	public function get_cycle_count(): int {
		return $this->cycle_count;
	}
}
