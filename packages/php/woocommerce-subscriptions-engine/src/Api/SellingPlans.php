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
 * Each extension constructs one instance scoped to its own slugs and reuses
 * it for every read - the slug scope is fixed at construction so call sites
 * never carry it around. Instances are cheap and hold no state beyond the
 * scope, so constructing more is harmless. Final: a facade over the engine
 * internals, not an extension seam.
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
	 * Extension slugs this instance reads plans for.
	 *
	 * @var array<int, string>
	 */
	private $extension_slugs;

	/**
	 * Scope the facade to the calling extension's slugs.
	 *
	 * @param array<int, string> $extension_slugs Extension slugs to read plans for.
	 */
	public function __construct( array $extension_slugs ) {
		$this->extension_slugs = $extension_slugs;
	}

	/**
	 * List the scoped extensions' active plans in display order - the read
	 * behind a plan-selection UI.
	 *
	 * @return array<int, Plan> Plans in display order.
	 */
	public function list_plans(): array {
		return ( new PlanRepository() )->query(
			array(
				'status'          => Plan::STATUS_ACTIVE,
				'extension_slugs' => $this->extension_slugs,
				'limit'           => self::PLAN_QUERY_LIMIT,
			)
		);
	}

	/**
	 * Fetch the active plans among the given ids owned by the scoped
	 * extensions, in display order - the read behind rendering a stored plan
	 * selection.
	 *
	 * Ids that are unknown, archived, or owned by an out-of-scope extension
	 * are simply absent from the result. An empty or invalid id list yields
	 * an empty array.
	 *
	 * @param array<int, int> $plan_ids Plan ids to fetch.
	 * @return array<int, Plan> Plans in display order.
	 */
	public function get_plans( array $plan_ids ): array {
		return ( new PlanRepository() )->query(
			array(
				'status'          => Plan::STATUS_ACTIVE,
				'extension_slugs' => $this->extension_slugs,
				'ids'             => $plan_ids,
				'limit'           => self::PLAN_QUERY_LIMIT,
			)
		);
	}
}
