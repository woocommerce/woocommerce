<?php
/**
 * Product schema.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * ProductSchema class.
 */
class ProductSchema {

	/**
	 * Return schema for products.
	 *
	 * @return array
	 */
	public static function get_schema() {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );
		$schema         = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product',
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name'                  => array(
					'description' => __( 'Product name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'slug'                  => array(
					'description' => __( 'Product slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'permalink'             => array(
					'description' => __( 'Product URL.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'date_created'          => array(
					'description' => __( "The date the product was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'      => array(
					'description' => __( 'The date the product was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'         => array(
					'description' => __( "The date the product was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'     => array(
					'description' => __( 'The date the product was last modified, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type'                  => array(
					'description' => __( 'Product type.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( wc_get_product_types() ),
					'context'     => array( 'view', 'edit' ),
				),
				'status'                => array(
					'description' => __( 'Product status (post status).', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'featured'              => array(
					'description' => __( 'Featured product.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'catalog_visibility'    => array(
					'description' => __( 'Catalog visibility.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'           => array(
					'description' => __( 'Product description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'short_description'     => array(
					'description' => __( 'Product short description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'sku'                   => array(
					'description' => __( 'Unique identifier.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wc_clean',
					),
				),
				'price'                 => array(
					'description' => __( 'Current product price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'regular_price'         => array(
					'description' => __( 'Product regular price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price'            => array(
					'description' => __( 'Product sale price.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_from'     => array(
					'description' => __( "Start date of sale price, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_from_gmt' => array(
					'description' => __( 'Start date of sale price, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_to'       => array(
					'description' => __( "End date of sale price, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_on_sale_to_gmt'   => array(
					'description' => __( "End date of sale price, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'price_html'            => array(
					'description' => __( 'Price formatted in HTML.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'on_sale'               => array(
					'description' => __( 'Shows if the product is on sale.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'purchasable'           => array(
					'description' => __( 'Shows if the product can be bought.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_sales'           => array(
					'description' => __( 'Amount of sales.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'virtual'               => array(
					'description' => __( 'If the product is virtual.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloadable'          => array(
					'description' => __( 'If the product is downloadable.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'downloads'             => array(
					'description' => __( 'List of downloadable files.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'File ID.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'File name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'file' => array(
								'description' => __( 'File URL.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'download_limit'        => array(
					'description' => __( 'Number of times downloadable files can be downloaded after purchase.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view', 'edit' ),
				),
				'download_expiry'       => array(
					'description' => __( 'Number of days until access to downloadable files expires.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view', 'edit' ),
				),
				'external_url'          => array(
					'description' => __( 'Product external URL. Only for external products.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'button_text'           => array(
					'description' => __( 'Product external button text. Only for external products.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'tax_status'            => array(
					'description' => __( 'Tax status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'taxable',
					'enum'        => array( 'taxable', 'shipping', 'none' ),
					'context'     => array( 'view', 'edit' ),
				),
				'tax_class'             => array(
					'description' => __( 'Tax class.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'manage_stock'          => array(
					'description' => __( 'Stock management at product level.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'stock_quantity'        => array(
					'description' => __( 'Stock quantity.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_status'          => array(
					'description' => __( 'Controls the stock status of the product.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'instock',
					'enum'        => array_keys( wc_get_product_stock_status_options() ),
					'context'     => array( 'view', 'edit' ),
				),
				'backorders'            => array(
					'description' => __( 'If managing stock, this controls if backorders are allowed.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'no',
					'enum'        => array( 'no', 'notify', 'yes' ),
					'context'     => array( 'view', 'edit' ),
				),
				'backorders_allowed'    => array(
					'description' => __( 'Shows if backorders are allowed.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'backordered'           => array(
					'description' => __( 'Shows if the product is on backordered.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sold_individually'     => array(
					'description' => __( 'Allow one item to be bought in a single order.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'weight'                => array(
					/* translators: %s: weight unit */
					'description' => sprintf( __( 'Product weight (%s).', 'woocommerce' ), $weight_unit ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'dimensions'            => array(
					'description' => __( 'Product dimensions.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product length (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product width (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product height (%s).', 'woocommerce' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping_required'     => array(
					'description' => __( 'Shows if the product need to be shipped.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_taxable'      => array(
					'description' => __( 'Shows whether or not the product shipping is taxable.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_class'        => array(
					'description' => __( 'Shipping class slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_class_id'     => array(
					'description' => __( 'Shipping class ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'reviews_allowed'       => array(
					'description' => __( 'Allow reviews.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'average_rating'        => array(
					'description' => __( 'Reviews average rating.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'rating_count'          => array(
					'description' => __( 'Amount of reviews that the product have.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'related_ids'           => array(
					'description' => __( 'List of related products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'upsell_ids'            => array(
					'description' => __( 'List of up-sell products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view', 'edit' ),
				),
				'cross_sell_ids'        => array(
					'description' => __( 'List of cross-sell products IDs.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'             => array(
					'description' => __( 'Product parent ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'purchase_note'         => array(
					'description' => __( 'Optional note to send the customer after purchase.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_kses_post',
					),
				),
				'categories'            => array(
					'description' => __( 'List of categories.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Category ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Category name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Category slug.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tags'                  => array(
					'description' => __( 'List of tags.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Tag ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Tag name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Tag slug.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'images'                => array(
					'description' => __( 'List of images.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                => array(
								'description' => __( 'Image ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'date_created'      => array(
								'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_created_gmt'  => array(
								'description' => __( 'The date the image was created, as GMT.', 'woocommerce' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_modified'     => array(
								'description' => __( "The date the image was last modified, in the site's timezone.", 'woocommerce' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_modified_gmt' => array(
								'description' => __( 'The date the image was last modified, as GMT.', 'woocommerce' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'src'               => array(
								'description' => __( 'Image URL.', 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
							),
							'name'              => array(
								'description' => __( 'Image name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'alt'               => array(
								'description' => __( 'Image alternative text.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'attributes'            => array(
					'description' => __( 'List of attributes.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array(
								'description' => __( 'Attribute ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name'      => array(
								'description' => __( 'Attribute name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'position'  => array(
								'description' => __( 'Attribute position.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'visible'   => array(
								'description' => __( "Define if the attribute is visible on the \"Additional information\" tab in the product's page.", 'woocommerce' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'variation' => array(
								'description' => __( 'Define if the attribute can be used as variation.', 'woocommerce' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'options'   => array(
								'description' => __( 'List of available term names of the attribute.', 'woocommerce' ),
								'type'        => 'array',
								'items'       => array(
									'type' => 'string',
								),
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'default_attributes'    => array(
					'description' => __( 'Defaults variation attributes.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'description' => __( 'Attribute ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name'   => array(
								'description' => __( 'Attribute name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'option' => array(
								'description' => __( 'Selected attribute term name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'variations'            => array(
					'description' => __( 'List of variations IDs.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'integer',
					),
					'readonly'    => true,
				),
				'grouped_products'      => array(
					'description' => __( 'List of grouped products ID.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'menu_order'            => array(
					'description' => __( 'Menu order, used to custom sort products.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'             => array(
					'description' => __( 'Meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);
		return $schema;
	}

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WC_Product $object Product instance.
	 * @param string      $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public static function object_to_schema( $object, $context ) {
		$data = array(
			'id'                    => $object->get_id(),
			'name'                  => $object->get_name( $context ),
			'slug'                  => $object->get_slug( $context ),
			'permalink'             => $object->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $object->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $object->get_date_created( $context ) ),
			'date_modified'         => wc_rest_prepare_date_response( $object->get_date_modified( $context ), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $object->get_date_modified( $context ) ),
			'type'                  => $object->get_type(),
			'status'                => $object->get_status( $context ),
			'featured'              => $object->is_featured(),
			'catalog_visibility'    => $object->get_catalog_visibility( $context ),
			'description'           => $object->get_description( $context ),
			'short_description'     => $object->get_short_description( $context ),
			'sku'                   => $object->get_sku( $context ),
			'price'                 => $object->get_price( $context ),
			'regular_price'         => $object->get_regular_price( $context ),
			'sale_price'            => $object->get_sale_price( $context ) ? $object->get_sale_price( $context ) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response( $object->get_date_on_sale_from( $context ), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $object->get_date_on_sale_from( $context ) ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $object->get_date_on_sale_to( $context ), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $object->get_date_on_sale_to( $context ) ),
			'price_html'            => $object->get_price_html(),
			'on_sale'               => $object->is_on_sale( $context ),
			'purchasable'           => $object->is_purchasable(),
			'total_sales'           => $object->get_total_sales( $context ),
			'virtual'               => $object->is_virtual(),
			'downloadable'          => $object->is_downloadable(),
			'downloads'             => self::get_downloads( $object ),
			'download_limit'        => $object->get_download_limit( $context ),
			'download_expiry'       => $object->get_download_expiry( $context ),
			'external_url'          => '',
			'button_text'           => '',
			'tax_status'            => $object->get_tax_status( $context ),
			'tax_class'             => $object->get_tax_class( $context ),
			'manage_stock'          => $object->managing_stock(),
			'stock_quantity'        => $object->get_stock_quantity( $context ),
			'stock_status'          => $object->get_stock_status( $context ),
			'backorders'            => $object->get_backorders( $context ),
			'backorders_allowed'    => $object->backorders_allowed(),
			'backordered'           => $object->is_on_backorder(),
			'sold_individually'     => $object->is_sold_individually(),
			'weight'                => $object->get_weight( $context ),
			'dimensions'            => array(
				'length' => $object->get_length( $context ),
				'width'  => $object->get_width( $context ),
				'height' => $object->get_height( $context ),
			),
			'shipping_required'     => $object->needs_shipping(),
			'shipping_taxable'      => $object->is_shipping_taxable(),
			'shipping_class'        => $object->get_shipping_class(),
			'shipping_class_id'     => $object->get_shipping_class_id( $context ),
			'reviews_allowed'       => $object->get_reviews_allowed( $context ),
			'average_rating'        => $object->get_average_rating( $context ),
			'rating_count'          => $object->get_rating_count(),
			'related_ids'           => wp_parse_id_list( wc_get_related_products( $object->get_id() ) ),
			'upsell_ids'            => wp_parse_id_list( $object->get_upsell_ids( $context ) ),
			'cross_sell_ids'        => wp_parse_id_list( $object->get_cross_sell_ids( $context ) ),
			'parent_id'             => $object->get_parent_id( $context ),
			'purchase_note'         => $object->get_purchase_note( $context ),
			'categories'            => self::get_taxonomy_terms( $object ),
			'tags'                  => self::get_taxonomy_terms( $object, 'tag' ),
			'images'                => self::get_images( $object ),
			'attributes'            => self::get_attributes( $object ),
			'default_attributes'    => self::get_default_attributes( $object ),
			'variations'            => array(),
			'grouped_products'      => array(),
			'menu_order'            => $object->get_menu_order( $context ),
			'meta_data'             => $object->get_meta_data(),
		);

		// Add variations to variable products.
		if ( $object->is_type( 'variable' ) ) {
			$data['variations'] = $object->get_children();
		}

		// Add grouped products data.
		if ( $object->is_type( 'grouped' ) ) {
			$data['grouped_products'] = $object->get_children();
		}

		// Add external product data.
		if ( $object->is_type( 'external' ) ) {
			$data['external_url'] = $object->get_product_url( $context );
			$data['button_text']  = $object->get_button_text( $context );
		}

		if ( 'view' === $context ) {
			$data['description']       = wpautop( do_shortcode( $data['description'] ) );
			$data['short_description'] = apply_filters( 'woocommerce_short_description', $data['short_description'] );
			$data['average_rating']    = wc_format_decimal( $data['average_rating'], 2 );
			$data['purchase_note']     = wpautop( do_shortcode( $data['purchase_note'] ) );
		}

		return $data;
	}

	/**
	 * Take data in the format of the schema and convert to a product object.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WC_Product
	 */
	public static function schema_to_object( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;

		if ( isset( $request['type'] ) ) {
			$classname = '\\' . \WC_Product_Factory::get_classname_from_product_type( $request['type'] );

			if ( ! class_exists( $classname ) ) {
				$classname = '\\WC_Product_Simple';
			}

			$object = new $classname( $id );
		} elseif ( isset( $request['id'] ) ) {
			$object = wc_get_product( $id );
		} else {
			$object = new \WC_Product_Simple();
		}

		if ( $object->is_type( 'variation' ) ) {
			return new \WP_Error(
				'woocommerce_rest_invalid_product_id',
				__( 'To manipulate product variations you should use the /products/&lt;product_id&gt;/variations/&lt;id&gt; endpoint.', 'woocommerce' ),
				array(
					'status' => 404,
				)
			);
		}

		self::set_object_data( $object, $request );

		return $object;
	}

	/**
	 * Set object data from a request.
	 *
	 * @param \WC_Product      $object Product object.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected static function set_object_data( &$object, $request ) {
		$values    = $request->get_params();
		$prop_keys = [
			'name',
			'sku',
			'description',
			'short_description',
			'slug',
			'menu_order',
			'reviews_allowed',
			'virtual',
			'tax_status',
			'tax_class',
			'catalog_visibility',
			'purchase_note',
			'status',
			'featured',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_from_gmt',
			'date_on_sale_to',
			'date_on_sale_to_gmt',
			'parent_id',
			'sold_individually',
			'manage_stock',
			'backorders',
			'stock_status',
			'stock_quantity',
			'downloadable',
			'button_text',
			'download_limit',
			'download_expiry',
			'date_created',
			'date_created_gmt',
			'upsell_ids',
			'cross_sell_ids',
		];

		$props_to_set = array_intersect_key( $values, array_flip( $prop_keys ) );
		$props_to_set = array_filter(
			$props_to_set,
			function ( $prop ) use ( $object ) {
				return is_callable( array( $object, "set_$prop" ) );
			},
			ARRAY_FILTER_USE_KEY
		);

		foreach ( $props_to_set as $prop => $value ) {
			switch ( $prop ) {
				case 'date_created':
				case 'date_created_gmt':
					$value = rest_parse_date( $value );
					break;
				case 'upsell_ids':
				case 'cross_sell_ids':
					$value = wp_parse_id_list( $value );
					break;
			}
			$object->{"set_$prop"}( $value );
		}

		if ( isset( $values['external_url'] ) && is_callable( array( $object, 'set_product_url' ) ) ) {
			$object->set_product_url( $values['external_url'] );
		}

		// Set children for a grouped product.
		if ( $object->is_type( 'grouped' ) && isset( $values['grouped_products'] ) ) {
			$object->set_children( $values['grouped_products'] );
		}

		// Allow set meta_data.
		if ( isset( $values['meta_data'] ) ) {
			foreach ( $values['meta_data'] as $meta ) {
				$object->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		// Save default attributes for variable products.
		if ( $object->is_type( 'variable' ) && isset( $values['default_attributes'] ) ) {
			self::set_default_attributes( $object, $values['default_attributes'] );
		}

		// Check for featured/gallery images, upload it and set it.
		if ( isset( $values['images'] ) ) {
			self::set_images( $object, $values['images'] );
		}

		// Product categories.
		if ( isset( $values['categories'] ) ) {
			self::set_taxonomy_terms( $object, $values['categories'] );
		}

		// Product tags.
		if ( isset( $values['tags'] ) ) {
			self::set_taxonomy_terms( $object, $values['tags'], 'tag' );
		}

		// Downloadable files.
		if ( isset( $values['downloads'] ) && is_array( $values['downloads'] ) ) {
			self::set_downloadable_files( $object, $values['downloads'] );
		}

		// Attributes.
		if ( isset( $values['attributes'] ) ) {
			self::set_attributes( $object, $values['attributes'] );
		}

		self::set_shipping_data( $object, $values );

		return $object;
	}

	/**
	 * Get the downloads for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 *
	 * @return array
	 */
	protected static function get_downloads( $object ) {
		$downloads = array();

		if ( $object->is_downloadable() ) {
			foreach ( $object->get_downloads() as $file_id => $file ) {
				$downloads[] = array(
					'id'   => $file_id, // MD5 hash.
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}

		return $downloads;
	}

	/**
	 * Get taxonomy terms.
	 *
	 * @param \WC_Product $object  Product instance.
	 * @param string      $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected static function get_taxonomy_terms( $object, $taxonomy = 'cat' ) {
		$terms = array();

		foreach ( wc_get_object_terms( $object->get_id(), 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
	}

	/**
	 * Get the images for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 * @return array
	 */
	protected static function get_images( $object ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $object->get_image_id() ) {
			$attachment_ids[] = $object->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $object->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @param string      $slug   Taxonomy name.
	 * @param \WC_Product $object Product data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected static function get_attribute_taxonomy_name( $slug, $object ) {
		// Format slug so it matches attributes of the product.
		$slug       = wc_attribute_taxonomy_slug( $slug );
		$attributes = $object->get_attributes();
		$attribute  = false;

		// pa_ attributes.
		if ( isset( $attributes[ wc_attribute_taxonomy_name( $slug ) ] ) ) {
			$attribute = $attributes[ wc_attribute_taxonomy_name( $slug ) ];
		} elseif ( isset( $attributes[ $slug ] ) ) {
			$attribute = $attributes[ $slug ];
		}

		if ( ! $attribute ) {
			return $slug;
		}

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	}

	/**
	 * Get default attributes.
	 *
	 * @param \WC_Product $object Product instance.
	 *
	 * @return array
	 */
	protected static function get_default_attributes( $object ) {
		$default = array();

		if ( $object->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $object->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => self::get_attribute_taxonomy_name( $key, $object ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => self::get_attribute_taxonomy_name( $key, $object ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
	 * Get attribute options.
	 *
	 * @param int   $object_id Product ID.
	 * @param array $attribute  Attribute data.
	 *
	 * @return array
	 */
	protected static function get_attribute_options( $object_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms(
				$object_id,
				$attribute['name'],
				array(
					'fields' => 'names',
				)
			);
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	}

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 *
	 * @return array
	 */
	protected static function get_attributes( $object ) {
		$attributes = array();

		if ( $object->is_type( 'variation' ) ) {
			$_product = wc_get_product( $object->get_parent_id() );
			foreach ( $object->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( empty( $attribute ) && '0' !== $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term  = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => self::get_attribute_taxonomy_name( $name, $_product ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => self::get_attribute_taxonomy_name( $name, $_product ),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $object->get_attributes() as $attribute ) {
				$attributes[] = array(
					'id'        => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
					'name'      => self::get_attribute_taxonomy_name( $attribute['name'], $object ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => self::get_attribute_options( $object->get_id(), $attribute ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Set product object's attributes.
	 *
	 * @param \WC_Product $object Product object.
	 * @param array       $raw_attributes Attribute data from request.
	 */
	protected static function set_attributes( &$object, $raw_attributes ) {
		$attributes = array();

		foreach ( $raw_attributes as $attribute ) {
			$attribute_id   = 0;
			$attribute_name = '';

			// Check ID for global attributes or name for product attributes.
			if ( ! empty( $attribute['id'] ) ) {
				$attribute_id   = absint( $attribute['id'] );
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} elseif ( ! empty( $attribute['name'] ) ) {
				$attribute_name = wc_clean( $attribute['name'] );
			}

			if ( ! $attribute_id && ! $attribute_name ) {
				continue;
			}

			if ( $attribute_id ) {

				if ( isset( $attribute['options'] ) ) {
					$options = $attribute['options'];

					if ( ! is_array( $attribute['options'] ) ) {
						// Text based attributes - Posted values are term names.
						$options = explode( WC_DELIMITER, $options );
					}

					$values = array_map( 'wc_sanitize_term_text_based', $options );
					$values = array_filter( $values, 'strlen' );
				} else {
					$values = array();
				}

				if ( ! empty( $values ) ) {
					// Add attribute to array, but don't set values.
					$attribute_object = new \WC_Product_Attribute();
					$attribute_object->set_id( $attribute_id );
					$attribute_object->set_name( $attribute_name );
					$attribute_object->set_options( $values );
					$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
					$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
					$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
					$attributes[] = $attribute_object;
				}
			} elseif ( isset( $attribute['options'] ) ) {
				// Custom attribute - Add attribute to array and set the values.
				if ( is_array( $attribute['options'] ) ) {
					$values = $attribute['options'];
				} else {
					$values = explode( WC_DELIMITER, $attribute['options'] );
				}
				$attribute_object = new \WC_Product_Attribute();
				$attribute_object->set_name( $attribute_name );
				$attribute_object->set_options( $values );
				$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
				$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
				$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
				$attributes[] = $attribute_object;
			}
		}
		$object->set_attributes( $attributes );
	}

	/**
	 * Set product images.
	 *
	 * @throws \WC_REST_Exception REST API exceptions.
	 *
	 * @param \WC_Product $object Product instance.
	 * @param array       $images  Images data.
	 */
	protected static function set_images( &$object, $images ) {
		$images = is_array( $images ) ? array_filter( $images ) : array();

		if ( ! empty( $images ) ) {
			$gallery = array();

			foreach ( $images as $index => $image ) {
				$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

				if ( 0 === $attachment_id && isset( $image['src'] ) ) {
					$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

					if ( is_wp_error( $upload ) ) {
						if ( ! apply_filters( 'woocommerce_rest_suppress_image_upload_error', false, $upload, $object->get_id(), $images ) ) {
							throw new \WC_REST_Exception( 'woocommerce_product_image_upload_error', $upload->get_error_message(), 400 );
						} else {
							continue;
						}
					}

					$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $object->get_id() );
				}

				if ( ! wp_attachment_is_image( $attachment_id ) ) {
					/* translators: %s: image ID */
					throw new \WC_REST_Exception( 'woocommerce_product_invalid_image_id', sprintf( __( '#%s is an invalid image ID.', 'woocommerce' ), $attachment_id ), 400 );
				}

				$featured_image = $object->get_image_id();

				if ( 0 === $index ) {
					$object->set_image_id( $attachment_id );
				} else {
					$gallery[] = $attachment_id;
				}

				// Set the image alt if present.
				if ( ! empty( $image['alt'] ) ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', wc_clean( $image['alt'] ) );
				}

				// Set the image name if present.
				if ( ! empty( $image['name'] ) ) {
					wp_update_post(
						array(
							'ID'         => $attachment_id,
							'post_title' => $image['name'],
						)
					);
				}
			}

			$object->set_gallery_image_ids( $gallery );
		} else {
			$object->set_image_id( '' );
			$object->set_gallery_image_ids( array() );
		}
	}

	/**
	 * Set product shipping data.
	 *
	 * @param \WC_Product $object Product instance.
	 * @param array       $data    Shipping data.
	 */
	protected static function set_shipping_data( &$object, $data ) {
		if ( $object->get_virtual() ) {
			$object->set_weight( '' );
			$object->set_height( '' );
			$object->set_length( '' );
			$object->set_width( '' );
		} else {
			if ( isset( $data['weight'] ) ) {
				$object->set_weight( $data['weight'] );
			}

			// Height.
			if ( isset( $data['dimensions']['height'] ) ) {
				$object->set_height( $data['dimensions']['height'] );
			}

			// Width.
			if ( isset( $data['dimensions']['width'] ) ) {
				$object->set_width( $data['dimensions']['width'] );
			}

			// Length.
			if ( isset( $data['dimensions']['length'] ) ) {
				$object->set_length( $data['dimensions']['length'] );
			}
		}

		// Shipping class.
		if ( isset( $data['shipping_class'] ) ) {
			$data_store        = $object->get_data_store();
			$shipping_class_id = $data_store->get_shipping_class_id_by_slug( wc_clean( $data['shipping_class'] ) );
			$object->set_shipping_class_id( $shipping_class_id );
		}
	}

	/**
	 * Save downloadable files.
	 *
	 * @param \WC_Product $object    Product instance.
	 * @param array       $downloads  Downloads data.
	 */
	protected static function set_downloadable_files( &$object, $downloads ) {
		$files = array();
		foreach ( $downloads as $key => $file ) {
			if ( empty( $file['file'] ) ) {
				continue;
			}

			$download = new \WC_Product_Download();
			$download->set_id( ! empty( $file['id'] ) ? $file['id'] : wp_generate_uuid4() );
			$download->set_name( $file['name'] ? $file['name'] : wc_get_filename_from_url( $file['file'] ) );
			$download->set_file( apply_filters( 'woocommerce_file_download_path', $file['file'], $object, $key ) );
			$files[] = $download;
		}
		$object->set_downloads( $files );
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @param \WC_Product $object  Product instance.
	 * @param array       $terms    Terms data.
	 * @param string      $taxonomy Taxonomy name.
	 */
	protected static function set_taxonomy_terms( &$object, $terms, $taxonomy = 'cat' ) {
		$term_ids = wp_list_pluck( $terms, 'id' );

		if ( 'cat' === $taxonomy ) {
			$object->set_category_ids( $term_ids );
		} elseif ( 'tag' === $taxonomy ) {
			$object->set_tag_ids( $term_ids );
		}
	}

	/**
	 * Save default attributes.
	 *
	 * @param \WC_Product $object Product instance.
	 * @param array       $raw_default_attributes Default attributes.
	 */
	protected static function set_default_attributes( &$object, $raw_default_attributes ) {
		$attributes         = $object->get_attributes();
		$default_attributes = array();

		foreach ( $raw_default_attributes as $attribute ) {
			$attribute_id   = 0;
			$attribute_name = '';

			// Check ID for global attributes or name for product attributes.
			if ( ! empty( $attribute['id'] ) ) {
				$attribute_id   = absint( $attribute['id'] );
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} elseif ( ! empty( $attribute['name'] ) ) {
				$attribute_name = sanitize_title( $attribute['name'] );
			}

			if ( ! $attribute_id && ! $attribute_name ) {
				continue;
			}

			if ( isset( $attributes[ $attribute_name ] ) ) {
				$_attribute = $attributes[ $attribute_name ];

				if ( $_attribute['is_variation'] ) {
					$value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';

					if ( ! empty( $_attribute['is_taxonomy'] ) ) {
						// If dealing with a taxonomy, we need to get the slug from the name posted to the API.
						$term = get_term_by( 'name', $value, $attribute_name );

						if ( $term && ! is_wp_error( $term ) ) {
							$value = $term->slug;
						} else {
							$value = sanitize_title( $value );
						}
					}

					if ( $value ) {
						$default_attributes[ $attribute_name ] = $value;
					}
				}
			}
		}

		$object->set_default_attributes( $default_attributes );
	}
}
