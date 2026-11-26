<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * What the agent readiness abilities share: the permission and the channel schema.
 *
 * @since 11.4.0
 */
trait ReadinessAbility {

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

	/**
	 * The output with every null field left out, at any depth, since Core ability output types are never nullable.
	 * Items of a list stay where they are.
	 *
	 * @param array<mixed> $output Ability output.
	 * @return array<mixed>
	 */
	protected static function without_nulls( array $output ): array {
		foreach ( $output as $key => $value ) {
			if ( null === $value && is_string( $key ) ) {
				unset( $output[ $key ] );
			} elseif ( is_array( $value ) ) {
				$output[ $key ] = self::without_nulls( $value );
			}
		}
		return $output;
	}
}
