<?php
/**
 * HistoryEntryView - a read-only view of a contract history entry at the `Api\` boundary.
 *
 * Consumers read history entries through this view instead of the Core entity. Getters may be
 * added, never removed.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\View
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\View;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\HistoryEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable history entry view.
 */
final class HistoryEntryView {

	/**
	 * Entry id.
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
	 * Cycle id, or null.
	 *
	 * @var int|null
	 */
	private $cycle_id;

	/**
	 * The extension's type slug.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The extension's payload, or null.
	 *
	 * @var array<int|string, mixed>|null
	 */
	private $payload;

	/**
	 * When the engine stored the entry (GMT `Y-m-d H:i:s`).
	 *
	 * @var string
	 */
	private $date_created_gmt;

	/**
	 * Use {@see self::from_entry()}.
	 */
	private function __construct() {
	}

	/**
	 * Build a view of a stored entry.
	 *
	 * @internal Built by the engine `Api\` facades only.
	 *
	 * @param HistoryEntry $entry Stored entry.
	 */
	public static function from_entry( HistoryEntry $entry ): self {
		$view                   = new self();
		$view->id               = (int) $entry->get_id();
		$view->contract_id      = $entry->get_contract_id();
		$view->cycle_id         = $entry->get_cycle_id();
		$view->type             = $entry->get_type();
		$view->payload          = $entry->get_payload();
		$view->date_created_gmt = (string) $entry->get_date_created_gmt();

		return $view;
	}

	/**
	 * Entry id.
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
	 * Cycle id, or null.
	 */
	public function get_cycle_id(): ?int {
		return $this->cycle_id;
	}

	/**
	 * The extension's type slug.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * The extension's payload, decoded, or null.
	 *
	 * @return array<int|string, mixed>|null
	 */
	public function get_payload(): ?array {
		return $this->payload;
	}

	/**
	 * When the engine stored the entry (GMT `Y-m-d H:i:s`).
	 */
	public function get_date_created_gmt(): string {
		return $this->date_created_gmt;
	}
}
