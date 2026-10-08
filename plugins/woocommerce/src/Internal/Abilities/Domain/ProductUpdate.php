<?php
/**
 * Product update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\AbilityFields;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product update ability.
 */
class ProductUpdate extends AbstractChangeAbility implements AbilityDefinition {

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
	 * Object type that the ability changes.
	 *
	 * @return string
	 */
	public static function get_object_type(): string {
		return 'product';
	}

	/**
	 * Load the product to change, with the input type.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public static function load( array $input ) {
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
		$product_config = isset( $input['product_type_alias'] )
			? self::get_product_config_for_alias( $input['product_type_alias'] )
			: self::get_product_config_for_product( $subject );

		try {
			if ( isset( $input['product_type_alias'] ) ) {
				self::apply_product_type_config( $subject, $product_config );
			}

			return self::set_product_props_from_input( $subject, $input, $product_config );
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * The product update that sets the fields of the input back to their values before the change.
	 *
	 * @param \WC_Product $subject Product before the change.
	 * @param array       $input   Ability input.
	 * @return array{ability: string, input: array}
	 */
	public static function undo( $subject, array $input ): ?array {
		$getters = array(
			'name'              => 'get_name',
			'sku'               => 'get_sku',
			'regular_price'     => 'get_regular_price',
			'sale_price'        => 'get_sale_price',
			'description'       => 'get_description',
			'short_description' => 'get_short_description',
			'status'            => 'get_status',
			'manage_stock'      => 'get_manage_stock',
			'stock_quantity'    => 'get_stock_quantity',
			'stock_status'      => 'get_stock_status',
			'external_url'      => 'get_product_url',
			'button_text'       => 'get_button_text',
			'grouped_products'  => 'get_children',
		);

		$undo = array( 'id' => $subject->get_id() );
		if ( isset( $input['product_type_alias'] ) ) {
			$alias = self::get_product_type_alias_for_product( $subject );
			if ( is_wp_error( $alias ) ) {
				return null;
			}
			$undo['product_type_alias'] = $alias;
		}
		foreach ( $getters as $field => $getter ) {
			if ( array_key_exists( $field, $input ) && is_callable( array( $subject, $getter ) ) ) {
				$undo[ $field ] = $subject->{$getter}( 'edit' );
			}
		}
		if ( ! empty( $input['extensions'] ) && is_array( $input['extensions'] ) ) {
			$values             = AbilityFields::get_values( 'product', $subject );
			$undo['extensions'] = array();
			foreach ( array_keys( $input['extensions'] ) as $attribute ) {
				$undo['extensions'][ $attribute ] = $values[ $attribute ] ?? null;
			}
		}

		return array(
			'ability' => self::get_name(),
			'input'   => $undo,
		);
	}

	/**
	 * Save the product.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public static function save( $subject ) {
		return self::save_product( $subject, 'woocommerce_product_update_failed' );
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
		return AbilityFields::add_to_input_schema( self::get_product_update_input_schema(), 'product' );
	}
}
