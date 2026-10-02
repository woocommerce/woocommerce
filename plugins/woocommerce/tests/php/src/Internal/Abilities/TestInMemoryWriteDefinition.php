<?php
/**
 * Test in-memory write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\InMemoryWrite;

/**
 * Renames a product through the in-memory write contract.
 */
class TestInMemoryWriteDefinition implements InMemoryWrite {

	public const ABILITY_ID = 'test-extension/rename-product';

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
			'label'               => 'Rename product',
			'description'         => 'Test in-memory write that renames a product.',
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'   => array( 'type' => 'integer' ),
					'name' => array( 'type' => 'string' ),
				),
				'required'   => array( 'id', 'name' ),
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
		return 'product';
	}

	/**
	 * Load the product.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|null
	 */
	public static function subject( array $input ) {
		return wc_get_product( $input['id'] ) ? wc_get_product( $input['id'] ) : null;
	}

	/**
	 * Refuse an empty name.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 * @return true|\WP_Error
	 */
	public static function validate( $subject, array $input ) {
		return '' === $input['name'] ? new \WP_Error( 'test_empty_name', 'Name is empty.' ) : true;
	}

	/**
	 * Rename in memory.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 */
	public static function apply( $subject, array $input ): void {
		$subject->set_name( $input['name'] );
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
	 * Rename back.
	 *
	 * @param \WC_Data $subject Product, before the change.
	 * @param array    $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public static function inverse( $subject, array $input ): ?array {
		return array(
			'ability' => self::ABILITY_ID,
			'input'   => array(
				'id'   => $subject->get_id(),
				'name' => $subject->get_name(),
			),
		);
	}

	/**
	 * The ability's response.
	 *
	 * @param \WC_Data $subject Saved product.
	 * @return array
	 */
	public static function respond( $subject ): array {
		return array(
			'product_id' => $subject->get_id(),
			'name'       => $subject->get_name(),
		);
	}
}
