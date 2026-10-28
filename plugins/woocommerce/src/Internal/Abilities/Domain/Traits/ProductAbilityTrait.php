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
			'oneOf' => self::get_product_type_input_schema_branches(),
		);
	}

	/**
	 * Get the product update input schema.
	 *
	 * The schema lists the fields of every supported product type. The update
	 * refuses a field that the type of the loaded product does not support.
	 *
	 * @return array
	 */
	protected static function get_product_update_input_schema(): array {
		$product_types = self::get_supported_product_types();
		$fields        = array();

		foreach ( $product_types as $product_type ) {
			$product_config = self::get_product_type_config( $product_type );
			if ( ! is_wp_error( $product_config ) ) {
				$fields = array_merge( $fields, $product_config['fields'] );
			}
		}

		return array(
			'type'                 => 'object',
			'properties'           => array_merge(
				array(
					'id'   => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'type' => self::get_product_type_schema( $product_types ),
				),
				self::get_product_mutation_field_schemas_for_fields( $fields )
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the product create input schema branches, one for each supported product type.
	 *
	 * @return array<int, array>
	 */
	private static function get_product_type_input_schema_branches(): array {
		$branches = array();

		foreach ( self::get_supported_product_types() as $product_type ) {
			$product_config = self::get_product_type_config( $product_type );
			if ( is_wp_error( $product_config ) ) {
				continue;
			}

			$required    = array( 'name' );
			$type_schema = self::get_product_type_schema( array( $product_type ) );

			if ( ProductType::SIMPLE === $product_type ) {
				$type_schema['default']      = ProductType::SIMPLE;
				$type_schema['description'] .= ' ' . __( 'A product without a type is simple.', 'woocommerce' );
			} else {
				$required[] = 'type';
			}

			$branches[] = array(
				'type'                 => 'object',
				'properties'           => array_merge(
					array( 'type' => $type_schema ),
					self::get_product_mutation_field_schemas_for_fields( $product_config['fields'] )
				),
				'required'             => $required,
				'additionalProperties' => false,
			);
		}

		return $branches;
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
	 * @return array<string, array>
	 */
	private static function get_product_mutation_field_schemas(): array {
		return array(
			'name'              => array( 'type' => 'string' ),
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
	 * @param array<int, string> $fields Field names.
	 * @return array<string, array>
	 */
	private static function get_product_mutation_field_schemas_for_fields( array $fields ): array {
		$schemas = self::get_product_mutation_field_schemas();
		$result  = array();

		foreach ( $schemas as $field => $schema ) {
			if ( in_array( $field, $fields, true ) ) {
				$result[ $field ] = $schema;
			}
		}

		return $result;
	}

	/**
	 * Product types that the mutation abilities support: every registered type
	 * with a class of its own, except the variable ones.
	 *
	 * @return array<int, string>
	 */
	protected static function get_supported_product_types(): array {
		$product_types = array();

		foreach ( array_keys( wc_get_product_types() ) as $product_type ) {
			$product_type = (string) $product_type;

			if ( ! is_wp_error( self::get_product_class_config( \WC_Product_Factory::get_product_classname( 0, $product_type ), $product_type ) ) ) {
				$product_types[] = $product_type;
			}
		}

		return $product_types;
	}

	/**
	 * Get the configuration of a product type.
	 *
	 * @param string $product_type Product type.
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}|\WP_Error
	 */
	protected static function get_product_type_config( string $product_type ) {
		if ( ! isset( wc_get_product_types()[ $product_type ] ) ) {
			return self::get_unsupported_product_type_error();
		}

		return self::get_product_class_config( \WC_Product_Factory::get_product_classname( 0, $product_type ), $product_type );
	}

	/**
	 * Get the configuration of a product class.
	 *
	 * The product class decides which Core fields apply. A class that extends
	 * a Core class gets the fields of that class. The owner of a type changes
	 * the list with the `woocommerce_ability_product_type_fields` filter.
	 *
	 * @param string $classname    Product class.
	 * @param string $product_type Product type.
	 * @return array{fields: array<int, string>, product_props: array<string, mixed>}|\WP_Error
	 */
	private static function get_product_class_config( string $classname, string $product_type ) {
		if ( is_a( $classname, \WC_Product_Variable::class, true ) ) {
			return self::get_unsupported_product_type_error();
		}

		if ( \WC_Product_Simple::class === $classname && ProductType::SIMPLE !== $product_type ) {
			return self::get_invalid_product_type_error();
		}

		$fields        = self::get_common_product_mutation_fields();
		$product_props = array();
		$price_fields  = array( 'regular_price', 'sale_price' );

		if ( is_a( $classname, \WC_Product_External::class, true ) ) {
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
				$fields = array_merge( $fields, $price_fields, array( 'manage_stock', 'stock_quantity', 'stock_status' ) );
			}
		}

		/**
		 * Filter the Core fields that the product abilities accept for a product type.
		 *
		 * WooCommerce picks the fields from the product class. The owner of a
		 * type adds a Core field that its type needs, such as a price for a
		 * bundle, or removes one. A name that is not a Core field is ignored.
		 *
		 * @since 11.4.0
		 *
		 * @param array<int, string> $fields       Field names.
		 * @param string             $product_type Product type.
		 * @param string             $classname    Product class.
		 */
		$fields = apply_filters( 'woocommerce_ability_product_type_fields', $fields, $product_type, $classname );

		return array(
			'fields'        => array_values( array_intersect( array_keys( self::get_product_mutation_field_schemas() ), (array) $fields ) ),
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
		$product = wc_get_product_object( $product_type, $product_id );

		if ( ! $product || $product->get_type() !== $product_type ) {
			return self::get_invalid_product_type_error();
		}

		return $product;
	}

	/**
	 * Get the configuration for a product object, from its class.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array|\WP_Error
	 */
	protected static function get_product_config_for_product( \WC_Product $product ) {
		return self::get_product_class_config( get_class( $product ), $product->get_type() );
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
		);

		foreach ( $setters as $field => $setter ) {
			if (
				! in_array( $field, $product_config['fields'], true )
				|| ! array_key_exists( $field, $input )
				|| ! is_callable( array( $product, $setter ) )
			) {
				continue;
			}

			$prepared_value = self::prepare_product_field_value( $field, $input[ $field ] );
			if ( is_wp_error( $prepared_value ) ) {
				return $prepared_value;
			}

			$product->{$setter}( $prepared_value );
		}

		return null;
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
			'date_created'      => wc_rest_prepare_date_response( $product->get_date_created(), false ),
			'date_created_gmt'  => wc_rest_prepare_date_response( $product->get_date_created() ),
			'date_modified'     => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
			'date_modified_gmt' => wc_rest_prepare_date_response( $product->get_date_modified() ),
		);

		return ExtensibleAbilities::add_fields_to_object( $data, 'product', $product );
	}

	/**
	 * Get the schema for a single product in a response.
	 *
	 * @return array
	 */
	protected static function get_product_output_schema(): array {
		return ExtensibleAbilities::add_fields_schema(
			array(
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
						'description' => __( 'Internal product type slug, such as simple, external, grouped, or variable.', 'woocommerce' ),
						'enum'        => array_keys( wc_get_product_types() ),
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
			),
			'product'
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
