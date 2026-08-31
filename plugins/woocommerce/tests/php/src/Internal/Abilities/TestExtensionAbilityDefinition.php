<?php
/**
 * Test extension ability definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

/**
 * Test extension ability definition.
 */
class TestExtensionAbilityDefinition implements AbilityDefinition {

	public const ABILITY_ID = 'test-extension/test-extension-ability';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => 'Test extension ability',
			'description'         => 'Test extension ability registered through the ability loader.',
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
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}
}
