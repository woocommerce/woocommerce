<?php
/**
 * CycleView - a read-only view of a cycle at the `Api\` boundary.
 *
 * Consumers read cycles through this view instead of the Core entity. Getters may be
 * added, never removed.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\View
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\View;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable cycle view.
 */
final class CycleView {

	/**
	 * Cycle id.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Chain kind (e.g. `billing`).
	 *
	 * @var string
	 */
	private $kind;

	/**
	 * Position in the chain.
	 *
	 * @var int
	 */
	private $sequence_no;

	/**
	 * Charge count, or null for a non-counting cycle.
	 *
	 * @var int|null
	 */
	private $count;

	/**
	 * Cycle status slug.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Period start (GMT `Y-m-d H:i:s`).
	 *
	 * @var string
	 */
	private $starts_at_gmt;

	/**
	 * Period end (GMT `Y-m-d H:i:s`).
	 *
	 * @var string
	 */
	private $ends_at_gmt;

	/**
	 * Expected total (decimal string).
	 *
	 * @var string
	 */
	private $expected_total;

	/**
	 * ISO-4217 currency code.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Linked order id, or null.
	 *
	 * @var int|null
	 */
	private $order_id;

	/**
	 * Use {@see self::from_cycle()}.
	 */
	private function __construct() {
	}

	/**
	 * Build a view of a stored cycle.
	 *
	 * @internal Built by the engine `Api\` facades only.
	 *
	 * @param Cycle $cycle Stored cycle.
	 */
	public static function from_cycle( Cycle $cycle ): self {
		$view                 = new self();
		$view->id             = (int) $cycle->get_id();
		$view->contract_id    = $cycle->get_contract_id();
		$view->kind           = $cycle->get_kind();
		$view->sequence_no    = $cycle->get_sequence_no();
		$view->count          = $cycle->get_count();
		$view->status         = $cycle->get_status()->get_value();
		$view->starts_at_gmt  = $cycle->get_starts_at_gmt();
		$view->ends_at_gmt    = $cycle->get_ends_at_gmt();
		$view->expected_total = $cycle->get_expected_total();
		$view->currency       = $cycle->get_currency();
		$view->order_id       = $cycle->get_order_id();

		return $view;
	}

	/**
	 * Cycle id.
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Contract id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * Chain kind (e.g. `billing`).
	 */
	public function get_kind(): string {
		return $this->kind;
	}

	/**
	 * Position in the chain.
	 */
	public function get_sequence_no(): int {
		return $this->sequence_no;
	}

	/**
	 * Charge count, or null for a non-counting cycle.
	 */
	public function get_count(): ?int {
		return $this->count;
	}

	/**
	 * Cycle status slug.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Period start (GMT `Y-m-d H:i:s`).
	 */
	public function get_starts_at_gmt(): string {
		return $this->starts_at_gmt;
	}

	/**
	 * Period end (GMT `Y-m-d H:i:s`).
	 */
	public function get_ends_at_gmt(): string {
		return $this->ends_at_gmt;
	}

	/**
	 * Expected total (decimal string).
	 */
	public function get_expected_total(): string {
		return $this->expected_total;
	}

	/**
	 * ISO-4217 currency code.
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Linked order id, or null.
	 */
	public function get_order_id(): ?int {
		return $this->order_id;
	}
}
