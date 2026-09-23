<?php
/**
 * RenewalCandidate - a contract as a candidate for renewal: its id plus the head-cycle
 * fields the renewal selector reads to decide what (if anything) to bill. Produced by
 * {@see ContractRepository::find_due()} - which joins each due contract to its head cycle
 * so the scan can filter to actionable contracts (head billed and due, or head pending
 * with an expired lease) in SQL, keeping non-actionable rows out of the batch budget -
 * and by {@see self::from_cycle()} on the single-contract paths (scheduled or manual),
 * where the caller has already loaded the head.
 *
 * A lean read-model, not the full {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle}:
 * it carries only the head fields selection reads, so the scan does not hydrate snapshots
 * for a decision the money-path re-reads anyway.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;

defined( 'ABSPATH' ) || exit;

/**
 * A renewal candidate: a contract plus its head-cycle fields.
 */
final class RenewalCandidate {

	/**
	 * Contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Head cycle chargeable count, or null for a countless (corrupt) head.
	 *
	 * @var int|null
	 */
	private $head_count;

	/**
	 * Head cycle status string (a {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus} value).
	 *
	 * @var string
	 */
	private $head_status;

	/**
	 * Head cycle period end (GMT string) - the moment the next cycle becomes due.
	 *
	 * @var string
	 */
	private $head_ends_at_gmt;

	/**
	 * Build a candidate from a contract and its head-cycle fields.
	 *
	 * @param int      $contract_id      Contract id.
	 * @param int|null $head_count       Head chargeable count (null when the head has none).
	 * @param string   $head_status      Head status string.
	 * @param string   $head_ends_at_gmt Head period end (GMT string).
	 */
	public function __construct( int $contract_id, ?int $head_count, string $head_status, string $head_ends_at_gmt ) {
		$this->contract_id      = $contract_id;
		$this->head_count       = $head_count;
		$this->head_status      = $head_status;
		$this->head_ends_at_gmt = $head_ends_at_gmt;
	}

	/**
	 * Build from a hydrated cycle - the single-contract path, where the caller has already loaded
	 * the contract's head cycle (rather than joining it in the scan) and maps its fields in. The
	 * head semantics are the caller's to uphold; this only reads the cycle it is handed.
	 *
	 * @param Cycle $cycle The cycle to read (the contract's head, in the single-contract path).
	 */
	public static function from_cycle( Cycle $cycle ): self {
		return new self(
			$cycle->get_contract_id(),
			$cycle->get_count(),
			$cycle->get_status()->get_value(),
			$cycle->get_ends_at_gmt()
		);
	}

	/**
	 * Contract id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * Head chargeable count, or null.
	 */
	public function get_head_count(): ?int {
		return $this->head_count;
	}

	/**
	 * Head status string.
	 */
	public function get_head_status(): string {
		return $this->head_status;
	}

	/**
	 * Head period end (GMT string).
	 */
	public function get_head_ends_at_gmt(): string {
		return $this->head_ends_at_gmt;
	}
}
