<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
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
