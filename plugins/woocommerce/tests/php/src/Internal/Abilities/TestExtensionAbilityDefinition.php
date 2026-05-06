<?php
/**
 * Test extension ability definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilityDefinition;

/**
 * Test extension ability definition.
 */
class TestExtensionAbilityDefinition implements AbilityDefinition {

	public const ABILITY_ID = 'woocommerce/test-extension-ability';

	/**
	 * Register the test extension ability.
	 */
	public static function register(): void {
		if ( function_exists( 'wp_has_ability' ) && wp_has_ability( self::ABILITY_ID ) ) {
			return;
		}

		wp_register_ability(
			self::ABILITY_ID,
			array(
				'label'               => 'Test Extension Ability',
				'description'         => 'Test extension ability registered through the WooCommerce ability loader.',
				'category'            => 'woocommerce',
				'execute_callback'    => static function (): array {
					return array(
						'ok' => true,
					);
				},
				'output_schema'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'ok' => array( 'type' => 'boolean' ),
					),
					'additionalProperties' => false,
				),
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}
}
