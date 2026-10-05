<?php
/**
 * Test extension products definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

/**
 * An extension's read ability, registered through the ability loader, that
 * returns products under `items` and opts in to extension fields.
 */
class TestExtensionProductsDefinition implements AbilityDefinition {

	/**
	 * Ability name.
	 */
	public static function get_name(): string {
		return 'test-extension/products';
	}

	/**
	 * Output key, or '' for the root.
	 */
	protected static function output_key(): string {
		return 'items';
	}

	/**
	 * Registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		$product_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'   => array( 'type' => 'integer' ),
				'name' => array( 'type' => 'string' ),
			),
		);
		$key            = static::output_key();

		return array(
			'label'               => 'Extension products',
			'description'         => 'Products an extension lists.',
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array( 'id' => array( 'type' => 'integer' ) ),
				'required'   => array( 'id' ),
			),
			'output_schema'       => '' === $key ? $product_schema : array(
				'type'       => 'object',
				'properties' => array(
					$key => array(
						'type'  => 'array',
						'items' => $product_schema,
					),
				),
			),
			'execute_callback'    => static function ( array $input ) use ( $key ): array {
				$product = wc_get_product( $input['id'] );
				$item    = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
				return '' === $key ? $item : array( $key => array( $item ) );
			},
			'permission_callback' => '__return_true',
			'meta'                => array(
				'woocommerce' => array(
					'extension_fields' => array(
						'object_type' => 'product',
						'output'      => $key,
					),
				),
			),
		);
	}
}
