<?php
/**
 * Product create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public static function get_resource(): string {
		return 'product';
	}

	/**
	 * Create the product object of the input type, without saving it.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public static function load( array $input ) {
		$product_config = self::get_product_config_for_alias( $input['product_type_alias'] ?? 'physical' );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		$product = ProductType::VARIABLE === $product_config['wc_type']
			? new VariableProductDraft()
			: wc_get_product_object( $product_config['wc_type'] );

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

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		try {
			self::apply_product_type_config( $subject, $product_config );

			$error = self::set_product_props_from_input( $subject, $input, $product_config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			if ( $subject instanceof VariableProductDraft ) {
				$variations = self::build_variations( $subject, $input['variations'] ?? array() );
				if ( is_wp_error( $variations ) ) {
					return $variations;
				}
				$subject->set_pending_variations( $variations );
			}

			return null;
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * Build the variation objects in memory. Each one must pick one option of
	 * every parent attribute, and no two may pick the same combination or SKU.
	 *
	 * @param \WC_Product_Variable $product Parent product, with its attributes set.
	 * @param array                $inputs Variation inputs.
	 * @return \WC_Product_Variation[]|\WP_Error
	 * @throws \WC_Data_Exception When a variation setter rejects a value.
	 */
	private static function build_variations( \WC_Product_Variable $product, array $inputs ) {
		$options = array();
		foreach ( $product->get_attributes() as $key => $attribute ) {
			$options[ $key ] = $attribute->get_options();
		}
		if ( empty( $options ) ) {
			return new \WP_Error(
				'woocommerce_product_attributes_required',
				__( 'A variable product needs at least one attribute.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$variations   = array();
		$combinations = array();
		$skus         = array();
		foreach ( $inputs as $index => $input ) {
			$attributes = array();
			foreach ( (array) $input['attributes'] as $name => $option ) {
				$key = sanitize_title( $name );
				if ( ! isset( $options[ $key ] ) || ! in_array( $option, $options[ $key ], true ) ) {
					return new \WP_Error(
						'woocommerce_product_variation_attribute_invalid',
						/* translators: 1: Variation position. 2: Attribute name. */
						sprintf( __( 'Variation %1$d uses the attribute "%2$s" with an option the product does not have.', 'woocommerce' ), $index + 1, $name ),
						array( 'status' => 400 )
					);
				}
				$attributes[ $key ] = $option;
			}
			ksort( $attributes );
			$combination = wp_json_encode( $attributes );
			if ( isset( $combinations[ $combination ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_duplicate',
					/* translators: %d: Variation position. */
					sprintf( __( 'Variation %d repeats the attributes of another variation.', 'woocommerce' ), $index + 1 ),
					array( 'status' => 400 )
				);
			}
			$combinations[ $combination ] = true;

			$sku = $input['sku'] ?? '';
			if ( '' !== $sku && isset( $skus[ $sku ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_sku_duplicate',
					/* translators: %d: Variation position. */
					sprintf( __( 'Variation %d repeats the SKU of another variation.', 'woocommerce' ), $index + 1 ),
					array( 'status' => 400 )
				);
			}
			$skus[ $sku ] = true;

			$variation = new \WC_Product_Variation();
			$variation->set_attributes( $attributes );
			$variation->set_status( ProductStatus::PUBLISH );
			foreach ( array( 'sku', 'regular_price', 'sale_price', 'manage_stock', 'stock_quantity', 'stock_status' ) as $field ) {
				if ( array_key_exists( $field, $input ) ) {
					$value = self::prepare_product_field_value( $field, $input[ $field ] );
					if ( is_wp_error( $value ) ) {
						return $value;
					}
					$variation->{'set_' . $field}( $value );
				}
			}
			$variations[] = $variation;
		}

		return $variations;
	}

	/**
	 * The variations of a variable product, each with the `extensions` input of its item.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @param array       $input   Ability input.
	 * @return array<int, array{0: \WC_Product_Variation, 1: array}>
	 */
	public static function pending_objects( $subject, array $input ): array {
		if ( ! $subject instanceof VariableProductDraft ) {
			return array();
		}
		$objects = array();
		foreach ( $subject->get_pending_variations() as $index => $variation ) {
			$objects[] = array( $variation, $input['variations'][ $index ]['extensions'] ?? array() );
		}
		return $objects;
	}

	/**
	 * Save the product.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public static function save( $subject ) {
		$error = self::save_product( $subject, 'woocommerce_product_create_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$variations = $subject instanceof VariableProductDraft ? $subject->get_pending_variations() : array();
		foreach ( $variations as $variation ) {
			$variation->set_parent_id( $subject->get_id() );
			$error = self::save_product( $variation, 'woocommerce_product_create_failed' );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
		}
		if ( ! empty( $variations ) ) {
			$children = $subject->get_data_store()->read_children( $subject, true );
			$subject->set_children( $children['all'] );
			$subject->set_visible_children( $children['visible'] );
			\WC_Product_Variable::sync( $subject );
			$subject->set_price( get_post_meta( $subject->get_id(), '_price', true ) );
		}

		return null;
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
		return self::get_product_create_input_schema();
	}
}
