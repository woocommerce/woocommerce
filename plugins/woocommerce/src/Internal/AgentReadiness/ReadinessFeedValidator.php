<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a product feed integration reuse the readiness checks: returns the ids
 * of the checks a product fails. The feed framework skips rows with issues, so
 * an integration that only wants to report gaps should wrap this rather than
 * return it from get_feed_validator() as is. A product the scan leaves out
 * (another type, hidden from the shop catalog) gets the `not_checked` issue,
 * so the feed skips it instead of exporting it unchecked.
 *
 * @since 11.4.0
 */
class ReadinessFeedValidator implements FeedValidatorInterface {
	/**
	 * Issue for a product the readiness checks don't cover.
	 */
	public const NOT_CHECKED = 'not_checked';


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
	 * Ids of the readiness checks the product fails, or `not_checked` for a
	 * product the checks don't cover. A variation is judged by its parent.
	 *
	 * @param array       $row     The mapped feed entry (unused; the checks read the product).
	 * @param \WC_Product $product The related product.
	 * @return string[] Failing check ids.
	 */
	public function validate_entry( array $row, \WC_Product $product ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$parent = $product instanceof \WC_Product_Variation ? wc_get_product( $product->get_parent_id() ) : $product;
		if ( $parent instanceof \WC_Product && ! ProductScan::covers( $parent ) ) {
			return array( self::NOT_CHECKED );
		}
		return $this->agent_readiness->get_failing_check_ids( $product );
	}
}
