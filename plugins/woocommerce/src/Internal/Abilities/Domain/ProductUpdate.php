<?php
/**
 * Product update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product update ability.
 */
class ProductUpdate extends AbstractDomainAbility implements AbilityDefinition {

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
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update product', 'woocommerce' ),
			'description'         => __(
				'Update an existing product using supported catalog fields.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'product', self::get_product_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_update_product' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
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

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_product_update_no_fields',
				__( 'At least one product field is required to update a product.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product_config = self::get_product_config_for_product( $product );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		if (
			isset( $input['status'] )
			&& in_array(
				sanitize_key( $input['status'] ),
				array( ProductStatus::PUBLISH, ProductStatus::FUTURE, ProductStatus::PRIVATE ),
				true
			)
			&& sanitize_key( $input['status'] ) !== $product->get_status()
			&& ! self::current_user_can_publish_products()
		) {
			return new \WP_Error(
				'woocommerce_product_publish_forbidden',
				__( 'You are not allowed to publish products.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( isset( $input['product_type_alias'] ) ) {
			$product_config = self::get_product_config_for_alias( $input['product_type_alias'] );

			if ( is_wp_error( $product_config ) ) {
				return $product_config;
			}

			$product = wc_get_product_object( $product_config['wc_type'], $product->get_id() );

			if ( ! $product ) {
				return new \WP_Error(
					'woocommerce_invalid_product_type',
					__( 'Invalid product type.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		try {
			if ( isset( $input['product_type_alias'] ) ) {
				self::apply_product_type_config( $product, $product_config );
			}

			$validation_error = self::set_product_props_from_input( $product, $input, $product_config );
			if ( is_wp_error( $validation_error ) ) {
				return $validation_error;
			}
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}

		$save_error = self::save_product( $product, 'woocommerce_product_update_failed' );
		if ( is_wp_error( $save_error ) ) {
			return $save_error;
		}

		return array(
			'product' => self::format_product_for_response( $product ),
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
		$product_id = self::get_id_from_input( $input );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'edit', $product_id );
	}

	/**
	 * Check whether the current user can publish products.
	 *
	 * @return bool
	 */
	private static function current_user_can_publish_products(): bool {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers the publish_products capability.
		return current_user_can( 'publish_products' );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return self::get_product_update_input_schema();
	}
}
