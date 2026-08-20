<?php
/**
 * Product ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

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
			'oneOf' => self::get_product_alias_input_schema_branches( array( 'name' ), false ),
		);
	}

	/**
	 * Get the product update input schema.
	 *
	 * Updates without a product type alias can mutate fields shared by all
	 * supported product aliases. Type-specific fields require an explicit alias.
	 *
	 * @return array
	 */
	protected static function get_product_update_input_schema(): array {
		$branches          = self::get_product_alias_input_schema_branches( array( 'id' ), true );
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
	 * Get alias-specific product input schema branches.
	 *
	 * @param array<int, string> $base_required Required fields for every branch.
	 * @param bool               $require_alias  Whether every branch must include product_type_alias.
	 * @return array<int, array>
	 */
	private static function get_product_alias_input_schema_branches( array $base_required, bool $require_alias ): array {
		$branches = array();

		foreach ( self::get_product_type_alias_configs() as $product_type_alias => $product_config ) {
			$required = $base_required;

			if ( $require_alias || 'physical' !== $product_type_alias ) {
				$required[] = 'product_type_alias';
			}

			$properties = array();

			if ( in_array( 'id', $base_required, true ) ) {
				$properties['id'] = array(
					'type'    => 'integer',
					'minimum' => 1,
				);
			}

			$properties['product_type_alias'] = self::get_product_type_alias_schema( $product_type_alias );

			if ( 'physical' === $product_type_alias && ! $require_alias ) {
				$properties['product_type_alias']['default'] = 'physical';
			}

			$properties = array_merge(
				$properties,
				self::get_product_mutation_field_schemas_for_fields( $product_config['fields'] )
			);

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
	 * Get the product type alias schema.
	 *
	 * @param string|null $product_type_alias Product type alias to restrict to, or null for all aliases.
	 * @return array
	 */
	private static function get_product_type_alias_schema( ?string $product_type_alias = null ): array {
		return array(
			'type'        => 'string',
			'description' => __(
				'Supported agent-facing product type alias. physical maps to a simple shippable, non-downloadable product; virtual maps to a simple non-shipping, non-downloadable product; digital maps to a simple virtual/downloadable product; affiliate maps to the external product type; grouped maps to grouped.',
				'woocommerce'
			),
			'enum'        => null === $product_type_alias ? self::get_supported_product_type_aliases() : array( $product_type_alias ),
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
				'description' => __( 'External destination URL for affiliate products.', 'woocommerce' ),
				'format'      => 'uri',
			),
			'button_text'       => array(
				'type'        => 'string',
				'description' => __( 'Button text for affiliate products.', 'woocommerce' ),
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
	 * Supported agent-facing product type aliases.
	 *
	 * @return array<int, string>
	 */
	protected static function get_supported_product_type_aliases(): array {
		return array_keys( self::get_product_type_alias_configs() );
	}

	/**
	 * Get product configuration by agent-facing product type alias.
	 *
	 * @param string $product_type_alias Agent-facing product type alias.
	 * @return array|\WP_Error
	 */
	protected static function get_product_config_for_alias( string $product_type_alias ) {
		$product_type_alias = sanitize_key( $product_type_alias );
		$configs            = self::get_product_type_alias_configs();

		if ( ! isset( $configs[ $product_type_alias ] ) ) {
			return new \WP_Error(
				'woocommerce_product_type_unsupported',
				__( 'Product type is not supported by this ability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $configs[ $product_type_alias ];
	}

	/**
	 * Get product query arguments for an agent-facing product type alias.
	 *
	 * @param string $product_type_alias Agent-facing product type alias.
	 * @return array|\WP_Error
	 */
	protected static function get_product_query_args_for_alias( string $product_type_alias ) {
		$product_config = self::get_product_config_for_alias( $product_type_alias );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		$query_args = array(
			'type' => $product_config['wc_type'],
		);

		if ( isset( $product_config['query_props'] ) ) {
			foreach ( $product_config['query_props'] as $field => $value ) {
				$query_args[ $field ] = (bool) $value;
			}
		}

		return $query_args;
	}

	/**
	 * Get product configuration for an existing product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array|\WP_Error
	 */
	protected static function get_product_config_for_product( \WC_Product $product ) {
		$product_type_alias = self::get_product_type_alias_for_product( $product );

		if ( is_wp_error( $product_type_alias ) ) {
			return $product_type_alias;
		}

		return self::get_product_config_for_alias( $product_type_alias );
	}

	/**
	 * Get the supported agent-facing product type alias for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string|\WP_Error
	 */
	protected static function get_product_type_alias_for_product( \WC_Product $product ) {
		if ( $product->is_type( ProductType::SIMPLE ) ) {
			if ( $product->get_virtual() && $product->get_downloadable() ) {
				return 'digital';
			}

			if ( $product->get_virtual() ) {
				return 'virtual';
			}

			return 'physical';
		}

		if ( $product->is_type( ProductType::EXTERNAL ) ) {
			return 'affiliate';
		}

		if ( $product->is_type( ProductType::GROUPED ) ) {
			return 'grouped';
		}

		return new \WP_Error(
			'woocommerce_product_type_unsupported',
			__( 'Product type is not supported by this ability.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Get fields shared by all supported product mutation aliases.
	 *
	 * @return array<int, string>
	 */
	private static function get_common_product_mutation_fields(): array {
		return array( 'name', 'sku', 'description', 'short_description', 'status' );
	}

	/**
	 * Get product type alias configuration.
	 *
	 * The keys are agent-facing product type aliases. Each config maps the alias to a
	 * WooCommerce product class plus the fields that can be applied to it.
	 *
	 * @return array<string, array{wc_type: string, fields: array<int, string>, product_props: array<string, mixed>, query_props?: array<string, mixed>}>
	 */
	private static function get_product_type_alias_configs(): array {
		$common_fields = self::get_common_product_mutation_fields();
		$simple_fields = array_merge(
			$common_fields,
			array( 'regular_price', 'sale_price', 'manage_stock', 'stock_quantity', 'stock_status' )
		);

		return array(
			'physical'  => array(
				'wc_type'       => ProductType::SIMPLE,
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => false,
					'downloadable' => false,
				),
				'query_props'   => array(
					'virtual'      => false,
					'downloadable' => false,
				),
			),
			'virtual'   => array(
				'wc_type'       => ProductType::SIMPLE,
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => true,
					'downloadable' => false,
				),
				'query_props'   => array(
					'virtual'      => true,
					'downloadable' => false,
				),
			),
			'digital'   => array(
				'wc_type'       => ProductType::SIMPLE,
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => true,
					'downloadable' => true,
				),
				'query_props'   => array(
					'virtual'      => true,
					'downloadable' => true,
				),
			),
			'affiliate' => array(
				'wc_type'       => ProductType::EXTERNAL,
				'fields'        => array_merge(
					$common_fields,
					array( 'regular_price', 'sale_price', 'external_url', 'button_text' )
				),
				'product_props' => array(
					'virtual'        => false,
					'downloadable'   => false,
					'manage_stock'   => false,
					'stock_quantity' => '',
					'stock_status'   => ProductStockStatus::IN_STOCK,
				),
			),
			'grouped'   => array(
				'wc_type'       => ProductType::GROUPED,
				'fields'        => array_merge( $common_fields, array( 'grouped_products' ) ),
				'product_props' => array(
					'manage_stock'   => false,
					'stock_quantity' => '',
				),
			),
		);
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
		$shared_fields          = array( 'id', 'product_type_alias' );
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

		return array(
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
	}

	/**
	 * Get the schema for a single product in a response.
	 *
	 * @return array
	 */
	protected static function get_product_output_schema(): array {
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
