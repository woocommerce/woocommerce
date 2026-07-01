<?php
/**
 * DueRenewal - one row of the cycle-aware due scan: a contract and the head fields the
 * renewal selector needs to decide what to charge. Produced by
 * {@see ContractRepository::find_due()}, which joins each due contract to its head cycle
 * so the scan can filter to actionable contracts (head billed and due, or head pending
 * with an expired lease) in SQL - keeping non-actionable rows out of the batch budget.
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
 * A due contract plus its head-cycle fields.
 */
final class DueRenewal {

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
	 * Build a due-scan row from a contract and its head-cycle fields.
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
	 * Build from a hydrated head cycle - the single-contract path where the head is loaded
	 * directly rather than joined in the scan.
	 *
	 * @param int   $contract_id Contract id.
	 * @param Cycle $head        The contract's head cycle.
	 */
	public static function from_head( int $contract_id, Cycle $head ): self {
		return new self(
			$contract_id,
			$head->get_count(),
			$head->get_status()->get_value(),
			$head->get_ends_at_gmt()
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
