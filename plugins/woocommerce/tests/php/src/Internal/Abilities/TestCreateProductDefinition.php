<?php
/**
 * Test create-product definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\InMemoryWrite;
use Automattic\WooCommerce\Abilities\InverseAfterSave;

/**
 * Creates a product through the in-memory write contract; its undo needs the new ID.
 */
class TestCreateProductDefinition implements InMemoryWrite, InverseAfterSave {

	public const ABILITY_ID = 'test-extension/create-product';

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
			'label'               => 'Create product',
			'description'         => 'Test in-memory write that creates a product.',
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'name' => array( 'type' => 'string' ),
				),
				'required'   => array( 'name' ),
			),
			'execute_callback'    => static function (): array {
				return array( 'own_execute' => true );
			},
			'permission_callback' => '__return_true',
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'idempotent'  => false,
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
	 * A new product.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data
	 */
	public static function subject( array $input ) {
		return new \WC_Product_Simple();
	}

	/**
	 * Accept any input.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 * @return true
	 */
	public static function validate( $subject, array $input ) {
		return true;
	}

	/**
	 * Name the product in memory.
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
	 * The undo is only known after the save.
	 *
	 * @param \WC_Data $subject Product, before the change.
	 * @param array    $input   Ability input.
	 * @return null
	 */
	public static function inverse( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * Delete the product the save created.
	 *
	 * @param array    $before        Snapshot before apply().
	 * @param \WC_Data $saved_subject Saved product.
	 * @param array    $input         Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public static function inverse_after_save( array $before, $saved_subject, array $input ): ?array {
		if ( 0 !== $before['id'] ) {
			return null;
		}
		return array(
			'ability' => 'woocommerce/product-delete',
			'input'   => array(
				'id'    => $saved_subject->get_id(),
				'force' => true,
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
		return array( 'product_id' => $subject->get_id() );
	}
}
