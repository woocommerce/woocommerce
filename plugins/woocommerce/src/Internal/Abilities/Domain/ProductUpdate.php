<?php
/**
 * Product update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product update ability.
 */
class ProductUpdate extends DomainAbility implements AbilityDefinition {

	use ProductAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/product-update';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_args(): array {
		return array(
			'label'               => __( 'Update Product', 'woocommerce' ),
			'description'         => __(
				'Update an existing WooCommerce product using WooCommerce product APIs.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'product' ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_update_product' ),
			'meta'                => self::get_domain_meta( 'update', false, false, true ),
		);
	}

	/**
	 * Update a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		self::set_product_props( $product, $input );
		$product->save();

		return array(
			'product' => self::format_product( $product ),
		);
	}

	/**
	 * Check product update access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_update_product( $input = array() ): bool {
		$product_id = self::get_input_id( $input );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'edit', $product_id );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$schema               = self::get_product_mutation_input_schema();
		$schema['properties'] = array_merge(
			array(
				'id' => array( 'type' => 'integer' ),
			),
			$schema['properties']
		);
		$schema['required']   = array( 'id' );

		return $schema;
	}
}
