<?php
/**
 * PlanView - a read-only view of a selling plan at the `Api\` boundary.
 *
 * Consumers read plans through this view instead of the Core entity. Getters may be
 * added, never removed. The three policies are the owning extension's opaque
 * payloads, returned as stored. The engine itself reads only `billing_policy`, as
 * the renewal fallback for contracts without a plan snapshot (see {@see \Automattic\WooCommerce\SubscriptionsEngine\Api\Plans}).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\View
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\View;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable plan view.
 */
final class PlanView {

	/**
	 * Plan row values keyed by field name.
	 *
	 * @var array{id: int, extension_slug: ?string, status: string, name: string, billing_policy: ?array<string, mixed>, pricing_policy: ?array<string, mixed>, delivery_policy: ?array<string, mixed>, date_created_gmt: ?string, date_updated_gmt: ?string}
	 */
	private $fields;

	/**
	 * Use {@see self::from_plan()}.
	 */
	private function __construct() {
	}

	/**
	 * Build a view of a plan. An unsaved plan (the would-be plan a create
	 * validates) has id 0.
	 *
	 * @internal Built by the engine `Api\` facades only.
	 *
	 * @param Plan $plan Plan entity.
	 */
	public static function from_plan( Plan $plan ): self {
		$view         = new self();
		$view->fields = array(
			'id'               => (int) $plan->get_id(),
			'extension_slug'   => $plan->get_extension_slug(),
			'status'           => $plan->get_status(),
			'name'             => $plan->get_name(),
			'billing_policy'   => $plan->get_billing_policy(),
			'pricing_policy'   => $plan->get_pricing_policy(),
			'delivery_policy'  => $plan->get_delivery_policy(),
			'date_created_gmt' => $plan->get_date_created_gmt(),
			'date_updated_gmt' => $plan->get_date_updated_gmt(),
		);

		return $view;
	}

	/**
	 * Plan id; 0 for a plan that is not stored yet.
	 */
	public function get_id(): int {
		return $this->fields['id'];
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->fields['extension_slug'];
	}

	/**
	 * Plan status slug.
	 */
	public function get_status(): string {
		return $this->fields['status'];
	}

	/**
	 * Display name.
	 */
	public function get_name(): string {
		return $this->fields['name'];
	}

	/**
	 * Billing payload of the owning extension, or null.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_billing_policy(): ?array {
		return $this->fields['billing_policy'];
	}

	/**
	 * Pricing payload of the owning extension, or null.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_pricing_policy(): ?array {
		return $this->fields['pricing_policy'];
	}

	/**
	 * Delivery payload of the owning extension, or null.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_delivery_policy(): ?array {
		return $this->fields['delivery_policy'];
	}

	/**
	 * Creation time (GMT, `Y-m-d H:i:s`), or null for an unsaved plan.
	 */
	public function get_date_created_gmt(): ?string {
		return $this->fields['date_created_gmt'];
	}

	/**
	 * Last update time (GMT, `Y-m-d H:i:s`), or null for an unsaved plan.
	 */
	public function get_date_updated_gmt(): ?string {
		return $this->fields['date_updated_gmt'];
	}
}
