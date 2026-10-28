<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a product feed integration reuse the readiness checks: returns the ids
 * of the checks a product fails. The feed framework skips rows with issues, so
 * an integration that only wants to report gaps should wrap this rather than
 * return it from get_feed_validator() as is.
 *
 * @since 11.4.0
 */
class ReadinessFeedValidator implements FeedValidatorInterface {

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param AgentReadiness $agent_readiness Readiness service.
	 */
	final public function init( AgentReadiness $agent_readiness ): void {
		$this->agent_readiness = $agent_readiness;
	}

	/**
	 * Ids of the readiness checks the product fails.
	 *
	 * @param array       $row     The mapped feed entry (unused; the checks read the product).
	 * @param \WC_Product $product The related product.
	 * @return string[] Failing check ids.
	 */
	public function validate_entry( array $row, \WC_Product $product ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->agent_readiness->get_failing_check_ids( $product );
	}
}
