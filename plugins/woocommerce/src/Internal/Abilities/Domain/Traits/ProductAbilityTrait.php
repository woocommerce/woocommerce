<?php
/**
 * Product ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * Shared product helpers for WooCommerce domain ability definitions.
 */
trait ProductAbilityTrait {

	/**
	 * Get the product create input schema.
	 *
	 * @return array
	 */
	protected static function get_product_create_input_schema(): array {
		return array(
			'type'  => 'object',
			'oneOf' => self::get_product_type_input_schema_branches( array( 'name' ), false ),
		);
	}

	/**
	 * Get the product update input schema.
	 *
	 * Updates without a product type can mutate fields shared by all
	 * supported product types. Type-specific fields require an explicit type.
	 *
	 * @return array
	 */
	protected static function get_product_update_input_schema(): array {
		$branches          = self::get_product_type_input_schema_branches( array( 'id' ), true );
		$mutation_schemas  = self::get_product_mutation_field_schemas();
		$common_properties = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);

		foreach ( self::get_common_product_mutation_fields() as $field ) {
			$common_properties[ $field ] = $mutation_schemas[ $field ];
		}

		array_unshift(
			$branches,
			array(
				'type'                 => 'object',
				'properties'           => $common_properties,
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			)
		);

		return array(
			'type'  => 'object',
			'oneOf' => $branches,
		);
	}

	/**
	 * Get product input schema branches, one for each supported product type.
	 *
	 * @param array<int, string> $base_required Required fields for every branch.
	 * @param bool               $require_type  Whether every branch must include type.
	 * @return array<int, array>
	 */
	private static function get_product_type_input_schema_branches( array $base_required, bool $require_type ): array {
		$branches = array();

		$creating = ! in_array( 'id', $base_required, true );

		foreach ( self::get_supported_product_types() as $product_type ) {
			$product_config = self::get_product_type_config( $product_type, $creating );
			if ( is_wp_error( $product_config ) ) {
				continue;
			}

			$required = $base_required;

			if ( $require_type || ProductType::SIMPLE !== $product_type ) {
				$required[] = 'type';
			}

			$properties = array();

			if ( ! $creating ) {
				$properties['id'] = array(
					'type'    => 'integer',
					'minimum' => 1,
				);
			}

			$properties['type'] = self::get_product_type_schema( array( $product_type ) );

			if ( ProductType::SIMPLE === $product_type && ! $require_type ) {
				$properties['type']['default'] = ProductType::SIMPLE;
			}

			$properties = array_merge(
				$properties,
				self::get_product_mutation_field_schemas_for_fields( $product_config['fields'], ProductType::VARIATION === $product_type, $creating )
			);

			if ( $creating && ProductType::VARIATION === $product_type ) {
				$required   = array_diff( $required, array( 'name' ) );
				$required[] = 'parent_id';
				$required[] = 'attributes';
			}

			$branches[] = array(
				'type'                 => 'object',
				'properties'           => $properties,
				'required'             => array_values( array_unique( $required ) ),
				'additionalProperties' => false,
			);
		}

		return $branches;
	}

	/**
	 * Get the schema of the `attributes` input and output.
	 *
	 * A parent product lists each attribute with its options. A variation lists
	 * the one option it picks for each attribute of its parent.
	 *
	 * @param bool $for_variation Whether the schema is for a variation.
	 * @return array
	 */
	private static function get_product_attributes_schema( bool $for_variation ): array {
		if ( $for_variation ) {
			return array(
				'type'        => 'array',
				'description' => __( 'The option this variation picks for each attribute of its parent, by attribute name.', 'woocommerce' ),
				'items'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'   => array( 'type' => 'string' ),
						'option' => array( 'type' => 'string' ),
					),
					'required'             => array( 'name', 'option' ),
					'additionalProperties' => false,
				),
			);
		}

		return array(
			'type'        => 'array',
			'description' => __( 'Attributes of the product, such as Size with the options S, M and L. Each variation picks one option of every attribute.', 'woocommerce' ),
			'items'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'name'    => array( 'type' => 'string' ),
					'options' => array(
						'type'     => 'array',
						'items'    => array( 'type' => 'string' ),
						'minItems' => 1,
					),
					'visible' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the attribute shows on the product page. Defaults to true.', 'woocommerce' ),
					),
				),
				'required'             => array( 'name', 'options' ),
				'additionalProperties' => false,
			),
		);
	}

	/**
	 * Get the schema of the `variations` input of a variable product. Each
	 * item takes the fields of a variation and, when the feature is on, the
	 * `extensions` input of the product resource. On an update an item with an
	 * `id` changes that variation, an item without one creates a variation,
	 * and `delete` removes one. Variations that the input does not name stay
	 * as they are.
	 *
	 * @param bool $creating Whether the schema is for a create.
	 * @return array
	 */
	private static function get_product_variations_input_schema( bool $creating ): array {
		$properties = array();
		if ( ! $creating ) {
			$properties['id']     = array(
				'type'        => 'integer',
				'description' => __( 'ID of the variation to change. Leave it out to create a variation.', 'woocommerce' ),
				'minimum'     => 1,
			);
			$properties['delete'] = array(
				'type'        => 'boolean',
				'description' => __( 'Delete the variation with this id.', 'woocommerce' ),
			);
		}
		$properties = array_merge( $properties, self::get_product_mutation_field_schemas_for_fields( self::get_variation_config()['fields'], true ) );

		if ( ExtensibleAbilities::is_enabled() ) {
			$properties['extensions'] = ExtensibleAbilities::get_input_schema( 'product' );
		}

		return array(
			'type'        => 'array',
			'description' => $creating
				? __( 'Variations to create with the product. Each one picks one option of every attribute, and no two pick the same options.', 'woocommerce' )
				: __( 'Variations to create, change or delete. An item with an id changes or deletes that variation, an item without one creates a variation. Other variations stay as they are.', 'woocommerce' ),
			'maxItems'    => 100,
			'items'       => array(
				'type'                 => 'object',
				'properties'           => $properties,
				'required'             => $creating ? array( 'attributes' ) : array(),
				'additionalProperties' => false,
			),
		);
	}

	/**
	 * Get the product type schema.
	 *
	 * @param array<int, string> $product_types Product types to allow.
	 * @return array
	 */
	protected static function get_product_type_schema( array $product_types ): array {
		return array(
			'type'        => 'string',
			'description' => __( 'Product type, from the product types that WooCommerce and extensions register.', 'woocommerce' ),
			'enum'        => $product_types,
		);
	}

	/**
	 * Get product mutation field schemas keyed by input field.
	 *
	 * @param bool $for_variation Whether the `attributes` schema is the one of a variation.
	 * @return array<string, array>
	 */
	private static function get_product_mutation_field_schemas( bool $for_variation = false ): array {
		return array(
			'name'              => array( 'type' => 'string' ),
			'parent_id'         => array(
				'type'        => 'integer',
				'description' => __( 'ID of the variable product this variation belongs to.', 'woocommerce' ),
				'minimum'     => 1,
			),
			'attributes'        => self::get_product_attributes_schema( $for_variation ),
			'variations'        => array( 'type' => 'array' ),
			'sku'               => array( 'type' => 'string' ),
			'regular_price'     => array(
				'type'        => 'string',
				'description' => __( 'Decimal price as a string, without a currency symbol or thousand separators.', 'woocommerce' ),
				'pattern'     => self::get_product_price_input_pattern(),
			),
			'sale_price'        => array(
				'type'        => 'string',
				'description' => __( 'Decimal price as a string, without a currency symbol or thousand separators.', 'woocommerce' ),
				'pattern'     => self::get_product_price_input_pattern(),
			),
			'description'       => array(
				'type'        => 'string',
				'description' => __( 'Product description content. Safe HTML is allowed.', 'woocommerce' ),
			),
			'short_description' => array(
				'type'        => 'string',
				'description' => __( 'Short product description content. Safe HTML is allowed.', 'woocommerce' ),
			),
			'status'            => array(
				'type' => 'string',
				'enum' => self::get_product_mutation_status_slugs(),
			),
			'virtual'           => array(
				'type'        => 'boolean',
				'description' => __( 'Whether the product is not shipped.', 'woocommerce' ),
			),
			'downloadable'      => array(
				'type'        => 'boolean',
				'description' => __( 'Whether the product gives the customer files to download.', 'woocommerce' ),
			),
			'manage_stock'      => array( 'type' => 'boolean' ),
			'stock_quantity'    => array(
				'type'        => self::get_product_stock_quantity_schema_type(),
				'description' => __( 'Available stock quantity when product-level stock management is used.', 'woocommerce' ),
			),
			'stock_status'      => array(
				'type' => 'string',
				'enum' => array_keys( wc_get_product_stock_status_options() ),
			),
			'external_url'      => array(
				'type'        => 'string',
				'description' => __( 'External destination URL for external products.', 'woocommerce' ),
				'format'      => 'uri',
			),
			'button_text'       => array(
				'type'        => 'string',
				'description' => __( 'Button text for external products.', 'woocommerce' ),
			),
			'grouped_products'  => array(
				'type'        => 'array',
				'description' => __( 'Product IDs to include as children of a grouped product.', 'woocommerce' ),
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		);
	}

	/**
	 * Get product mutation field schemas for a set of fields.
	 *
	 * @param array<int, string> $fields        Field names.
	 * @param bool               $for_variation Whether the `attributes` schema is the one of a variation.
	 * @param bool               $creating      Whether the `variations` schema is the one of a create.
	 * @return array<string, array>
	 */
	private static function get_product_mutation_field_schemas_for_fields( array $fields, bool $for_variation = false, bool $creating = false ): array {
		$schemas = self::get_product_mutation_field_schemas( $for_variation );
		$result  = array();

		foreach ( $schemas as $field => $schema ) {
			if ( in_array( $field, $fields, true ) ) {
				$result[ $field ] = 'variations' === $field ? self::get_product_variations_input_schema( $creating ) : $schema;
			}
		}

		return $result;
	}

	/**
	 * Product types that the mutation abilities support: every registered type
	 * and `variation`, which WooCommerce does not list as a type.
	 *
	 * @return array<int, string>
	 */
	protected static function get_supported_product_types(): array {
		return array_merge( array_map( 'strval', array_keys( wc_get_product_types() ) ), array( ProductType::VARIATION ) );
	}

	/**
	 * The fields of a variation, as an item of the `variations` input of its
	 * parent. As a product of its own it also takes `parent_id` on create.
	 *
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}
	 */
	protected static function get_variation_config(): array {
		return array(
			'fields'        => array( 'sku', 'description', 'status', 'virtual', 'downloadable', 'attributes', 'regular_price', 'sale_price', 'manage_stock', 'stock_quantity', 'stock_status' ),
			'product_props' => array(),
		);
	}

	/**
	 * Get the configuration of a product type.
	 *
	 * The product class decides which Core fields apply. A type whose class
	 * extends a Core class gets the fields of that class. A variable type
	 * accepts `attributes` and `variations`, and a variation accepts
	 * `parent_id` on create.
	 *
	 * @param string $product_type Product type.
	 * @param bool   $creating     Whether the fields are for a create.
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}|\WP_Error
	 */
	protected static function get_product_type_config( string $product_type, bool $creating = false ) {
		if ( ! in_array( $product_type, self::get_supported_product_types(), true ) ) {
			return new \WP_Error(
				'woocommerce_product_type_unsupported',
				__( 'Product type is not supported by this ability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$classname     = \WC_Product_Factory::get_product_classname( 0, $product_type );
		$common_fields = self::get_common_product_mutation_fields();
		$price_fields  = array( 'regular_price', 'sale_price' );
		$stock_fields  = array( 'manage_stock', 'stock_quantity', 'stock_status' );

		if ( is_a( $classname, \WC_Product_Variation::class, true ) ) {
			$config = self::get_variation_config();
			if ( $creating ) {
				$config['fields'][] = 'parent_id';
			}
			return $config;
		}

		if ( is_a( $classname, \WC_Product_Variable::class, true ) ) {
			return array(
				'fields'        => array_merge( $common_fields, array( 'attributes', 'variations' ) ),
				'product_props' => array(),
			);
		}

		if ( is_a( $classname, \WC_Product_External::class, true ) ) {
			return array(
				'fields'        => array_merge( $common_fields, $price_fields, array( 'external_url', 'button_text' ) ),
				'product_props' => array(
					'virtual'        => false,
					'downloadable'   => false,
					'manage_stock'   => false,
					'stock_quantity' => '',
					'stock_status'   => ProductStockStatus::IN_STOCK,
				),
			);
		}

		if ( is_a( $classname, \WC_Product_Grouped::class, true ) ) {
			return array(
				'fields'        => array_merge( $common_fields, array( 'grouped_products' ) ),
				'product_props' => array(
					'manage_stock'   => false,
					'stock_quantity' => '',
				),
			);
		}

		$fields = array_merge( $common_fields, array( 'virtual', 'downloadable' ) );

		if ( is_a( $classname, \WC_Product_Simple::class, true ) ) {
			$fields = array_merge( $fields, $price_fields, $stock_fields );
		}

		return array(
			'fields'        => $fields,
			'product_props' => array(),
		);
	}

	/**
	 * Get the product object of a product type.
	 *
	 * @param string $product_type Product type.
	 * @param int    $product_id   Product ID, or 0 for a new product.
	 * @return \WC_Product|\WP_Error
	 */
	protected static function get_product_object_for_type( string $product_type, int $product_id = 0 ) {
		$product = wc_get_product_object( $product_type, $product_id );

		if ( ! $product || $product->get_type() !== $product_type ) {
			return new \WP_Error(
				'woocommerce_invalid_product_type',
				__( 'Invalid product type.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $product;
	}

	/**
	 * Get the configuration for a product object.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param bool        $creating Whether the fields are for a create.
	 * @return array|\WP_Error
	 */
	protected static function get_product_config_for_product( \WC_Product $product, bool $creating = false ) {
		return self::get_product_type_config( $product->get_type(), $creating );
	}

	/**
	 * Get fields shared by all supported product types.
	 *
	 * @return array<int, string>
	 */
	private static function get_common_product_mutation_fields(): array {
		return array( 'name', 'sku', 'description', 'short_description', 'status' );
	}

	/**
	 * Allowed product post-status slugs for mutation abilities.
	 *
	 * Mirrors the REST products controller mutation enum.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_mutation_status_slugs(): array {
		return array_merge(
			array_keys( get_post_statuses() ),
			array( ProductStatus::FUTURE )
		);
	}

	/**
	 * Allowed product post-status slugs for product output.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_output_status_slugs(): array {
		return array_values(
			array_unique(
				array_merge(
					self::get_product_mutation_status_slugs(),
					array( ProductStatus::AUTO_DRAFT, ProductStatus::TRASH )
				)
			)
		);
	}

	/**
	 * Allowed product post-status slugs for query abilities.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_query_status_slugs(): array {
		return array_merge(
			array( ProductStatus::FUTURE, ProductStatus::TRASH ),
			array_keys( get_post_statuses() )
		);
	}

	/**
	 * Get a product from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	protected static function get_product_from_input( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_product_id_required',
				__( 'Product ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product_id = (int) $input['id'];

		if ( $product_id < 1 ) {
			return new \WP_Error(
				'woocommerce_product_id_required',
				__( 'Product ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return new \WP_Error(
				'woocommerce_product_not_found',
				__( 'Product not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $product;
	}

	/**
	 * Set supported product properties from ability input.
	 *
	 * @param \WC_Product $product        Product object.
	 * @param array       $input          Ability input.
	 * @param array       $product_config Product type configuration.
	 * @return null|\WP_Error
	 */
	protected static function set_product_props_from_input( \WC_Product $product, array $input, array $product_config ) {
		$validation_error = self::validate_product_fields_for_config( $input, $product_config );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$setters = array(
			'parent_id'         => 'set_parent_id',
			'name'              => 'set_name',
			'sku'               => 'set_sku',
			'regular_price'     => 'set_regular_price',
			'sale_price'        => 'set_sale_price',
			'description'       => 'set_description',
			'short_description' => 'set_short_description',
			'status'            => 'set_status',
			'virtual'           => 'set_virtual',
			'downloadable'      => 'set_downloadable',
			'manage_stock'      => 'set_manage_stock',
			'stock_quantity'    => 'set_stock_quantity',
			'stock_status'      => 'set_stock_status',
			'external_url'      => 'set_product_url',
			'button_text'       => 'set_button_text',
			'grouped_products'  => 'set_children',
			'attributes'        => 'set_attributes',
		);

		foreach ( $setters as $field => $setter ) {
			if (
				! in_array( $field, $product_config['fields'], true )
				|| ! array_key_exists( $field, $input )
				|| ! is_callable( array( $product, $setter ) )
			) {
				continue;
			}

			$prepared_value = 'attributes' === $field
				? self::prepare_product_attributes( $product, $input[ $field ] )
				: self::prepare_product_field_value( $field, $input[ $field ] );
			if ( is_wp_error( $prepared_value ) ) {
				return $prepared_value;
			}

			$product->{$setter}( $prepared_value );
		}

		if ( $product instanceof \WC_Product_Variation && array_key_exists( 'attributes', $input ) && $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( ! $parent instanceof \WC_Product_Variable ) {
				return new \WP_Error(
					'woocommerce_product_parent_invalid',
					__( 'The parent must be a variable product.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
			return self::check_variation_attributes( $product->get_attributes(), $parent );
		}

		return null;
	}

	/**
	 * Sync the parent of a saved variation, so its price range and stock
	 * status follow the variation.
	 *
	 * @param \WC_Product $product Saved product.
	 */
	protected static function sync_variation_parent( \WC_Product $product ): void {
		if ( $product instanceof \WC_Product_Variation && $product->get_parent_id() > 0 ) {
			\WC_Product_Variable::sync( $product->get_parent_id() );
		}
	}

	/**
	 * Build the variations that a `variations` input names, in memory. An item
	 * with an `id` loads that variation of the parent and changes it, or marks
	 * it for deletion. An item without one builds a new variation. Each saved
	 * variation must pick one option of every attribute of the parent, and no
	 * two variations of the parent may pick the same options or carry the same
	 * SKU. The database SKU check runs on each save and does not see the other
	 * new variations, so the call checks them here.
	 *
	 * @param \WC_Product_Variable $parent_product Parent, with its attributes set. Saved or not.
	 * @param array                $items          Items of the `variations` input.
	 * @return array{save: \WC_Product_Variation[], delete: \WC_Product_Variation[], extensions: array<int, array>}|\WP_Error
	 * @throws \WC_Data_Exception When a variation setter rejects a value.
	 */
	protected static function plan_variations( \WC_Product_Variable $parent_product, array $items ) {
		$config = self::get_variation_config();
		$plan   = array(
			'save'       => array(),
			'delete'     => array(),
			'extensions' => array(),
		);
		$named  = array();

		foreach ( array_values( $items ) as $index => $item ) {
			$position = $index + 1;
			$item     = is_array( $item ) ? $item : array();
			$id       = (int) ( $item['id'] ?? 0 );

			if ( $id > 0 ) {
				$variation = wc_get_product( $id );
				if ( ! $variation instanceof \WC_Product_Variation || $variation->get_parent_id() !== $parent_product->get_id() ) {
					return new \WP_Error(
						'woocommerce_product_variation_not_found',
						/* translators: %d: variation position. */
						sprintf( __( 'Variation %d is not a variation of this product.', 'woocommerce' ), $position ),
						array( 'status' => 404 )
					);
				}
				$named[ $id ] = true;
				if ( ! empty( $item['delete'] ) ) {
					$plan['delete'][] = $variation;
					continue;
				}
			} else {
				$variation = self::get_product_object_for_type( ProductType::VARIATION );
				if ( is_wp_error( $variation ) ) {
					return $variation;
				}
				if ( ! $variation->get_status( 'edit' ) ) {
					$variation->set_status( ProductStatus::PUBLISH );
				}
			}

			$error = self::set_product_props_from_input( $variation, array_diff_key( $item, array_flip( array( 'id', 'delete', 'extensions' ) ) ), $config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$error = self::check_variation_attributes( $variation->get_attributes(), $parent_product, $position );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$plan['save'][]       = $variation;
			$plan['extensions'][] = is_array( $item['extensions'] ?? null ) ? $item['extensions'] : array();
		}

		$combinations = array();
		$skus         = array();
		foreach ( $parent_product->get_children() as $child_id ) {
			$child = isset( $named[ $child_id ] ) ? null : wc_get_product( $child_id );
			if ( $child instanceof \WC_Product_Variation ) {
				$combinations[ self::get_variation_combination( $child ) ] = true;
				$skus[ $child->get_sku( 'edit' ) ]                         = true;
			}
		}
		foreach ( $plan['save'] as $index => $variation ) {
			$position    = $index + 1;
			$combination = self::get_variation_combination( $variation );
			if ( isset( $combinations[ $combination ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_duplicate',
					/* translators: %d: variation position. */
					sprintf( __( 'Variation %d picks the same options as another variation.', 'woocommerce' ), $position ),
					array( 'status' => 400 )
				);
			}
			$combinations[ $combination ] = true;

			$sku = $variation->get_sku( 'edit' );
			if ( '' !== $sku && isset( $skus[ $sku ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_sku_duplicate',
					/* translators: %d: variation position. */
					sprintf( __( 'Variation %d repeats the SKU of another variation.', 'woocommerce' ), $position ),
					array( 'status' => 400 )
				);
			}
			$skus[ $sku ] = true;
		}

		return $plan;
	}

	/**
	 * The options a variation picks, as one string to compare variations with.
	 *
	 * @param \WC_Product_Variation $variation Variation.
	 * @return string
	 */
	private static function get_variation_combination( \WC_Product_Variation $variation ): string {
		$attributes = $variation->get_attributes();
		ksort( $attributes );
		return (string) wp_json_encode( $attributes );
	}

	/**
	 * Save the planned variations of a saved parent, delete the ones marked
	 * for deletion, then sync the parent with its variations. A failed save
	 * returns an error, and the transaction of the pipeline rolls back the
	 * earlier saves.
	 *
	 * @param \WC_Product_Variable $parent_product Saved parent.
	 * @param array                $plan           The plan from plan_variations().
	 * @param string               $failure_code   Error code to use when a save fails.
	 * @return null|\WP_Error
	 */
	protected static function save_variations( \WC_Product_Variable $parent_product, array $plan, string $failure_code ) {
		foreach ( $plan['save'] as $variation ) {
			$variation->set_parent_id( $parent_product->get_id() );
			$error = self::save_product( $variation, $failure_code );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
		}

		foreach ( $plan['delete'] as $variation ) {
			if ( ! $variation->delete( true ) ) {
				return new \WP_Error( $failure_code, __( 'Failed to delete a variation.', 'woocommerce' ), array( 'status' => 500 ) );
			}
		}

		if ( empty( $plan['save'] ) && empty( $plan['delete'] ) ) {
			return null;
		}

		$children = $parent_product->get_data_store()->read_children( $parent_product, true );
		$parent_product->set_children( $children['all'] );
		$parent_product->set_visible_children( $children['visible'] );
		\WC_Product_Variable::sync( $parent_product );
		$parent_product->set_price( (string) get_post_meta( $parent_product->get_id(), '_price', true ) );

		return null;
	}

	/**
	 * Build the attributes of a product from input. A parent gets
	 * WC_Product_Attribute objects. A variation gets the option it picks for each
	 * attribute, keyed by the attribute slug.
	 *
	 * @param \WC_Product $product Product object.
	 * @param mixed       $value   The `attributes` input.
	 * @return array|\WP_Error
	 */
	private static function prepare_product_attributes( \WC_Product $product, $value ) {
		if ( ! is_array( $value ) ) {
			return self::get_invalid_product_attributes_error();
		}

		if ( $product instanceof \WC_Product_Variation ) {
			$attributes = array();
			foreach ( $value as $item ) {
				if ( ! is_array( $item ) || ! is_string( $item['name'] ?? null ) || '' === $item['name'] || ! is_scalar( $item['option'] ?? null ) ) {
					return self::get_invalid_product_attributes_error();
				}
				$attributes[ sanitize_title( $item['name'] ) ] = wc_clean( (string) $item['option'] );
			}
			return $attributes;
		}

		$attributes = array();
		foreach ( $value as $position => $item ) {
			$options = is_array( $item['options'] ?? null ) ? array_values( array_filter( array_map( 'wc_clean', $item['options'] ), 'is_string' ) ) : array();
			if ( ! is_array( $item ) || ! is_string( $item['name'] ?? null ) || '' === trim( $item['name'] ) || empty( $options ) ) {
				return self::get_invalid_product_attributes_error();
			}
			$attribute = new \WC_Product_Attribute();
			$attribute->set_name( wc_clean( $item['name'] ) );
			$attribute->set_options( $options );
			$attribute->set_position( (int) $position );
			$attribute->set_visible( (bool) ( $item['visible'] ?? true ) );
			$attribute->set_variation( true );
			$attributes[ sanitize_title( $item['name'] ) ] = $attribute;
		}

		if ( count( $attributes ) !== count( $value ) ) {
			return new \WP_Error(
				'woocommerce_product_attributes_invalid',
				__( 'Two attributes have the same name.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $attributes;
	}

	/**
	 * Check that a variation picks one option of every variation attribute of
	 * its parent, and nothing else.
	 *
	 * @param array<string, string> $attributes     Attributes of the variation, keyed by attribute slug.
	 * @param \WC_Product           $parent_product Parent product, saved or not.
	 * @param int                   $position       Position of the variation in the input, for the message. 0 for a single variation.
	 * @return null|\WP_Error
	 */
	protected static function check_variation_attributes( array $attributes, \WC_Product $parent_product, int $position = 0 ) {
		$options = array();
		foreach ( $parent_product->get_attributes() as $slug => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute && $attribute->get_variation() ) {
				$options[ $slug ] = $attribute->get_slugs();
			}
		}

		$missing = array_diff_key( $options, $attributes );
		if ( empty( $options ) || ! empty( $missing ) || count( $attributes ) !== count( $options ) ) {
			return new \WP_Error(
				'woocommerce_product_variation_attributes_invalid',
				sprintf(
					/* translators: 1: variation position, 2: comma-separated attribute names. */
					__( 'Variation %1$d must pick one option of each of these attributes: %2$s.', 'woocommerce' ),
					$position,
					implode( ', ', array_keys( $options ) )
				),
				array( 'status' => 400 )
			);
		}

		foreach ( $attributes as $slug => $option ) {
			if ( ! in_array( $option, $options[ $slug ], true ) && ! in_array( sanitize_title( $option ), $options[ $slug ], true ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_attributes_invalid',
					sprintf(
						/* translators: 1: variation position, 2: attribute name, 3: option. */
						__( 'Variation %1$d picks the option "%3$s" that the attribute "%2$s" does not have.', 'woocommerce' ),
						$position,
						$slug,
						$option
					),
					array( 'status' => 400 )
				);
			}
		}

		return null;
	}

	/**
	 * Get the error for an `attributes` input of the wrong shape.
	 *
	 * @return \WP_Error
	 */
	private static function get_invalid_product_attributes_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_product_attributes_invalid',
			__( 'Each attribute needs a name and, on a parent product, at least one option, or, on a variation, one option.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Apply product type configuration to a product object.
	 *
	 * @param \WC_Product $product        Product object.
	 * @param array       $product_config Product type configuration.
	 */
	protected static function apply_product_type_config( \WC_Product $product, array $product_config ): void {
		$prop_setters = array(
			'virtual'        => 'set_virtual',
			'downloadable'   => 'set_downloadable',
			'manage_stock'   => 'set_manage_stock',
			'stock_quantity' => 'set_stock_quantity',
			'stock_status'   => 'set_stock_status',
		);

		foreach ( $product_config['product_props'] as $prop => $value ) {
			$setter = $prop_setters[ $prop ] ?? '';

			if ( '' !== $setter && is_callable( array( $product, $setter ) ) ) {
				$product->{$setter}( $value );
			}
		}
	}

	/**
	 * Validate that input fields are supported for the configured product type.
	 *
	 * @param array $input          Ability input.
	 * @param array $product_config Product type configuration.
	 * @return null|\WP_Error
	 */
	private static function validate_product_fields_for_config( array $input, array $product_config ) {
		$shared_fields          = array( 'id', 'type', 'extensions' );
		$supported_fields       = array_merge( $shared_fields, $product_config['fields'] );
		$unsupported_field_keys = array_diff( array_keys( $input ), $supported_fields );

		if ( empty( $unsupported_field_keys ) ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_product_field_unsupported',
			sprintf(
				/* translators: %s is a comma-separated list of unsupported product fields. */
				__( 'These fields are not supported for the selected product type: %s.', 'woocommerce' ),
				implode( ', ', $unsupported_field_keys )
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Prepare a product field value before passing it to the product setter.
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return mixed|\WP_Error
	 */
	private static function prepare_product_field_value( string $field, $value ) {
		if ( in_array( $field, array( 'name', 'description', 'short_description' ), true ) && is_string( $value ) ) {
			return wp_filter_post_kses( $value );
		}

		if ( 'status' === $field && is_string( $value ) ) {
			return sanitize_key( $value );
		}

		if ( 'external_url' === $field && is_string( $value ) ) {
			return esc_url_raw( $value );
		}

		if ( in_array( $field, array( 'regular_price', 'sale_price' ), true ) ) {
			return self::prepare_product_price_value( $value );
		}

		if ( 'grouped_products' === $field ) {
			if ( ! is_array( $value ) ) {
				return new \WP_Error(
					'woocommerce_product_grouped_products_invalid',
					__( 'Grouped product IDs must be positive integers.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$grouped_product_ids = array();

			foreach ( $value as $product_id ) {
				if ( ! rest_is_integer( $product_id ) || (int) $product_id < 1 ) {
					return new \WP_Error(
						'woocommerce_product_grouped_products_invalid',
						__( 'Grouped product IDs must be positive integers.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				$grouped_product_ids[] = (int) $product_id;
			}

			return $grouped_product_ids;
		}

		if ( is_string( $value ) ) {
			return wc_clean( $value );
		}

		return $value;
	}

	/**
	 * Prepare a product price before passing it to the product setter.
	 *
	 * @param mixed $value Price value.
	 * @return string|\WP_Error
	 */
	private static function prepare_product_price_value( $value ) {
		if ( ! is_scalar( $value ) || is_bool( $value ) ) {
			return self::get_invalid_product_price_error();
		}

		$value = trim( (string) $value );

		if ( 1 !== preg_match( '/' . self::get_product_price_input_pattern() . '/', $value ) ) {
			return self::get_invalid_product_price_error();
		}

		return wc_format_decimal( $value );
	}

	/**
	 * Get a schema-compatible pattern for product price inputs.
	 *
	 * @return string
	 */
	private static function get_product_price_input_pattern(): string {
		$decimal_separators        = array_map(
			static function ( string $separator ): string {
				return preg_quote( $separator, '/' );
			},
			self::get_product_price_decimal_separators()
		);
		$decimal_separator_pattern = implode( '', $decimal_separators );

		return '^(?:-?(?:[0-9]+(?:[' . $decimal_separator_pattern . '][0-9]+)?|[' . $decimal_separator_pattern . '][0-9]+)|)$';
	}

	/**
	 * Get decimal separators accepted by WooCommerce decimal formatting.
	 *
	 * @return array<int, string>
	 */
	private static function get_product_price_decimal_separators(): array {
		$locale = localeconv();

		return array_values(
			array_unique(
				array_filter(
					array(
						wc_get_price_decimal_separator(),
						$locale['decimal_point'] ?? '',
						$locale['mon_decimal_point'] ?? '',
					),
					static function ( $separator ): bool {
						return is_string( $separator ) && '' !== $separator;
					}
				)
			)
		);
	}

	/**
	 * Get an invalid product price error.
	 *
	 * @return \WP_Error
	 */
	private static function get_invalid_product_price_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_product_price_invalid',
			__( 'Product prices must be decimal strings without currency symbols or thousand separators.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Convert a WooCommerce data exception to a WordPress error.
	 *
	 * @param \WC_Data_Exception $exception Exception.
	 * @return \WP_Error
	 */
	protected static function get_product_data_exception_error( \WC_Data_Exception $exception ): \WP_Error {
		return new \WP_Error(
			$exception->getErrorCode(),
			$exception->getMessage(),
			$exception->getErrorData()
		);
	}

	/**
	 * Save a product and return a failure if WooCommerce did not persist it.
	 *
	 * @param \WC_Product $product      Product object.
	 * @param string      $failure_code Error code to use when save returns no ID.
	 * @return null|\WP_Error
	 */
	protected static function save_product( \WC_Product $product, string $failure_code ) {
		try {
			$product_id = $product->save();
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		} catch ( \Exception $exception ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					'WooCommerce domain ability failed to save product.',
					array(
						'source'        => 'woocommerce-abilities',
						'failure_code'  => $failure_code,
						'product_id'    => $product->get_id(),
						'exception'     => get_class( $exception ),
						'error_message' => $exception->getMessage(),
					)
				);
			}

			return new \WP_Error(
				$failure_code,
				__( 'Failed to save product.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		if ( $product_id <= 0 || $product->get_id() <= 0 ) {
			return new \WP_Error(
				$failure_code,
				__( 'Failed to save product.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return null;
	}

	/**
	 * Format a product for ability output.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	protected static function format_product_for_response( \WC_Product $product ): array {
		$stock_quantity = $product->get_stock_quantity();
		$permalink      = $product->get_permalink();

		$data = array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'permalink'         => false === $permalink ? null : $permalink,
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'sku'               => $product->get_sku(),
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => html_entity_decode(
				get_woocommerce_currency_symbol(),
				ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
			),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'stock_status'      => $product->get_stock_status(),
			'stock_quantity'    => null === $stock_quantity ? null : wc_stock_amount( $stock_quantity ),
			'manage_stock'      => (bool) $product->get_manage_stock(),
			'virtual'           => (bool) $product->get_virtual(),
			'downloadable'      => (bool) $product->get_downloadable(),
			'external_url'      => $product instanceof \WC_Product_External ? $product->get_product_url() : null,
			'button_text'       => $product instanceof \WC_Product_External ? $product->get_button_text() : null,
			'grouped_products'  => $product instanceof \WC_Product_Grouped ? array_map( 'absint', $product->get_children( 'edit' ) ) : array(),
			'parent_id'         => (int) $product->get_parent_id(),
			'attributes'        => self::format_product_attributes( $product ),
			'variation_ids'     => $product instanceof \WC_Product_Variable ? array_map( 'absint', $product->get_children() ) : array(),
			'date_created'      => wc_rest_prepare_date_response( $product->get_date_created(), false ),
			'date_created_gmt'  => wc_rest_prepare_date_response( $product->get_date_created() ),
			'date_modified'     => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
			'date_modified_gmt' => wc_rest_prepare_date_response( $product->get_date_modified() ),
		);

		return ExtensibleAbilities::add_fields_to_object( $data, 'product', $product );
	}

	/**
	 * Format the attributes of a product for output, in the shape of the
	 * `attributes` input: options on a parent, one option on a variation.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<int, array<string, mixed>>
	 */
	private static function format_product_attributes( \WC_Product $product ): array {
		$output = array();

		foreach ( $product->get_attributes() as $slug => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$output[] = array(
					'name'    => wc_attribute_label( $attribute->get_name(), $product ),
					'options' => array_map( 'strval', $attribute->get_slugs() ),
					'visible' => (bool) $attribute->get_visible(),
				);
			} else {
				$output[] = array(
					'name'   => wc_attribute_label( (string) $slug, $product ),
					'option' => (string) $attribute,
				);
			}
		}

		return $output;
	}

	/**
	 * Get the schema for a single product in a response.
	 *
	 * @return array
	 */
	protected static function get_product_output_schema(): array {
		return ExtensibleAbilities::add_fields_schema( self::get_single_product_output_schema(), 'product' );
	}

	/**
	 * Get the schema of a product in a response.
	 *
	 * @return array
	 */
	private static function get_single_product_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                => array( 'type' => 'integer' ),
				'name'              => array( 'type' => 'string' ),
				'slug'              => array( 'type' => 'string' ),
				'permalink'         => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Product permalink, or null when no public permalink is available.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'type'              => array(
					'type'        => 'string',
					'description' => __( 'Internal product type slug, such as simple, external, grouped, variable or variation.', 'woocommerce' ),
					'enum'        => self::get_supported_product_types(),
				),
				'status'            => array(
					'type' => 'string',
					'enum' => self::get_product_output_status_slugs(),
				),
				'sku'               => array( 'type' => 'string' ),
				'currency'          => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'currency_symbol'   => array( 'type' => 'string' ),
				'price'             => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'regular_price'     => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'sale_price'        => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'stock_status'      => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'stock_quantity'    => array(
					'type'        => array( self::get_product_stock_quantity_schema_type(), 'null' ),
					'description' => __( 'Current stock quantity, or null when no stock quantity is set.', 'woocommerce' ),
				),
				'manage_stock'      => array( 'type' => 'boolean' ),
				'virtual'           => array( 'type' => 'boolean' ),
				'downloadable'      => array( 'type' => 'boolean' ),
				'external_url'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'External product URL for external products.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'button_text'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Button text for external products.', 'woocommerce' ),
				),
				'grouped_products'  => array(
					'type'        => 'array',
					'description' => __( 'Product IDs included as children of a grouped product.', 'woocommerce' ),
					'items'       => array( 'type' => 'integer' ),
				),
				'parent_id'         => array(
					'type'        => 'integer',
					'description' => __( 'ID of the variable product of a variation, or 0.', 'woocommerce' ),
				),
				'attributes'        => array(
					'type'        => 'array',
					'description' => __( 'Attributes with their options on a product, or the option each attribute picks on a variation.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'    => array( 'type' => 'string' ),
							'options' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'option'  => array( 'type' => 'string' ),
							'visible' => array( 'type' => 'boolean' ),
						),
						'required'             => array( 'name' ),
						'additionalProperties' => false,
					),
				),
				'variation_ids'     => array(
					'type'        => 'array',
					'description' => __( 'IDs of the variations of a variable product.', 'woocommerce' ),
					'items'       => array( 'type' => 'integer' ),
				),
				'date_created'      => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_created_gmt'  => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_modified'     => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_modified_gmt' => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the schema type for product stock quantities.
	 *
	 * WooCommerce stock quantities can support fractional values when the
	 * stock amount filter is configured to return non-integer amounts.
	 *
	 * @return string
	 */
	protected static function get_product_stock_quantity_schema_type(): string {
		return wc_is_stock_amount_integer() ? 'integer' : 'number';
	}
}
