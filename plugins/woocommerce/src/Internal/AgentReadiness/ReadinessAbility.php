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
	 * A value that may be null: the non-null schema, or null. Prefer this over a list of types
	 * so each branch has one type name (MCP and the Core ability schema checks).
	 *
	 * @param array<string, mixed> $schema Schema of the non-null value (must include type).
	 * @return array{anyOf: list<array<string, mixed>>}
	 */
	protected static function nullable( array $schema ): array {
		return array(
			'anyOf' => array(
				$schema,
				array( 'type' => 'null' ),
			),
		);
	}
}
