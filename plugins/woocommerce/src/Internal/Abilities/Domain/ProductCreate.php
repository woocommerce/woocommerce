<?php
/**
 * Product create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\AbilityFields;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product create ability.
 */
class ProductCreate extends AbstractChangeAbility implements AbilityDefinition {

	use ProductAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/product-create';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create product', 'woocommerce' ),
			'description'         => __(
				'Create a product using supported catalog fields.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'product', self::get_product_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_create_product' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Object type that the ability changes.
	 *
	 * @return string
	 */
	public static function get_object_type(): string {
		return 'product';
	}

	/**
	 * Make a new product of the input type.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public static function load( array $input ) {
		$product_config = self::get_product_config_for_alias( $input['product_type_alias'] ?? 'physical' );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		$product = wc_get_product_object( $product_config['wc_type'] );

		if ( ! $product ) {
			return new \WP_Error(
				'woocommerce_invalid_product_type',
				__( 'Invalid product type.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $product;
	}

	/**
	 * Set the product properties in memory.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error
	 */
	public static function change( $subject, array $input ) {
		$product_config = self::get_product_config_for_alias( $input['product_type_alias'] ?? 'physical' );

		try {
			self::apply_product_type_config( $subject, $product_config );

			return self::set_product_props_from_input( $subject, $input, $product_config );
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * Save the new product.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public static function save( $subject ) {
		return self::save_product( $subject, 'woocommerce_product_create_failed' );
	}

	/**
	 * The ability output for the saved product.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array
	 */
	public static function prepare_response( $subject ): array {
		return array(
			'product' => self::format_product_for_response( $subject ),
		);
	}

	/**
	 * Check product creation access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_create_product( $input = array() ): bool {
		// Cap is not object-scoped for create.
		unset( $input );

		return wc_rest_check_post_permissions( 'product', 'create' );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return AbilityFields::add_to_input_schema( self::get_product_create_input_schema(), 'product' );
	}
}
