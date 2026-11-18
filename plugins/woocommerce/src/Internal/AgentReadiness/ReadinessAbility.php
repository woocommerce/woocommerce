<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * What the agent readiness abilities share: the feature gate, the permission, and the channel schema.
 *
 * @since 11.4.0
 */
trait ReadinessAbility {

	/**
	 * Ability name while the feature is on, or an empty string so AbilitiesLoader skips it.
	 *
	 * @param string $name Ability name.
	 * @return string
	 */
	protected static function name_when_enabled( string $name ): string {
		$features = wc_get_container()->get( FeaturesController::class );
		return $features->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID ) ? $name : '';
	}

	/**
	 * Whether the current user may use the ability.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Schema of a check's field and effect by channel.
	 *
	 * @return array<string, mixed>
	 */
	protected static function channel_map_schema(): array {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Feed field and effect by channel id: not_listable when the channel requires the field, gaps when the product lists with a gap. Empty for checks about the product page.', 'woocommerce' ),
			'additionalProperties' => array(
				'type'       => 'object',
				'properties' => array(
					'field'  => array( 'type' => 'string' ),
					'effect' => array(
						'type' => 'string',
						'enum' => array( ReadinessRule::EFFECT_NOT_LISTABLE, ReadinessRule::EFFECT_GAPS ),
					),
				),
			),
		);
	}
}
