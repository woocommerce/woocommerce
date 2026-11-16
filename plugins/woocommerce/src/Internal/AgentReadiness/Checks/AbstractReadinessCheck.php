<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessRule;

defined( 'ABSPATH' ) || exit;

/**
 * Shared defaults for readiness checks.
 *
 * @since 11.4.0
 */
abstract class AbstractReadinessCheck implements ReadinessRule {

	/**
	 * The same field and effect on every channel.
	 *
	 * @param string $field  Feed field.
	 * @param string $effect EFFECT_NOT_LISTABLE or EFFECT_GAPS.
	 * @return array<string, array{field:string,effect:string}>
	 */
	protected static function on_every_channel( string $field, string $effect ): array {
		return array_fill_keys(
			array_keys( FeedChannels::all() ),
			array(
				'field'  => $field,
				'effect' => $effect,
			)
		);
	}

	/**
	 * The published variations of a failing variable product that have the problem, so only their
	 * sales count against it. Null when the problem is the whole product's.
	 *
	 * @param \WC_Product $product Product that fails the rule.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 * @return array<int, int>|null Variation ids.
	 */
	public function failing_variations( \WC_Product $product, ProductScan $scan ): ?array {
		unset( $product, $scan );
		return null;
	}

	/**
	 * Only the ids of the variations a test fails, or null when none does, as when the parent fails.
	 *
	 * @param \WC_Product $product Product.
	 * @param callable    $test    Whether a variation has the problem: fn( \WC_Product ): bool.
	 * @return array<int, int>|null
	 */
	protected static function variation_ids_where( \WC_Product $product, callable $test ): ?array {
		$ids = array();
		foreach ( ProductFields::variations( $product ) as $variation ) {
			if ( $test( $variation ) ) {
				$ids[] = $variation->get_id();
			}
		}
		return array() === $ids ? null : $ids;
	}

	/**
	 * Field and effect by channel for one failing product: the given map.
	 *
	 * @param \WC_Product                                      $product  Product that fails the rule.
	 * @param array<string, array{field:string,effect:string}> $channels The rule's channel map.
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels_for( \WC_Product $product, array $channels ): array {
		return $channels;
	}
}
