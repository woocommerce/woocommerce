<?php
/**
 * SellingPlans - the engine's public plan read facade.
 *
 * The one surface consumers import to read plans: list the scoped extensions'
 * plans, fetch specific plans by id, or fetch one plan. Results are read-only
 * {@see PlanView}s; callers choose which statuses they want (no status is
 * filtered by default). Which products a plan applies to is consumer-owned.
 * Strictly additive-only, like every `Api\` surface.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public selling-plans read facade.
 *
 * Each extension constructs one instance scoped to its own slugs and reuses
 * it for every read - the slug scope is fixed at construction so call sites
 * never carry it around. Plans owned by an out-of-scope extension are never
 * returned. Instances are cheap and hold no state beyond the scope. Final: a
 * facade over the engine internals, not an extension seam.
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
	 * List the scoped extensions' plans, oldest id first.
	 *
	 * @param array<string, mixed> $args Optional `status`: a plan status slug or a list of slugs. Absent: every status.
	 * @return array<int, PlanView>
	 */
	public function list_plans( array $args = array() ): array {
		return $this->query( $args, array() );
	}

	/**
	 * Fetch the plans among the given ids owned by the scoped extensions, oldest
	 * id first. Unknown, out-of-scope, or status-filtered ids are absent; an
	 * empty or invalid id list yields an empty array.
	 *
	 * @param array<int, int>      $plan_ids Plan ids to fetch.
	 * @param array<string, mixed> $args     Optional `status`: a plan status slug or a list of slugs. Absent: every status.
	 * @return array<int, PlanView>
	 */
	public function get_plans( array $plan_ids, array $args = array() ): array {
		return $this->query( $args, array( 'ids' => $plan_ids ) );
	}

	/**
	 * Fetch one plan owned by the scoped extensions, in any status.
	 *
	 * @param int $id Plan id.
	 * @return PlanView|null Null for an unknown, out-of-scope, or non-positive id.
	 */
	public function get_plan( int $id ): ?PlanView {
		if ( $id <= 0 ) {
			return null;
		}

		$plans = $this->get_plans( array( $id ) );

		return $plans[0] ?? null;
	}

	/**
	 * Run a scoped repository query and map the results to views.
	 *
	 * @param array<string, mixed> $args    Caller args (only `status` is read).
	 * @param array<string, mixed> $filters Extra repository filters.
	 * @return array<int, PlanView>
	 */
	private function query( array $args, array $filters ): array {
		$query = array_merge(
			$filters,
			array(
				'extension_slugs' => $this->extension_slugs,
				'orderby'         => 'id',
				'order'           => 'asc',
				'limit'           => self::PLAN_QUERY_LIMIT,
			)
		);
		if ( array_key_exists( 'status', $args ) ) {
			$query['status'] = $args['status'];
		}

		return array_map(
			static function ( Plan $plan ): PlanView {
				return PlanView::from_plan( $plan );
			},
			( new PlanRepository() )->query( $query )
		);
	}
}
