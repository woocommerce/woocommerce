<?php
/**
 * Test option write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\InMemoryWrite;

/**
 * Sets an option through the in-memory write contract.
 */
class TestOptionWriteDefinition implements InMemoryWrite {

	public const ABILITY_ID = 'test-extension/set-option';

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
			'label'               => 'Set option',
			'description'         => 'Test in-memory write that sets an option.',
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'name'  => array( 'type' => 'string' ),
					'value' => array( 'type' => 'string' ),
				),
				'required'   => array( 'name', 'value' ),
			),
			'execute_callback'    => static function (): array {
				return array( 'own_execute' => true );
			},
			'permission_callback' => '__return_true',
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Kind of object the write changes.
	 */
	public static function subject_type(): string {
		return 'test_option';
	}

	/**
	 * Load the option.
	 *
	 * @param array $input Ability input.
	 * @return TestOptionRecord
	 */
	public static function subject( array $input ) {
		return new TestOptionRecord( $input['name'] );
	}

	/**
	 * Refuse an empty value.
	 *
	 * @param TestOptionRecord $subject Option.
	 * @param array            $input   Ability input.
	 * @return true|\WP_Error
	 */
	public static function validate( $subject, array $input ) {
		return '' === $input['value'] ? new \WP_Error( 'test_empty_value', 'Value is empty.' ) : true;
	}

	/**
	 * Set the value in memory.
	 *
	 * @param TestOptionRecord $subject Option.
	 * @param array            $input   Ability input.
	 */
	public static function apply( $subject, array $input ): void {
		$subject->value = $input['value'];
	}

	/**
	 * Declared effects.
	 *
	 * @return array{effects: string[], undoable: bool}
	 */
	public static function hints(): array {
		return array(
			'effects'  => array(),
			'undoable' => true,
		);
	}

	/**
	 * Set the value back.
	 *
	 * @param TestOptionRecord $subject Option, before the change.
	 * @param array            $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public static function inverse( $subject, array $input ): ?array {
		return array(
			'ability' => self::ABILITY_ID,
			'input'   => $subject->snapshot(),
		);
	}

	/**
	 * The ability's response.
	 *
	 * @param TestOptionRecord $subject Saved option.
	 * @return array
	 */
	public static function respond( $subject ): array {
		return array(
			'name'  => $subject->name,
			'value' => get_option( $subject->name ),
		);
	}
}
