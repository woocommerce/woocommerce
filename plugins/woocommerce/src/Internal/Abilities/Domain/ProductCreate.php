<?php
/**
 * Product create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product create ability.
 */
class ProductCreate extends AbstractDomainAbility implements AbilityDefinition {

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
	 * Create a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$product_type_alias = $input['product_type_alias'] ?? 'physical';
		$product_config     = self::get_product_create_config( $product_type_alias );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		$product = wc_get_product_object( $product_config['wc_type'] );

		if ( ! $product || $product->get_type() !== $product_config['wc_type'] ) {
			return new \WP_Error(
				'woocommerce_invalid_product_type',
				__( 'Invalid product type.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			self::apply_product_type_config( $product, $product_config );

			$validation_error = self::set_product_props_from_input( $product, $input, $product_config );
			if ( is_wp_error( $validation_error ) ) {
				return $validation_error;
			}

			$rejection = self::apply_ability_contracts( $product, $input, 'woocommerce_product_create_rejected' );
			if ( is_wp_error( $rejection ) ) {
				return $rejection;
			}

			$rejection = isset( $product_config['validate'] ) ? call_user_func( $product_config['validate'], $product_type_alias, $product ) : null;
			if ( is_wp_error( $rejection ) ) {
				return new \WP_Error( 'woocommerce_product_create_rejected', $rejection->get_error_message(), array( 'status' => 400 ) );
			}
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}

		$save_error = self::save_product( $product, 'woocommerce_product_create_failed' );
		if ( is_wp_error( $save_error ) ) {
			return $save_error;
		}

		return array(
			'product' => self::format_product_for_response( $product ),
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
		return self::get_product_create_input_schema();
	}
}
