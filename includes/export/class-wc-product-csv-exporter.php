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
if ( ! class_exists( 'WC_CSV_Batch_Exporter', false ) ) {
	include_once( WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php' );
}

/**
 * WC_Product_CSV_Exporter Class.
 */
class WC_Product_CSV_Exporter extends WC_CSV_Batch_Exporter {

	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = 'product';

	/**
	 * Should meta be exported?
	 *
	 * @var boolean
	 */
	protected $enable_meta_export = false;

	/**
	 * Which product types are being exported.
	 *
	 * @var array
	 */
	protected $product_types_to_export = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_product_types_to_export( array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ) );
	}

	/**
	 * Should meta be exported?
	 *
	 * @since 3.1.0
	 * @param bool $enable_meta_export Should meta be exported.
	 */
	public function enable_meta_export( $enable_meta_export ) {
		$this->enable_meta_export = (bool) $enable_meta_export;
	}

	/**
	 * Product types to export.
	 *
	 * @since 3.1.0
	 * @param array $product_types_to_export List of types to export.
	 */
	public function set_product_types_to_export( $product_types_to_export ) {
		$this->product_types_to_export = array_map( 'wc_clean', $product_types_to_export );
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		return apply_filters( "woocommerce_product_export_{$this->export_type}_default_columns", array(
			'id'                 => __( 'ID', 'woocommerce' ),
			'type'               => __( 'Type', 'woocommerce' ),
			'sku'                => __( 'SKU', 'woocommerce' ),
			'name'               => __( 'Name', 'woocommerce' ),
			'published'          => __( 'Published', 'woocommerce' ),
			'featured'           => __( 'Is featured?', 'woocommerce' ),
			'catalog_visibility' => __( 'Visibility in catalog', 'woocommerce' ),
			'short_description'  => __( 'Short description', 'woocommerce' ),
			'description'        => __( 'Description', 'woocommerce' ),
			'date_on_sale_from'  => __( 'Date sale price starts', 'woocommerce' ),
			'date_on_sale_to'    => __( 'Date sale price ends', 'woocommerce' ),
			'tax_status'         => __( 'Tax status', 'woocommerce' ),
			'tax_class'          => __( 'Tax class', 'woocommerce' ),
			'stock_status'       => __( 'In stock?', 'woocommerce' ),
			'stock'              => __( 'Stock', 'woocommerce' ),
			'backorders'         => __( 'Backorders allowed?', 'woocommerce' ),
			'sold_individually'  => __( 'Sold individually?', 'woocommerce' ),
			'weight'             => sprintf( __( 'Weight (%s)', 'woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
			'length'             => sprintf( __( 'Length (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'width'              => sprintf( __( 'Width (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'height'             => sprintf( __( 'Height (%s)', 'woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
			'reviews_allowed'    => __( 'Allow customer reviews?', 'woocommerce' ),
			'purchase_note'      => __( 'Purchase note', 'woocommerce' ),
			'sale_price'         => __( 'Sale price', 'woocommerce' ),
			'regular_price'      => __( 'Regular price', 'woocommerce' ),
			'category_ids'       => __( 'Categories', 'woocommerce' ),
			'tag_ids'            => __( 'Tags', 'woocommerce' ),
			'shipping_class_id'  => __( 'Shipping class', 'woocommerce' ),
			'images'             => __( 'Images', 'woocommerce' ),
			'download_limit'     => __( 'Download limit', 'woocommerce' ),
			'download_expiry'    => __( 'Download expiry days', 'woocommerce' ),
			'parent_id'          => __( 'Parent', 'woocommerce' ),
			'grouped_products'   => __( 'Grouped products', 'woocommerce' ),
			'upsell_ids'         => __( 'Upsells', 'woocommerce' ),
			'cross_sell_ids'     => __( 'Cross-sells', 'woocommerce' ),
			'product_url'        => __( 'External URL', 'woocommerce' ),
			'button_text'        => __( 'Button text', 'woocommerce' ),
			'menu_order'         => __( 'Position', 'woocommerce' ),
		) );
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 3.1.0
	 */
	public function prepare_data_to_export() {
		$columns  = $this->get_column_names();
		$args = apply_filters( "woocommerce_product_export_{$this->export_type}_query_args", array(
			'status'   => array( 'private', 'publish', 'draft' ),
			'type'     => $this->product_types_to_export,
			'limit'    => $this->get_limit(),
			'page'     => $this->get_page(),
			'orderby'  => array(
				'ID'   => 'ASC',
			),
			'return'   => 'objects',
			'paginate' => true,
		) );
		$products = wc_get_products( $args );

		$this->total_rows = $products->total;
		$this->row_data   = array();

		foreach ( $products->products as $product ) {
			$row = array();
			foreach ( $columns as $column_id => $column_name ) {
				$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
				$value     = '';

				// Skip some columns if dynamically handled later or if we're being selective.
				if ( in_array( $column_id, array( 'downloads', 'attributes', 'meta' ), true ) || ! $this->is_column_exporting( $column_id ) ) {
					continue;
				}

				if ( has_filter( "woocommerce_product_export_{$this->export_type}_column_{$column_id}" ) ) {
					// Filter for 3rd parties.
					$value = apply_filters( "woocommerce_product_export_{$this->export_type}_column_{$column_id}", '', $product, $column_id );

				} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
					// Handle special columns which don't map 1:1 to product data.
					$value = $this->{"get_column_value_{$column_id}"}( $product );

				} elseif ( is_callable( array( $product, "get_{$column_id}" ) ) ) {
					// Default and custom handling.
					$value = $product->{"get_{$column_id}"}( 'edit' );
				}

				$row[ $column_id ] = $value;
			}

			$this->prepare_downloads_for_export( $product, $row );
			$this->prepare_attributes_for_export( $product, $row );
			$this->prepare_meta_for_export( $product, $row );

			$this->row_data[] = apply_filters( 'woocommerce_product_export_row_data', $row, $product );
		}
	}

	/**
	 * Get published value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return int
	 */
	protected function get_column_value_published( $product ) {
		$statuses = array(
			'draft'   => -1,
			'private' => 0,
			'publish' => 1,
		);

		$status = $product->get_status( 'edit' );

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : -1;
	}

	/**
	 * Get product_cat value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_category_ids( $product ) {
		$term_ids = $product->get_category_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_cat' );
	}

	/**
	 * Get product_tag value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_tag_ids( $product ) {
		$term_ids = $product->get_tag_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_tag' );
	}

	/**
	 * Get product_shipping_class value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_shipping_class_id( $product ) {
		$term_ids = $product->get_shipping_class_id( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_shipping_class' );
	}

	/**
	 * Get images value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_images( $product ) {
		$image_ids = array_merge( array( $product->get_image_id( 'edit' ) ), $product->get_gallery_image_ids( 'edit' ) );
		$images    = array();

		foreach ( $image_ids as $image_id ) {
			$image  = wp_get_attachment_image_src( $image_id, 'full' );

			if ( $image ) {
				$images[] = $image[0];
			}
		}

		return $this->implode_values( $images );
	}

	/**
	 * Prepare linked products for export.
	 *
	 * @since 3.1.0
	 * @param int[] $linked_products Array of linked product ids.
	 * @return string
	 */
	protected function prepare_linked_products_for_export( $linked_products ) {
		$product_list = array();

		foreach ( $linked_products as $linked_product ) {
			if ( $linked_product->get_sku() ) {
				$product_list[] = $linked_product->get_sku();
			} else {
				$product_list[] = 'id:' . $linked_product->get_id();
			}
		}

		return $this->implode_values( $product_list );
	}

	/**
	 * Get cross_sell_ids value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_cross_sell_ids( $product ) {
		return $this->prepare_linked_products_for_export( array_filter( array_map( 'wc_get_product', (array) $product->get_cross_sell_ids( 'edit' ) ) ) );
	}

	/**
	 * Get upsell_ids value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_upsell_ids( $product ) {
		return $this->prepare_linked_products_for_export( array_filter( array_map( 'wc_get_product', (array) $product->get_upsell_ids( 'edit' ) ) ) );
	}

	/**
	 * Get parent_id value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_parent_id( $product ) {
		if ( $product->get_parent_id( 'edit' ) ) {
			$parent = wc_get_product( $product->get_parent_id( 'edit' ) );
			if ( ! $parent ) {
				return '';
			}

			return $parent->get_sku( 'edit' ) ? $parent->get_sku( 'edit' ) : 'id:' . $parent->get_id();
		}
		return '';
	}

	/**
	 * Get grouped_products value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_grouped_products( $product ) {
		if ( 'grouped' !== $product->get_type() ) {
			return '';
		}

		$grouped_products = array();
		$child_ids = $product->get_children( 'edit' );
		foreach ( $child_ids as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ! $child ) {
				continue;
			}

			$grouped_products[] = $child->get_sku( 'edit' ) ? $child->get_sku( 'edit' ) : 'id:' . $child_id;
		}
		return $this->implode_values( $grouped_products );
	}

	/**
	 * Get download_limit value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_download_limit( $product ) {
		return $product->is_downloadable() && $product->get_download_limit( 'edit' ) ? $product->get_download_limit( 'edit' ) : '';
	}

	/**
	 * Get download_expiry value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_download_expiry( $product ) {
		return $product->is_downloadable() && $product->get_download_expiry( 'edit' ) ? $product->get_download_expiry( 'edit' ) : '';
	}

	/**
	 * Get stock value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_stock( $product ) {
		$manage_stock   = $product->get_manage_stock( 'edit' );
		$stock_quantity = $product->get_stock_quantity( 'edit' );

		if ( $product->is_type( 'variation' && 'parent' === $manage_stock ) ) {
			return 'parent';
		} elseif ( $manage_stock ) {
			return $stock_quantity;
		} else {
			return '';
		}
	}

	/**
	 * Get stock status value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_stock_status( $product ) {
		$status = $product->get_stock_status( 'edit' );
		return 'instock' === $status ? 1 : 0;
	}

	/**
	 * Get backorders.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_backorders( $product ) {
		$backorders = $product->get_backorders( 'edit' );

		switch ( $backorders ) {
			case 'notify' :
				return 'notify';
			default :
				return wc_string_to_bool( $backorders ) ? 1 : 0;
		}
	}

	/**
	 * Get type value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_type( $product ) {
		$types   = array();
		$types[] = $product->get_type();

		if ( $product->is_downloadable() ) {
			$types[] = 'downloadable';
		}

		if ( $product->is_virtual() ) {
			$types[] = 'virtual';
		}

		return $this->implode_values( $types );
	}

	/**
	 * Export downloads.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @param array      $row     Row being exported.
	 */
	protected function prepare_downloads_for_export( $product, &$row ) {
		if ( $product->is_downloadable() && $this->is_column_exporting( 'downloads' ) ) {
			$downloads = $product->get_downloads( 'edit' );

			if ( $downloads ) {
				$i = 1;
				foreach ( $downloads as $download ) {
					$this->column_names[ 'downloads:name' . $i ] = sprintf( __( 'Download %d name', 'woocommerce' ), $i );
					$this->column_names[ 'downloads:url' . $i ]  = sprintf( __( 'Download %d URL', 'woocommerce' ), $i );
					$row[ 'downloads:name' . $i ] = $download->get_name();
					$row[ 'downloads:url' . $i ]  = $download->get_file();
					$i++;
				}
			}
		}
	}

	/**
	 * Export attributes data.
	 *
	 * @since 3.1.0
	 * @param  WC_Product $product Product being exported.
	 * @param  array      $row     Row being exported.
	 */
	protected function prepare_attributes_for_export( $product, &$row ) {
		if ( $this->is_column_exporting( 'attributes' ) ) {
			$attributes         = $product->get_attributes();
			$default_attributes = $product->get_default_attributes();

			if ( count( $attributes ) ) {
				$i = 1;
				foreach ( $attributes as $attribute_name => $attribute ) {
					$this->column_names[ 'attributes:name' . $i ]     = sprintf( __( 'Attribute %d name', 'woocommerce' ), $i );
					$this->column_names[ 'attributes:value' . $i ]    = sprintf( __( 'Attribute %d value(s)', 'woocommerce' ), $i );
					$this->column_names[ 'attributes:visible' . $i ]  = sprintf( __( 'Attribute %d visible', 'woocommerce' ), $i );
					$this->column_names[ 'attributes:taxonomy' . $i ] = sprintf( __( 'Attribute %d global', 'woocommerce' ), $i );

					if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute->get_name(), $product );

						if ( $attribute->is_taxonomy() ) {
							$terms  = $attribute->get_terms();
							$values = array();

							foreach ( $terms as $term ) {
								$values[] = $term->name;
							}

							$row[ 'attributes:value' . $i ]    = $this->implode_values( $values );
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = $this->implode_values( $attribute->get_options() );
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = $attribute->get_visible();
					} else {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute_name, $product );

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $attribute, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:value' . $i ]    = $option_term && ! is_wp_error( $option_term ) ? str_replace( ',', '\\,', $option_term->name ) : $attribute;
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = $attribute;
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = '';
					}

					if ( $product->is_type( 'variable' ) && isset( $default_attributes[ sanitize_title( $attribute_name ) ] ) ) {
						$this->column_names[ 'attributes:default' . $i ] = sprintf( __( 'Attribute %d default', 'woocommerce' ), $i );
						$default_value                                   = $default_attributes[ sanitize_title( $attribute_name ) ];

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $default_value, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:default' . $i ]   = $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $default_value;
						} else {
							$row[ 'attributes:default' . $i ] = $default_value;
						}
					}
					$i++;
				}
			}
		}
	}

	/**
	 * Export meta data.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @param array      $row Row data.
	 */
	protected function prepare_meta_for_export( $product, &$row ) {
		if ( $this->enable_meta_export ) {
			$meta_data = $product->get_meta_data();

			if ( count( $meta_data ) ) {
				$meta_keys_to_skip = apply_filters( 'woocommerce_product_export_skip_meta_keys', array(), $product );

				$i = 1;
				foreach ( $meta_data as $meta ) {
					if ( in_array( $meta->key, $meta_keys_to_skip, true ) ) {
						continue;
					}

					// Allow 3rd parties to process the meta, e.g. to transform non-scalar values to scalar.
					$meta_value = apply_filters( 'woocommerce_product_export_meta_value', $meta->value, $meta, $product, $row );

					if ( ! is_scalar( $meta_value ) ) {
						continue;
					}

					$column_key                        = 'meta:' . esc_attr( $meta->key );
					$this->column_names[ $column_key ] = sprintf( __( 'Meta: %s', 'woocommerce' ), $meta->key );
					$row[ $column_key ]                = $meta_value;
					$i ++;
				}
			}
		}
	}
}
