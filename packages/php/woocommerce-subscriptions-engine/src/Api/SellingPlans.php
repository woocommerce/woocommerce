<?php
/**
 * SellingPlans - the engine's public catalog read facade.
 *
 * The one surface consumers import to read the plans catalog: list an
 * extension's active plans and fetch specific plans by id for selection and
 * display UIs. Which products a plan applies to is consumer-owned - the
 * engine stores the catalog, not product attachment. The facade hides the
 * internal `Integration\` repositories behind a stable boundary, so the
 * internals stay refactorable. Strictly additive-only, like every `Api\`
 * surface.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public selling-plans catalog read facade.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class SellingPlans {

	/**
	 * Query limit for plan lookups; high enough that a plan catalog is never
	 * truncated by the repository's default of 50.
	 *
	 * @var int
	 */
	private const PLAN_QUERY_LIMIT = 200;

	/**
	 * List an extension's active plans in display order - the read behind a
	 * plan-selection UI.
	 *
	 * @param string $extension_slug Extension slug scope.
	 * @return array<int, Plan> Plans in display order.
	 */
	public static function list_plans( string $extension_slug ): array {
		return ( new PlanRepository() )->query(
			array(
				'status'         => Plan::STATUS_ACTIVE,
				'extension_slug' => $extension_slug,
				'limit'          => self::PLAN_QUERY_LIMIT,
			)
		);
	}

	/**
	 * Fetch the active plans among the given ids owned by one extension, in
	 * display order - the read behind rendering a stored plan selection.
	 *
	 * Ids that are unknown, archived, or owned by another extension are
	 * simply absent from the result. An empty or invalid id list yields an
	 * empty array.
	 *
	 * @param array<int, int> $plan_ids       Plan ids to fetch.
	 * @param string          $extension_slug Extension slug scope.
	 * @return array<int, Plan> Plans in display order.
	 */
	public static function get_plans( array $plan_ids, string $extension_slug ): array {
		return ( new PlanRepository() )->query(
			array(
				'status'         => Plan::STATUS_ACTIVE,
				'extension_slug' => $extension_slug,
				'ids'            => $plan_ids,
				'limit'          => self::PLAN_QUERY_LIMIT,
			)
		);
	}
}
