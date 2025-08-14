<?php

namespace Automattic\WooCommerce\RestApi\V4\Schemas;

/**
 * Product schema for REST API v4.
 *
 * Defines the JSON schema for product resources.
 */
class ProductSchema {

	/**
	 * Get the complete product schema.
	 *
	 * @return array
	 */
	public static function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-07/schema#',
			'title'      => 'product',
			'type'       => 'object',
			'properties' => self::get_properties(),
			'required'   => array( 'name', 'type' ),
		);
	}

	/**
	 * Get product properties schema.
	 *
	 * @return array
	 */
	public static function get_properties(): array {
		return array(
			'id'                => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'              => array(
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'slug'              => array(
				'description' => __( 'Product slug.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_title',
				),
			),
			'type'              => array(
				'description' => __( 'Product type.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'simple',
				'enum'        => array( 'simple', 'grouped', 'external', 'variable' ),
				'context'     => array( 'view', 'edit' ),
			),
			'status'            => array(
				'description' => __( 'Product status (post status).', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'publish',
				'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
				'context'     => array( 'view', 'edit' ),
			),
			'featured'          => array(
				'description' => __( 'Featured product.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'description'       => array(
				'description' => __( 'Product description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'wp_kses_post',
				),
			),
			'short_description' => array(
				'description' => __( 'Product short description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'wp_kses_post',
				),
			),
			'sku'               => array(
				'description' => __( 'Unique identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'price'             => array(
				'description' => __( 'Current product price.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'regular_price'     => array(
				'description' => __( 'Product regular price.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'sale_price'        => array(
				'description' => __( 'Product sale price.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'manage_stock'      => array(
				'description' => __( 'Stock management at product level.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'stock_quantity'    => array(
				'description' => __( 'Stock quantity.', 'woocommerce' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
			'stock_status'      => array(
				'description' => __( 'Controls the stock status of the product.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'instock',
				'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
				'context'     => array( 'view', 'edit' ),
			),
			'date_created'      => array(
				'description' => __( "The date the product was created, in the site's timezone.", 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_modified'     => array(
				'description' => __( "The date the product was last modified, in the site's timezone.", 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Get schema for creating products.
	 *
	 * @return array
	 */
	public static function get_create_schema(): array {
		$schema = self::get_schema();

		// Remove readonly fields for creation
		$readonly_fields = array( 'id', 'price', 'date_created', 'date_modified' );
		foreach ( $readonly_fields as $field ) {
			unset( $schema['properties'][ $field ] );
		}

		return $schema;
	}

	/**
	 * Get schema for updating products.
	 *
	 * @return array
	 */
	public static function get_update_schema(): array {
		$schema = self::get_schema();

		// Remove readonly fields except ID for updates
		$readonly_fields = array( 'price', 'date_created', 'date_modified' );
		foreach ( $readonly_fields as $field ) {
			unset( $schema['properties'][ $field ] );
		}

		// ID is required for updates
		$schema['required'] = array( 'id' );

		return $schema;
	}
}

