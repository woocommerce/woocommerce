<?php
/**
 * Handles product CSV export.
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Export
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	include_once( WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php' );
}

/**
 * WC_Product_CSV_Exporter Class.
 */
class WC_Product_CSV_Exporter extends WC_CSV_Batch_Exporter {

	/**
	 * The filename to export to.
	 */
	protected $filename = 'wc-product-export.csv';

	/**
	 * Type of export used in filter names.
	 */
	protected $export_type = 'product';

	/**
	 * Return an array of columns to export.
	 *
	 * @return array
	 */
	public function get_default_column_names() {
		return array(
			'id'                 => __( 'ID', 'woocommerce' ),
			'type'               => __( 'Type', 'woocommerce' ),
			'sku'                => __( 'SKU', 'woocommerce' ),
			'name'               => __( 'Name', 'woocommerce' ),
			'published'          => __( 'Published', 'woocommerce' ),
			'featured'           => __( 'Is featured?', 'woocommerce' ),
			'catalog_visibility' => __( 'Visibility in catalog', 'woocommerce' ),
			'short_description'  => __( 'Short Description', 'woocommerce' ),
			'description'        => __( 'Description', 'woocommerce' ),
			'date_on_sale_from'  => __( 'Date sale price starts', 'woocommerce' ),
			'date_on_sale_to'    => __( 'Date sale price ends', 'woocommerce' ),
			'tax_status'         => __( 'Tax Class', 'woocommerce' ),
			'stock_status'       => __( 'In stock?', 'woocommerce' ),
			'backorders'         => __( 'Backorders allowed?', 'woocommerce' ),
			'sold_individually'  => __( 'Sold individually?', 'woocommerce' ),
			'weight'             => sprintf( __( 'Weight (%s)', 'woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
			'length'             => sprintf( __( 'Length (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'width'              => sprintf( __( 'Width (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'height'             => sprintf( __( 'Height (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'reviews_allowed'    => __( 'Allow customer reviews?', 'woocommerce' ),
			'purchase_note'      => __( 'Purchase Note', 'woocommerce' ),
			'price'              => __( 'Price', 'woocommerce' ),
			'regular_price'      => __( 'Regular Price', 'woocommerce' ),
			'stock_quantity'     => __( 'Stock', 'woocommerce' ),
			'category_ids'       => __( 'Categories', 'woocommerce' ),
			'tag_ids'            => __( 'Tags', 'woocommerce' ),
			'shipping_class_id'  => __( 'Shipping Class', 'woocommerce' ),
			'image_id'           => __( 'Images', 'woocommerce' ),
			'download_limit'     => __( 'Download Limit', 'woocommerce' ),
			'download_expiry'    => __( 'Download Expiry Days', 'woocommerce' ),
			'parent_id'          => __( 'Parent', 'woocommerce' ),
			'upsell_ids'         => __( 'Upsells', 'woocommerce' ),
			'cross_sell_ids'     => __( 'Cross-sells', 'woocommerce' ),
		);
	}

	/**
	 * Prepare data for export.
	 */
	public function prepare_data_to_export() {
		$columns  = $this->get_column_names();
		$products = wc_get_products( array(
			'status'   => array( 'private', 'publish' ),
			'type'     => array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ),
			'limit'    => $this->get_limit(),
			'page'     => $this->get_page(),
			'orderby'  => array(
				'ID'   => 'DESC',
			),
			'return'   => 'objects',
			'paginate' => true,
		) );

		$this->total_rows = $products->total;
		$this->row_data   = array();

		foreach ( $products->products as $product ) {
			$row = array();
			foreach ( $columns as $column_id => $column_name ) {
				// Format column id.
				$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;

				// Skip some columns if dynamically handled later.
				if ( in_array( $column_id, array( 'downloads', 'attributes' ) ) ) {
					continue;
				}

				// Skip columns if we're being selective.
				if ( ! $this->is_column_exporting( $column_id ) ) {
					continue;
				}

				// Handle special columns which don't map 1:1 to product data.
				if ( 'published' === $column_id ) {
					$value = 'publish' === $product->get_status( 'edit' ) ? 1 : 0;

				} elseif ( 'category_ids' === $column_id ) {
					$term_ids = $product->get_category_ids( 'edit' );
					$value    = $this->format_term_ids( $term_ids, 'product_cat' );

				} elseif ( 'tag_ids' === $column_id ) {
					$term_ids = $product->get_tag_ids( 'edit' );
					$value    = $this->format_term_ids( $term_ids, 'product_tag' );

				} elseif ( 'shipping_class_id' === $column_id ) {
					$term_ids = $product->get_shipping_class_id( 'edit' );
					$value    = $this->format_term_ids( $term_ids, 'product_shipping_class' );

				} elseif ( in_array( $column_id, array( 'cross_sell_ids', 'upsell_ids', 'parent_id' ) ) ) {
					$linked_products = array_filter( array_map( 'wc_get_product', (array) $product->{"get_{$column_id}"}( 'edit' ) ) );
					$product_list    = array();

					foreach ( $linked_products as $linked_product ) {
						if ( $linked_product->get_sku() ) {
							$product_list[] = $linked_product->get_sku();
						} else {
							$product_list[] = 'id:' . $linked_product->get_id();
						}
					}

					$value = implode( ',', $product_list );

				} elseif ( in_array( $column_id, array( 'download_limit', 'download_expiry' ) ) ) {
					if ( $product->is_type( 'downloadable' ) && is_callable( array( $product, "get_{$column_id}" ) ) ) {
						$value = $product->{"get_{$column_id}"}( 'edit' );
					} else {
						$value = __( 'N/A', 'woocommerce' );
					}
				} elseif ( 'image_id' === $column_id ) {
					$image_ids = array_merge( array( $product->get_image_id( 'edit' ) ), $product->get_gallery_image_ids( 'edit' ) );
					$images    = array();

					foreach ( $image_ids as $image_id ) {
						$image  = wp_get_attachment_image_src( $product->get_image_id( 'edit' ), 'full' );

						if ( $image ) {
							$images[] = $image[0];
						}
					}

					$value = implode( ', ', $images );

				// Default and custom handling.
				} else {
					if ( is_callable( array( $product, "get_{$column_id}" ) ) ) {
						$value = $product->{"get_{$column_id}"}( 'edit' );
					} elseif ( has_filter( "woocommerce_export_{$this->export_type}_column_{$column_id}" ) ) {
						$value = apply_filters( "woocommerce_export_{$this->export_type}_column_{$column_id}", '', $product );
					}
				}

				$row[ $column_id ] = $value;
			}

			// Downloads are dynamic.
			if ( $product->is_downloadable() && $this->is_column_exporting( 'downloads' ) ) {
				$downloads = $product->get_downloads( 'edit' );

				if ( $downloads ) {
					$i = 1;
					foreach ( $downloads as $download ) {
						$this->column_names[ 'downloads:name' . $i ] = sprintf( __( 'Download %d Name', 'woocommerce' ), $i );
						$this->column_names[ 'downloads:url' . $i ]  = sprintf( __( 'Download %d URL', 'woocommerce' ), $i );
						$row[ 'downloads:name' . $i ] = $download->get_name();
						$row[ 'downloads:url' . $i ]  = $download->get_file();
						$i++;
					}
				}
			}

			// @todo price, meta

			// Attributes are dynamic.
			if ( $this->is_column_exporting( 'attributes' ) ) {
				$attributes = $product->get_attributes();

				if ( count( $attributes ) ) {
					$i = 1;
					foreach ( $attributes as $attribute_name => $attribute ) {
						$this->column_names[ 'attributes:name' . $i ]  = sprintf( __( 'Attribute %d Name', 'woocommerce' ), $i );
						$this->column_names[ 'attributes:value' . $i ] = sprintf( __( 'Attribute %d Value(s)', 'woocommerce' ), $i );

						if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
							$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute->get_name(), $product );

							if ( $attribute->is_taxonomy() ) {
								$terms  = $attribute->get_terms();
								$values = array();

								foreach ( $terms as $term ) {
									$values[] = $term->name;
								}

								$row[ 'attributes:value' . $i ] = implode( ', ', $values );
							} else {
								$row[ 'attributes:value' . $i ] = implode( ', ', $attribute->get_options() );
							}
						} else {
							$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute_name, $product );

							if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
								$option_term = get_term_by( 'slug', $attribute, $attribute_name );
								$row[ 'attributes:value' . $i ] = $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute;
							} else {
								$row[ 'attributes:value' . $i ] = $attribute;
							}
						}

						$i++;
					}
				}
			}

			$this->row_data[] = $row;
		}
	}
}
