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
		return self::get_product_mutation_input_schema( true );
	}

	/**
	 * Get the product update input schema.
	 *
	 * @return array
	 */
	protected static function get_product_update_input_schema(): array {
		return self::get_product_mutation_input_schema( false );
	}

	/**
	 * Get the input schema of product-create or product-update.
	 *
	 * The schema is one flat object with the fields of every supported product
	 * type. The ability refuses a field that the type does not take with
	 * `woocommerce_product_field_unsupported`, naming the fields it takes.
	 *
	 * @param bool $creating Whether the schema is for product-create.
	 * @return array
	 */
	private static function get_product_mutation_input_schema( bool $creating ): array {
		$configs     = self::get_supported_product_type_configs( $creating );
		$fields      = array_merge( ...array_values( array_column( $configs, 'fields' ) ) );
		$type_schema = self::get_product_type_schema( array_keys( $configs ) );

		if ( $creating ) {
			$type_schema['default']      = ProductType::SIMPLE;
			$type_schema['description'] .= ' ' . __( 'A product without a type is simple.', 'woocommerce' );
		}

		$properties = $creating ? array() : array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);

		return array(
			'type'                 => 'object',
			'properties'           => array_merge(
				$properties,
				array( 'type' => $type_schema ),
				self::get_product_mutation_field_schemas_for_fields( $fields, false, $creating ),
				$creating && in_array( 'attributes', $fields, true ) ? array( 'attributes' => self::get_product_create_attributes_schema() ) : array()
			),
			'additionalProperties' => false,
		) + ( $creating ? array() : array( 'required' => array( 'id' ) ) );
	}

	/**
	 * Get the schema of the `attributes` input of a create. A variable product
	 * takes attributes with their options, and a variation takes the option it
	 * picks for each attribute. The ability checks the shape against the type.
	 * An update takes the parent shape only: a variation's options change
	 * through the `variations` input of its parent.
	 *
	 * @return array
	 */
	private static function get_product_create_attributes_schema(): array {
		return array(
			'type'        => 'array',
			'description' => __( 'On a variable product, the attributes with their options. On a variation, the option it picks for each attribute of its parent.', 'woocommerce' ),
			'items'       => array(
				'anyOf' => array(
					self::get_product_attributes_schema( false )['items'],
					self::get_product_attributes_schema( true )['items'],
				),
			),
		);
	}

	/**
	 * Get the schema of the `attributes` input and output.
	 *
	 * A parent product lists each attribute with its options. A variation lists
	 * the one option it picks for each attribute of its parent. An attribute
	 * with `global: true` or an `id` is a global attribute.
	 *
	 * @param bool $for_variation Whether the schema is for a variation.
	 * @return array
	 */
	private static function get_product_attributes_schema( bool $for_variation ): array {
		if ( $for_variation ) {
			return array(
				'type'        => 'array',
				'description' => __( 'The option this variation picks for each attribute of its parent, by attribute name or global attribute id. For a global attribute the option is the term slug. A null option matches any option of the attribute.', 'woocommerce' ),
				'items'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'     => self::get_global_attribute_id_schema(),
						'name'   => array( 'type' => 'string' ),
						'option' => array( 'type' => array( 'string', 'null' ) ),
					),
					'required'             => array( 'option' ),
					'additionalProperties' => false,
				),
			);
		}

		return array(
			'type'        => 'array',
			'description' => __( 'Attributes of the product, such as Size with the options S, M and L. Each variation picks one option of every attribute that is for variations. A name is an attribute of this product only, unless global is true or an id names a global attribute. The options of a global attribute are term names or slugs, and missing terms are created.', 'woocommerce' ),
			'items'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => self::get_global_attribute_id_schema(),
					'name'      => array( 'type' => 'string' ),
					'global'    => array(
						'type'        => 'boolean',
						'description' => __( 'Use the global attribute with this name. The input is refused when there is none. Defaults to false.', 'woocommerce' ),
					),
					'options'   => array(
						'type'     => 'array',
						'items'    => array( 'type' => 'string' ),
						'minItems' => 1,
					),
					'visible'   => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the attribute shows on the product page. Defaults to true.', 'woocommerce' ),
					),
					'variation' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the variations pick an option of this attribute. Defaults to true. An attribute that is not for variations only shows on the product page.', 'woocommerce' ),
					),
				),
				'required'             => array( 'options' ),
				'additionalProperties' => false,
			),
		);
	}

	/**
	 * Get the schema of the `id` of an attribute.
	 *
	 * @return array
	 */
	private static function get_global_attribute_id_schema(): array {
		return array(
			'type'        => 'integer',
			'description' => __( 'ID of a global attribute. 0 or absent for an attribute of this product only.', 'woocommerce' ),
			'minimum'     => 0,
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
			$properties['extensions'] = ExtensibleAbilities::get_input_schema( 'product', array( self::class, 'field_applies_to_variations' ) );
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
			'name'              => array(
				'type'        => 'string',
				'description' => __( 'Required, except on a variation, which takes its name from its parent.', 'woocommerce' ),
			),
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
				'enum' => $for_variation ? self::get_variation_status_slugs() : self::get_product_mutation_status_slugs(),
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
	 * with a class of its own, and `variation`, which WooCommerce does not list
	 * as a type.
	 *
	 * @return array<int, string>
	 */
	protected static function get_supported_product_types(): array {
		return array_keys( self::get_supported_product_type_configs() );
	}

	/**
	 * Get the configuration of every supported product type, keyed by type.
	 *
	 * @param bool $creating Whether the fields are for a create.
	 * @return array<string, array{fields: array<int, string>, product_props: array<string, mixed>}>
	 */
	private static function get_supported_product_type_configs( bool $creating = false ): array {
		$configs = array();

		foreach ( array_merge( array_keys( wc_get_product_types() ), array( ProductType::VARIATION ) ) as $product_type ) {
			$product_type = (string) $product_type;
			$config       = self::get_product_class_config( \WC_Product_Factory::get_product_classname( 0, $product_type ), $product_type, $creating );

			if ( ! is_wp_error( $config ) ) {
				$configs[ $product_type ] = $config;
			}
		}

		return $configs;
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
	 * @param string $product_type Product type.
	 * @param bool   $creating     Whether the fields are for a create.
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}|\WP_Error
	 */
	protected static function get_product_type_config( string $product_type, bool $creating = false ) {
		if ( ProductType::VARIATION !== $product_type && ! isset( wc_get_product_types()[ $product_type ] ) ) {
			return self::get_unsupported_product_type_error();
		}

		return self::get_product_class_config( \WC_Product_Factory::get_product_classname( 0, $product_type ), $product_type, $creating );
	}

	/**
	 * Get the configuration of a product class.
	 *
	 * The product class decides which Core fields apply. A class that extends
	 * a Core class gets the fields of that class. A variable type accepts
	 * `attributes` and `variations`. A variation accepts `parent_id` and
	 * `attributes` on create only: on update its options change through the
	 * `variations` input of its parent. The owner of a type changes the list
	 * with the `woocommerce_ability_product_type_fields` filter.
	 *
	 * `WC_Product_Factory` returns `WC_Product_Simple` itself for a type that
	 * has no class. Such a type is refused. A class that extends
	 * `WC_Product_Simple`, such as a subscription, is a class of its own.
	 *
	 * @param string $classname    Product class.
	 * @param string $product_type Product type.
	 * @param bool   $creating     Whether the fields are for a create.
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}|\WP_Error
	 */
	private static function get_product_class_config( string $classname, string $product_type, bool $creating = false ) {
		if ( \WC_Product_Simple::class === $classname && ProductType::SIMPLE !== $product_type ) {
			return self::get_invalid_product_type_error();
		}

		$fields        = self::get_common_product_mutation_fields();
		$product_props = array();
		$price_fields  = array( 'regular_price', 'sale_price' );
		$stock_fields  = array( 'manage_stock', 'stock_quantity', 'stock_status' );

		if ( is_a( $classname, \WC_Product_Variation::class, true ) ) {
			$fields = self::get_variation_config()['fields'];
			if ( $creating ) {
				$fields[] = 'parent_id';
			} else {
				$fields = array_values( array_diff( $fields, array( 'attributes' ) ) );
			}
		} elseif ( is_a( $classname, \WC_Product_Variable::class, true ) ) {
			$fields = array_merge( $fields, array( 'attributes', 'variations' ) );
		} elseif ( is_a( $classname, \WC_Product_External::class, true ) ) {
			$fields        = array_merge( $fields, $price_fields, array( 'external_url', 'button_text' ) );
			$product_props = array(
				'virtual'        => false,
				'downloadable'   => false,
				'manage_stock'   => false,
				'stock_quantity' => '',
				'stock_status'   => ProductStockStatus::IN_STOCK,
			);
		} elseif ( is_a( $classname, \WC_Product_Grouped::class, true ) ) {
			$fields        = array_merge( $fields, array( 'grouped_products' ) );
			$product_props = array(
				'manage_stock'   => false,
				'stock_quantity' => '',
			);
		} else {
			$fields = array_merge( $fields, array( 'virtual', 'downloadable' ) );

			if ( is_a( $classname, \WC_Product_Simple::class, true ) ) {
				$fields = array_merge( $fields, $price_fields, $stock_fields );
			}
		}

		/**
		 * Filter the Core fields that the product abilities accept for a product type.
		 *
		 * WooCommerce picks the fields from the product class. The owner of a
		 * type adds a Core field that its type needs, such as a price for a
		 * bundle, or removes one. A name that is not a Core field is ignored.
		 * An extension field names its types in `object_types` of
		 * `ExtensibleAbilities::register_field()`.
		 *
		 * @since 11.4.0
		 *
		 * @param array<int, string> $fields       Field names.
		 * @param string             $product_type Product type.
		 */
		$fields = (array) apply_filters( 'woocommerce_ability_product_type_fields', $fields, $product_type );

		return array(
			'fields'        => array_values( array_intersect( array_keys( self::get_product_mutation_field_schemas() ), $fields ) ),
			'product_props' => $product_props,
		);
	}

	/**
	 * The error for a product type that the ability does not support.
	 *
	 * @return \WP_Error
	 */
	private static function get_unsupported_product_type_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_product_type_unsupported',
			__( 'Product type is not supported by this ability.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * The error for a product type without a class.
	 *
	 * @return \WP_Error
	 */
	private static function get_invalid_product_type_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_invalid_product_type',
			__( 'Invalid product type.', 'woocommerce' ),
			array( 'status' => 400 )
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
		try {
			$product = wc_get_product_object( $product_type, $product_id );
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_invalid_product_type_error();
		}

		if ( ! $product || $product->get_type() !== $product_type ) {
			return self::get_invalid_product_type_error();
		}

		return $product;
	}

	/**
	 * Get the configuration for a product object, from its class.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param bool        $creating Whether the fields are for a create.
	 * @return array|\WP_Error
	 */
	protected static function get_product_config_for_product( \WC_Product $product, bool $creating = false ) {
		return self::get_product_class_config( get_class( $product ), $product->get_type(), $creating );
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
	 * The statuses a variation can have. WooCommerce counts a variation as a
	 * child of its parent only when it is published or private.
	 *
	 * @return array<int, string>
	 */
	protected static function get_variation_status_slugs(): array {
		return array( ProductStatus::PUBLISH, ProductStatus::PRIVATE );
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
		$validation_error = self::validate_product_fields_for_config( $product, $input, $product_config );
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

		return null;
	}

	/**
	 * The product whose permissions decide access to a product ID. A variation
	 * follows its parent: a published variation of a draft parent is as hidden
	 * as the parent, and whoever may edit the parent may edit its variations.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return int The parent ID for a variation, else the ID itself.
	 */
	protected static function get_product_permission_id( int $product_id ): int {
		if ( $product_id > 0 && 'product_variation' === get_post_type( $product_id ) ) {
			$parent_id = wp_get_post_parent_id( $product_id );
			return $parent_id > 0 ? $parent_id : $product_id;
		}

		return $product_id;
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
	 * Build a new variation of a parent, in memory.
	 *
	 * The product factory picks the class of a saved variation from its post,
	 * and a new variation has no post yet. The owner of a variable type names
	 * the type of its variations here, so the fields and validators of that type
	 * run before the save.
	 *
	 * @param \WC_Product_Variable $parent_product Parent, saved or not.
	 * @return \WC_Product_Variation|\WP_Error
	 */
	protected static function get_new_variation( \WC_Product_Variable $parent_product ) {
		/**
		 * Filter the product type of a new variation of a variable product.
		 *
		 * The product factory must build a `WC_Product_Variation` subclass for
		 * the type, by its class name or through the `woocommerce_product_class`
		 * filter. The subclass must have a data store and answer true to
		 * `is_type( 'variation' )`, as WooCommerce expects of any variation.
		 *
		 * @since 11.4.0
		 *
		 * @param string               $variation_type Product type. Default `variation`.
		 * @param \WC_Product_Variable $parent_product Parent, saved or not.
		 */
		$variation_type = (string) apply_filters( 'woocommerce_ability_variation_product_type', ProductType::VARIATION, $parent_product );
		$variation      = self::get_product_object_for_type( $variation_type );

		if ( is_wp_error( $variation ) || $variation instanceof \WC_Product_Variation ) {
			return $variation;
		}

		return self::get_invalid_product_type_error();
	}

	/**
	 * Get the variable product types behind each variation subclass.
	 *
	 * A variation has no product type term, so only the type of its parent tells
	 * which subclass it is. `variation` itself is not listed, because it covers
	 * every variation.
	 *
	 * @return array<string, array<int, string>> Parent types, keyed by variation type.
	 */
	protected static function get_variation_subclass_parent_types(): array {
		$parent_types = array();

		foreach ( array_keys( wc_get_product_types() ) as $product_type ) {
			$product_type = (string) $product_type;
			$parent       = is_a( \WC_Product_Factory::get_product_classname( 0, $product_type ), \WC_Product_Variable::class, true ) ? self::get_product_object_for_type( $product_type ) : null;
			$variation    = $parent instanceof \WC_Product_Variable ? self::get_new_variation( $parent ) : null;

			if ( $variation instanceof \WC_Product_Variation && ProductType::VARIATION !== $variation->get_type() ) {
				$parent_types[ $variation->get_type() ][] = $product_type;
			}
		}

		return $parent_types;
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

			if ( array_key_exists( 'delete', $item ) && ( $id <= 0 || count( $item ) > 2 ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_invalid',
					/* translators: %d: variation position. */
					sprintf( __( 'Variation %d: an item with delete takes an id and nothing else.', 'woocommerce' ), $position ),
					array( 'status' => 400 )
				);
			}

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
				if ( isset( $named[ $id ] ) ) {
					return new \WP_Error(
						'woocommerce_product_variation_invalid',
						/* translators: %d: variation position. */
						sprintf( __( 'Variation %d names a variation that an earlier item already names.', 'woocommerce' ), $position ),
						array( 'status' => 400 )
					);
				}
				$named[ $id ] = true;
				if ( ! empty( $item['delete'] ) ) {
					$plan['delete'][] = $variation;
					continue;
				}
			} else {
				$variation = self::get_new_variation( $parent_product );
				if ( is_wp_error( $variation ) ) {
					return $variation;
				}
				if ( $parent_product->get_id() > 0 ) {
					$variation->set_parent_id( $parent_product->get_id() );
				}
				if ( ! $variation->get_status( 'edit' ) ) {
					$variation->set_status( ProductStatus::PUBLISH );
				}
			}

			$error = self::set_product_props_from_input( $variation, array_diff_key( $item, array_flip( array( 'id', 'delete', 'extensions' ) ) ), $config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$error = self::check_variation_attributes( $variation, $parent_product, $position );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$plan['save'][]       = $variation;
			$plan['extensions'][] = is_array( $item['extensions'] ?? null ) ? $item['extensions'] : array();
		}

		$error = self::check_variation_siblings( $parent_product, $plan['save'], $named );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return $plan;
	}

	/**
	 * The variations of a parent, from the data store. `WC_Product_Variable::get_children()`
	 * runs the `woocommerce_get_children` filter, which a plugin can use to hide
	 * variations from a user, so checks and output read the data store instead.
	 *
	 * @param \WC_Product_Variable $parent_product Parent product.
	 * @param bool                 $force_read     Whether to skip the children cache.
	 * @return array{all: array<int, int>, visible: array<int, int>}
	 */
	private static function read_variation_children( \WC_Product_Variable $parent_product, bool $force_read ): array {
		$children = $parent_product->get_data_store()->read_children( $parent_product, $force_read ); // @phpstan-ignore method.notFound (WC_Data_Store forwards the call to the variable product data store)
		return array(
			'all'     => array_map( 'intval', (array) ( $children['all'] ?? array() ) ),
			'visible' => array_map( 'intval', (array) ( $children['visible'] ?? array() ) ),
		);
	}

	/**
	 * The IDs of every variation of a parent, from the data store.
	 *
	 * @param \WC_Product_Variable $parent_product Parent product.
	 * @param bool                 $force_read     Whether to skip the children cache.
	 * @return array<int, int>
	 */
	protected static function get_variation_ids( \WC_Product_Variable $parent_product, bool $force_read ): array {
		return self::read_variation_children( $parent_product, $force_read )['all'];
	}

	/**
	 * Pair each variation of a plan with the `extensions` input of its item, for change().
	 *
	 * @param array{save: \WC_Product_Variation[], extensions: array<int, array>} $plan Plan from plan_variations().
	 * @return array<int, array{0: \WC_Product_Variation, 1: array}>
	 */
	protected static function get_variation_objects( array $plan ): array {
		$objects = array();
		foreach ( $plan['save'] as $index => $variation ) {
			$objects[] = array( $variation, $plan['extensions'][ $index ] ?? array() );
		}
		return $objects;
	}

	/**
	 * Check that no variation picks the same options or carries the same SKU
	 * as another variation in the list, or as a saved variation of the parent.
	 * A saved variation that the input names is left out, because the input
	 * changes or deletes it.
	 *
	 * @param \WC_Product_Variable    $parent_product Parent product.
	 * @param \WC_Product_Variation[] $variations     Variations to save, in input order.
	 * @param array<int, true>        $named          IDs of the saved variations that the input names.
	 * @return null|\WP_Error
	 */
	protected static function check_variation_siblings( \WC_Product_Variable $parent_product, array $variations, array $named = array() ) {
		$combinations = array();
		$skus         = array();
		foreach ( $parent_product->get_id() > 0 ? self::get_variation_ids( $parent_product, true ) : array() as $child_id ) {
			$child = isset( $named[ $child_id ] ) ? null : wc_get_product( $child_id );
			if ( $child instanceof \WC_Product_Variation ) {
				$combinations[ self::get_variation_combination( $child ) ] = true;
				$skus[ $child->get_sku( 'edit' ) ]                         = true;
			}
		}
		foreach ( array_values( $variations ) as $index => $variation ) {
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

		return null;
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

		self::sync_variable_parent( $parent_product );

		return null;
	}

	/**
	 * Sync a parent with its variations after they changed: reload its
	 * children from the data store, then sync its price range and stock status.
	 *
	 * @param \WC_Product_Variable $parent_product Saved parent.
	 */
	protected static function sync_variable_parent( \WC_Product_Variable $parent_product ): void {
		$children = self::read_variation_children( $parent_product, true );
		$parent_product->set_children( $children['all'] );
		$parent_product->set_visible_children( $children['visible'] );
		\WC_Product_Variable::sync( $parent_product );
		$parent_product->set_price( (string) get_post_meta( $parent_product->get_id(), '_price', true ) );
	}

	/**
	 * Build the attributes of a product from input. A parent gets
	 * WC_Product_Attribute objects: a taxonomy attribute with term ids for a
	 * global attribute, like the REST API builds, else a local attribute. An
	 * option without a term keeps its name, and the save creates the term. A
	 * variation gets the option it picks for each attribute, keyed by the
	 * attribute slug, or by the taxonomy for a global attribute id.
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
				if ( ! is_array( $item ) || ! array_key_exists( 'option', $item ) || ! ( null === $item['option'] || is_scalar( $item['option'] ) ) ) {
					return self::get_invalid_product_attributes_error();
				}
				$key = empty( $item['id'] ) ? sanitize_title( (string) ( $item['name'] ?? '' ) ) : wc_attribute_taxonomy_name_by_id( (int) $item['id'] );
				if ( '' === $key ) {
					return self::get_invalid_product_attributes_error();
				}
				$attributes[ $key ] = null === $item['option'] ? '' : wc_clean( (string) $item['option'] );
			}
			return $attributes;
		}

		$attributes = array();
		foreach ( $value as $position => $item ) {
			if ( ! is_array( $item ) ) {
				return self::get_invalid_product_attributes_error();
			}
			$options  = is_array( $item['options'] ?? null ) ? array_values( array_filter( array_map( 'wc_clean', $item['options'] ), 'is_string' ) ) : array();
			$taxonomy = self::get_global_attribute_taxonomy( $item );
			$name     = '' === $taxonomy ? sanitize_text_field( trim( (string) ( $item['name'] ?? '' ) ) ) : $taxonomy;
			if ( '' === $taxonomy && ( ! empty( $item['id'] ) || ! empty( $item['global'] ) ) ) {
				return new \WP_Error(
					'woocommerce_product_attributes_invalid',
					sprintf(
						/* translators: %s: attribute name or id. */
						__( 'There is no global attribute "%s".', 'woocommerce' ),
						(string) ( $item['id'] ?? $item['name'] ?? '' )
					),
					array( 'status' => 400 )
				);
			}
			if ( '' === $name || empty( $options ) ) {
				return self::get_invalid_product_attributes_error();
			}
			$attribute = new \WC_Product_Attribute();
			if ( '' !== $taxonomy ) {
				$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
				foreach ( $options as $index => $option ) {
					$term              = get_term_by( 'slug', $option, $taxonomy );
					$term              = $term instanceof \WP_Term ? $term : get_term_by( 'name', $option, $taxonomy );
					$options[ $index ] = $term instanceof \WP_Term ? $term->term_id : $option;
				}
				$options = array_values( array_unique( $options ) );
			}
			$attribute->set_name( $name );
			$attribute->set_options( $options );
			$attribute->set_position( (int) $position );
			$attribute->set_visible( (bool) ( $item['visible'] ?? true ) );
			$attribute->set_variation( (bool) ( $item['variation'] ?? true ) );
			$attributes[ sanitize_title( $name ) ] = $attribute;
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
	 * The taxonomy of the global attribute that an `attributes` item names: by
	 * `id`, or by a `name` that matches a global attribute, with or without the
	 * `pa_` prefix.
	 *
	 * @param array $item Item of the `attributes` input.
	 * @return string The taxonomy, such as `pa_size`, or an empty string for a local attribute.
	 */
	private static function get_global_attribute_taxonomy( array $item ): string {
		if ( ! empty( $item['id'] ) ) {
			return wc_attribute_taxonomy_name_by_id( (int) $item['id'] );
		}

		$name = is_string( $item['name'] ?? null ) ? trim( $item['name'] ) : '';
		if ( empty( $item['global'] ) || '' === $name ) {
			return '';
		}

		if ( wc_attribute_taxonomy_id_by_name( $name ) > 0 ) {
			return wc_attribute_taxonomy_name( wc_attribute_taxonomy_slug( $name ) );
		}

		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			if ( 0 === strcasecmp( (string) $taxonomy->attribute_label, $name ) ) {
				return wc_attribute_taxonomy_name( (string) $taxonomy->attribute_name );
			}
		}

		return '';
	}

	/**
	 * The values a variation can pick for an attribute: the term slugs of a
	 * global attribute, or the options of a local one. An option of a global
	 * attribute that has no term yet gives the slug that the save creates.
	 *
	 * @param \WC_Product_Attribute $attribute Attribute of the parent.
	 * @return array<int, string>
	 */
	private static function get_attribute_option_slugs( \WC_Product_Attribute $attribute ): array {
		if ( ! $attribute->is_taxonomy() ) {
			return array_map( 'strval', $attribute->get_options() );
		}

		$slugs = array();
		foreach ( $attribute->get_options() as $option ) {
			$term    = is_int( $option ) ? get_term( $option, $attribute->get_name() ) : null;
			$slugs[] = $term instanceof \WP_Term ? $term->slug : sanitize_title( (string) $option );
		}

		return $slugs;
	}

	/**
	 * Check that a variation picks one option of every variation attribute of
	 * its parent, and nothing else. An attribute name matches the parent's
	 * local attribute first, then its global attribute. The variation then
	 * stores each option as the parent lists it: the term slug for a global
	 * attribute, as `WC_Product_Variation` stores it.
	 *
	 * @param \WC_Product_Variation $variation      Variation, with its attributes set from input.
	 * @param \WC_Product           $parent_product Parent product, saved or not.
	 * @param int                   $position       Position of the variation in the input, for the message. 0 for a single variation.
	 * @return null|\WP_Error
	 */
	protected static function check_variation_attributes( \WC_Product_Variation $variation, \WC_Product $parent_product, int $position = 0 ) {
		$options = array();
		foreach ( $parent_product->get_attributes() as $slug => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute && $attribute->get_variation() ) {
				$options[ $slug ] = self::get_attribute_option_slugs( $attribute );
			}
		}

		$attributes = array();
		foreach ( $variation->get_attributes() as $slug => $option ) {
			if ( ! isset( $options[ $slug ] ) && isset( $options[ wc_attribute_taxonomy_name( $slug ) ] ) ) {
				$slug = wc_attribute_taxonomy_name( $slug );
			}
			$attributes[ $slug ] = (string) $option;
		}

		$missing = array_diff_key( $options, $attributes );
		if ( empty( $options ) || ! empty( $missing ) || count( $attributes ) !== count( $options ) ) {
			return new \WP_Error(
				'woocommerce_product_variation_attributes_invalid',
				sprintf(
					/* translators: 1: "Variation N" or "The variation", 2: comma-separated attribute names. */
					__( '%1$s must pick one option of each of these attributes: %2$s.', 'woocommerce' ),
					self::get_variation_label( $position ),
					implode( ', ', array_keys( $options ) )
				),
				array( 'status' => 400 )
			);
		}

		foreach ( $attributes as $slug => $option ) {
			if ( '' === $option || in_array( $option, $options[ $slug ], true ) ) {
				continue;
			}
			if ( ! in_array( sanitize_title( $option ), $options[ $slug ], true ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_attributes_invalid',
					sprintf(
						/* translators: 1: "Variation N" or "The variation", 2: attribute name, 3: option. */
						__( '%1$s picks the option "%3$s" that the attribute "%2$s" does not have.', 'woocommerce' ),
						self::get_variation_label( $position ),
						$slug,
						$option
					),
					array( 'status' => 400 )
				);
			}
			$attributes[ $slug ] = sanitize_title( $option );
		}

		$variation->set_attributes( $attributes );

		return null;
	}

	/**
	 * How an error names a variation: by its position in a list, or alone.
	 *
	 * @param int $position Position in the input, or 0 for a single variation.
	 * @return string
	 */
	private static function get_variation_label( int $position ): string {
		/* translators: %d: variation position in the input. */
		return $position > 0 ? sprintf( __( 'Variation %d', 'woocommerce' ), $position ) : __( 'The variation', 'woocommerce' );
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
	 * Refuse a Core field that the product type does not take.
	 *
	 * @param \WC_Product $product        Product object.
	 * @param array       $input          Ability input.
	 * @param array       $product_config Product type configuration.
	 * @return null|\WP_Error
	 */
	private static function validate_product_fields_for_config( \WC_Product $product, array $input, array $product_config ) {
		$supported   = $product_config['fields'];
		$unsupported = array_diff( array_keys( $input ), array( 'id', 'type', 'extensions' ), $supported );

		if ( empty( $unsupported ) ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_product_field_unsupported',
			sprintf(
				/* translators: 1: product type, 2: comma-separated list of the fields sent, 3: comma-separated list of the fields the type takes. */
				__( 'The %1$s product type does not take %2$s. It takes %3$s.', 'woocommerce' ),
				$product->get_type(),
				implode( ', ', $unsupported ),
				implode( ', ', $supported )
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
			'variation_ids'     => $product instanceof \WC_Product_Variable ? self::get_variation_ids( $product, false ) : array(),
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
					'id'        => $attribute->get_id(),
					'name'      => wc_attribute_label( $attribute->get_name(), $product ),
					'options'   => self::get_attribute_option_slugs( $attribute ),
					'visible'   => (bool) $attribute->get_visible(),
					'variation' => (bool) $attribute->get_variation(),
				);
			} else {
				$output[] = array(
					'id'     => taxonomy_is_product_attribute( (string) $slug ) ? wc_attribute_taxonomy_id_by_name( (string) $slug ) : 0,
					'name'   => wc_attribute_label( (string) $slug, $product ),
					'option' => '' === (string) $attribute ? null : (string) $attribute,
				);
			}
		}

		return $output;
	}

	/**
	 * Get the output schema of product-create and product-update: the product,
	 * and the variations the call created or changed.
	 *
	 * @return array
	 */
	protected static function get_product_mutation_output_schema(): array {
		$schema = self::get_entity_output_schema( 'product', self::get_product_output_schema() );

		$schema['properties']['variations'] = array(
			'type'        => 'array',
			'description' => __( 'The variations the call created or changed, in input order. Deleted variations are not listed.', 'woocommerce' ),
			'items'       => ExtensibleAbilities::add_fields_schema( self::get_single_product_output_schema(), 'product', array( self::class, 'field_applies_to_variations' ) ),
		);

		return $schema;
	}

	/**
	 * Build the response of product-create and product-update. The slug comes
	 * from the stored post, because the data store does not set the slug that
	 * WordPress generates back on the object.
	 *
	 * @param \WC_Product             $product    Saved product.
	 * @param \WC_Product_Variation[] $variations Variations the call saved, in input order.
	 * @return array
	 */
	protected static function format_product_mutation_response( \WC_Product $product, array $variations ): array {
		$format = static function ( \WC_Product $saved ): array {
			return array_merge(
				self::format_product_for_response( $saved ),
				array( 'slug' => (string) get_post_field( 'post_name', $saved->get_id() ) )
			);
		};

		return array(
			'product'    => $format( $product ),
			'variations' => array_map( $format, array_values( $variations ) ),
		);
	}

	/**
	 * The error for a `variations` item that writes a field that cannot apply to
	 * a variation. The input schema would refuse it too, with a generic error.
	 *
	 * @param mixed $input Ability input.
	 * @return \WP_Error|null
	 */
	private static function variation_fields_refusal( $input ): ?\WP_Error {
		if ( ! ExtensibleAbilities::is_enabled() || ! is_array( $input['variations'] ?? null ) ) {
			return null;
		}
		$registered = ExtensibleAbilities::get_fields( 'product' );
		foreach ( $input['variations'] as $item ) {
			foreach ( (array) ( $item['extensions'] ?? array() ) as $namespace => $fields ) {
				foreach ( array_keys( (array) $fields ) as $field ) {
					$args = $registered[ $namespace ][ $field ] ?? null;
					if ( null !== $args && ! self::field_applies_to_variations( $args ) ) {
						return self::field_object_type_refusal( 'extensions.' . $namespace . '.' . $field, ProductType::VARIATION, $args['object_types'] );
					}
				}
			}
		}
		return null;
	}

	/**
	 * Whether a product field can apply to a variation, from its `object_types`.
	 *
	 * @param array<string, mixed> $args Field arguments from `register_field()`.
	 * @return bool
	 */
	public static function field_applies_to_variations( array $args ): bool {
		if ( empty( $args['object_types'] ) ) {
			return true;
		}
		foreach ( $args['object_types'] as $object_type ) {
			if ( is_a( \WC_Product_Factory::get_product_classname( 0, $object_type ), \WC_Product_Variation::class, true ) ) {
				return true;
			}
		}
		return false;
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
					'description' => __( 'Attributes with their options on a product, or the option each attribute picks on a variation. For a global attribute the options are term slugs.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'        => array(
								'type'        => 'integer',
								'description' => __( 'ID of the global attribute, or 0 for an attribute of this product only.', 'woocommerce' ),
							),
							'name'      => array( 'type' => 'string' ),
							'options'   => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'option'    => array(
								'type'        => array( 'string', 'null' ),
								'description' => __( 'The option a variation picks. Null matches any option.', 'woocommerce' ),
							),
							'visible'   => array( 'type' => 'boolean' ),
							'variation' => array(
								'type'        => 'boolean',
								'description' => __( 'Whether the variations pick an option of this attribute.', 'woocommerce' ),
							),
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
