<?php
/**
 * Handles product JSON export.
 *
 * @package WooCommerce\Export
 * @version 3.1.0
 */

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use Automattic\WooCommerce\Utilities\I18nUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_JSON_Batch_Exporter', false ) ) {
	include_once WC_ABSPATH . 'includes/export/abstract-wc-json-batch-exporter.php';
}

/**
 * WC_Product_JSON_Exporter Class.
 */
class WC_Product_JSON_Exporter extends WC_JSON_Batch_Exporter {

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
	 * Products belonging to what category should be exported.
	 *
	 * @var array
	 */
	protected $product_category_to_export = array();

	/**
	 * Specific product IDs to export, overriding other filters if hook is not used.
	 *
	 * @var array
	 */
	protected $product_ids_to_export = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_product_types_to_export( array_keys( WC_Admin_Exporters::get_product_types() ) );
	}

	/**
	 * Should meta be exported?
	 *
	 * @param bool $enable_meta_export Should meta be exported.
	 *
	 * @since 3.1.0
	 */
	public function enable_meta_export( $enable_meta_export ) {
		$this->enable_meta_export = (bool) $enable_meta_export;
	}

	/**
	 * Product types to export.
	 *
	 * @param array $product_types_to_export List of types to export.
	 *
	 * @since 3.1.0
	 */
	public function set_product_types_to_export( $product_types_to_export ) {
		$this->product_types_to_export = array_map( 'wc_clean', $product_types_to_export );
	}

	/**
	 * Product category to export
	 *
	 * @param string $product_category_to_export Product category slug to export, empty string exports all.
	 *
	 * @since  3.5.0
	 * @return void
	 */
	public function set_product_category_to_export( $product_category_to_export ) {
		$this->product_category_to_export = array_map( 'sanitize_title_with_dashes', $product_category_to_export );
	}

	/**
	 * Specific product IDs to export.
	 *
	 * @param array $product_ids List of product IDs to export.
	 * @since 9.9.0
	 */
	public function set_product_ids_to_export( $product_ids ) {
		$this->product_ids_to_export = array_filter( array_map( 'absint', (array) $product_ids ) );
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		$weight_unit_label    = I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit', 'kg' ) );
		$dimension_unit_label = I18nUtil::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit', 'cm' ) );

		$default_columns = array(
			'id'                 => __( 'ID', 'woocommerce' ),
			'type'               => __( 'Type', 'woocommerce' ),
			'sku'                => __( 'SKU', 'woocommerce' ),
			'global_unique_id'   => __( 'GTIN, UPC, EAN, or ISBN', 'woocommerce' ),
			'name'               => __( 'Name', 'woocommerce' ),
			'published'          => __( 'Published', 'woocommerce' ),
			'featured'           => __( 'Is featured?', 'woocommerce' ),
			'catalog_visibility' => __( 'Visibility in catalog', 'woocommerce' ),
			'short_description'  => __( 'Short description', 'woocommerce' ),
			'description'        => __( 'Description', 'woocommerce' ),
			'status'             => __( 'Status', 'woocommerce' ),
			'date_on_sale_from'  => __( 'Date sale price starts', 'woocommerce' ),
			'date_on_sale_to'    => __( 'Date sale price ends', 'woocommerce' ),
			'on_sale'            => __( 'On sale?', 'woocommerce' ),
			'tax_status'         => __( 'Tax status', 'woocommerce' ),
			'tax_class'          => __( 'Tax class', 'woocommerce' ),
			'stock_status'       => __( 'In stock?', 'woocommerce' ),
			'stock'              => __( 'Stock', 'woocommerce' ),
			'low_stock_amount'   => __( 'Low stock amount', 'woocommerce' ),
			'backorders'         => __( 'Backorders allowed?', 'woocommerce' ),
			'backorders_allowed' => __( 'Backorders allowed?', 'woocommerce' ),
			'sold_individually'  => __( 'Sold individually?', 'woocommerce' ),
			'manage_stock'       => __( 'Manage stock?', 'woocommerce' ),
			'stock_quantity'     => __( 'Stock quantity', 'woocommerce' ),
			/* translators: %s: weight */
			'weight'             => sprintf( __( 'Weight (%s)', 'woocommerce' ), $weight_unit_label ),
			/* translators: %s: length */
			'length'             => sprintf( __( 'Length (%s)', 'woocommerce' ), $dimension_unit_label ),
			/* translators: %s: width */
			'width'              => sprintf( __( 'Width (%s)', 'woocommerce' ), $dimension_unit_label ),
			/* translators: %s: Height */
			'height'             => sprintf( __( 'Height (%s)', 'woocommerce' ), $dimension_unit_label ),
			'reviews_allowed'    => __( 'Allow customer reviews?', 'woocommerce' ),
			'purchase_note'      => __( 'Purchase note', 'woocommerce' ),
			'price'              => __( 'Current price', 'woocommerce' ),
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
			'attributes'         => __( 'Attributes', 'woocommerce' ),
			'downloadable'       => __( 'Downloadable', 'woocommerce' ),
		);

		if ( wc_get_container()->get( CostOfGoodsSoldController::class )->feature_is_enabled() ) {
			$default_columns['cogs_value'] = __( 'Cost of goods', 'woocommerce' );
		}

		return apply_filters(
			"woocommerce_product_export_{$this->export_type}_default_columns",
			$default_columns
		);
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 3.1.0
	 */
	public function prepare_data_to_export() {
		// Memory logging - start
		$start_memory = memory_get_usage();
		$start_peak = memory_get_peak_usage();
		error_log("WC JSON Export Memory - Start: " . round($start_memory / 1024 / 1024, 2) . " MB, Peak: " . round($start_peak / 1024 / 1024, 2) . " MB, Page: " . $this->get_page());

		$args = array(
			'status'   => array( ProductStatus::PRIVATE, ProductStatus::PUBLISH, ProductStatus::DRAFT, ProductStatus::FUTURE, ProductStatus::PENDING ),
			'limit'    => $this->get_limit(),
			'page'     => $this->get_page(),
			'orderby'  => array(
				'ID' => 'ASC',
			),
			'return'   => 'objects',
			'paginate' => true,
		);

		// Set up query args based on whether specific IDs are being exported.
		if ( ! empty( $this->product_ids_to_export ) ) {
			$args['include'] = $this->product_ids_to_export;
		} else {
			$args['type'] = $this->product_types_to_export;
			if ( ! empty( $this->product_category_to_export ) ) {
				$args['category'] = $this->product_category_to_export;
			}
		}

		$args = apply_filters( "woocommerce_product_export_{$this->export_type}_query_args", $args );

		if ( ! empty( $args['include'] ) ) {
			$args['include'] = array_map( 'absint', (array) $args['include'] );
		}

		$products = wc_get_products( $args );

		// Memory logging - after query
		$after_query_memory = memory_get_usage();
		$after_query_peak = memory_get_peak_usage();
		error_log("WC JSON Export Memory - After query: " . round($after_query_memory / 1024 / 1024, 2) . " MB, Peak: " . round($after_query_peak / 1024 / 1024, 2) . " MB, Products fetched: " . count($products->products));

		$this->total_rows  = $products->total;
		$this->row_data    = array();
		$variable_products = array();

		foreach ( $products->products as $product ) {
			if ( ( ! empty( $args['include'] ) || ! empty( $args['category'] ) ) &&
				$product->is_type( ProductType::VARIABLE ) &&
				! in_array( $product->get_id(), $variable_products, true ) ) {
				$variable_products[] = $product->get_id();
			}

			$this->row_data[] = $this->generate_row_data( $product );
		}

		// Memory logging - after main products processed
		$after_main_memory = memory_get_usage();
		$after_main_peak = memory_get_peak_usage();
		error_log("WC JSON Export Memory - After main products: " . round($after_main_memory / 1024 / 1024, 2) . " MB, Peak: " . round($after_main_peak / 1024 / 1024, 2) . " MB, Variable products found: " . count($variable_products));

		// Process variable product variations
		if ( ! empty( $variable_products ) ) {
			foreach ( $variable_products as $parent_id ) {
				$products = wc_get_products(
					array(
						'parent' => $parent_id,
						'type'   => array( ProductType::VARIATION ),
						'return' => 'objects',
						'limit'  => -1,
					)
				);

				if ( ! $products ) {
					continue;
				}

				foreach ( $products as $product ) {
					$this->row_data[] = $this->generate_row_data( $product );
				}
			}
		}

		// Memory logging - final after variations processed
		$final_memory = memory_get_usage();
		$final_peak = memory_get_peak_usage();
		error_log("WC JSON Export Memory - Final: " . round($final_memory / 1024 / 1024, 2) . " MB, Peak: " . round($final_peak / 1024 / 1024, 2) . " MB, Total rows: " . count($this->row_data));
	}

	/**
	 * Take a product and generate row data from it for export.
	 *
	 * @param WC_Product $product WC_Product object.
	 *
	 * @return array
	 */
	protected function generate_row_data( $product ) {
		$columns = $this->get_column_names();
		$row     = array();

		foreach ( $columns as $column_id => $column_name ) {
			$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';

			if ( in_array( $column_id, array( 'downloads', 'attributes', 'meta' ), true ) || ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}

			// Handle custom column values with switch case
			switch ( $column_id ) {
				case 'price':
					$value = $product->get_price();
					break;
				case 'type':
					$value = $product->get_type();
					break;
				case 'downloadable':
					$value = $product->is_downloadable();
					break;
				case 'status':
					$value = $product->get_status();
					break;
				case 'on_sale':
					$value = $product->is_on_sale();
					break;
				case 'manage_stock':
					$value = $product->managing_stock();
					break;
				case 'stock_quantity':
					$value = $product->get_stock_quantity();
					break;
				case 'backorders':
					$value = $product->get_backorders();
					break;
				case 'backorders_allowed':
					$value = $product->backorders_allowed();
					break;
				default:
					if ( has_filter( "woocommerce_product_export_{$this->export_type}_column_{$column_id}" ) ) {
						$value = apply_filters( "woocommerce_product_export_{$this->export_type}_column_{$column_id}", '', $product, $column_id );
					} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
						$value = $this->{"get_column_value_{$column_id}"}( $product );
					} elseif ( is_callable( array( $product, "get_{$column_id}" ) ) ) {
						$value = $product->{"get_{$column_id}"}( 'edit' );
					}
					break;
			}

			$row[ $column_id ] = $value;
		}

		$this->prepare_downloads_for_export( $product, $row );
		$this->prepare_attributes_for_export( $product, $row );
		$this->prepare_meta_for_export( $product, $row );

		// Apply type casting to ensure API consistency
		$row = $this->cast_row_data_to_api_types( $row );

		return apply_filters( 'woocommerce_product_export_row_data', $row, $product, $this );
	}

	/**
	 * Cast entire row data to expected API types.
	 *
	 * @param array $row Row data to cast.
	 * @return array Row data with proper types.
	 */
	protected function cast_row_data_to_api_types( $row ) {
		foreach ( $row as $column_id => $value ) {
			$row[ $column_id ] = $this->cast_value_to_api_type( $column_id, $value );
		}
		return $row;
	}

	/**
	 * Cast value to expected API type based on column ID.
	 *
	 * @param string $column_id Column identifier.
	 * @param mixed  $value     Raw value.
	 * @return mixed Properly typed value.
	 */
	protected function cast_value_to_api_type( $column_id, $value ) {
		// Boolean fields
		$boolean_fields = array(
			'featured',
			'virtual',
			'downloadable',
			'manage_stock',
			'sold_individually',
			'reviews_allowed',
			'on_sale',
			'backorders_allowed',
		);

		// Integer/numeric fields
		$integer_fields = array(
			'id',
			'parent_id',
			'menu_order',
			'stock',
			'low_stock_amount',
			'download_limit',
			'download_expiry',
			'shipping_class_id',
			'stock_quantity',
		);

		// Float/decimal fields
		$float_fields = array(
			'weight',
			'length',
			'width',
			'height',
		);

		// Price fields (keep as strings but ensure numeric format)
		$price_fields = array(
			'regular_price',
			'sale_price',
			'price',
		);

		// String fields that should always be strings
		$string_fields = array(
			'sku',
			'name',
			'description',
			'short_description',
			'status',
			'catalog_visibility',
			'tax_status',
			'tax_class',
			'stock_status',
			'backorders',
			'button_text',
			'purchase_note',
			'product_url',
			'global_unique_id',
			'type',
		);

		// Array fields that should always be arrays
		$array_fields = array(
			'category_ids',
			'tag_ids',
			'images',
			'upsell_ids',
			'cross_sell_ids',
			'grouped_products',
		);

		if ( in_array( $column_id, $boolean_fields, true ) ) {
			return (bool) $value;
		}

		if ( in_array( $column_id, $integer_fields, true ) ) {
			return is_numeric( $value ) ? (int) $value : $value;
		}

		if ( in_array( $column_id, $float_fields, true ) ) {
			return is_numeric( $value ) ? (float) $value : $value;
		}

		if ( in_array( $column_id, $price_fields, true ) ) {
			// Keep prices as strings but ensure they're properly formatted numbers
			return is_numeric( $value ) ? (string) $value : '';
		}

		if ( in_array( $column_id, $array_fields, true ) ) {
			return is_array( $value ) ? $value : array();
		}

		if ( in_array( $column_id, $string_fields, true ) ) {
			return (string) $value;
		}

		return $value;
	}

	/**
	 * Get published value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return int
	 */
	protected function get_column_value_published( $product ) {
		$statuses = array(
			ProductStatus::DRAFT   => -1,
			ProductStatus::PRIVATE => 0,
			ProductStatus::PUBLISH => 1,
		);

		if ( ProductType::VARIATION === $product->get_type() ) {
			$parent = $product->get_parent_data();
			$status = ProductStatus::DRAFT === $parent['status'] ? $parent['status'] : $product->get_status( 'edit' );
		} else {
			$status = $product->get_status( 'edit' );
		}

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : -1;
	}

	/**
	 * Get product_cat value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	protected function get_column_value_category_ids( $product ) {
		$term_ids = $product->get_category_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_cat' );
	}

	/**
	 * Get product_tag value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	protected function get_column_value_tag_ids( $product ) {
		$term_ids = $product->get_tag_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_tag' );
	}

	/**
	 * Get product_shipping_class value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	protected function get_column_value_shipping_class_id( $product ) {
		$term_ids = $product->get_shipping_class_id( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_shipping_class' );
	}

	/**
	 * Get images value.
	 * TODO: copied from WC_REST_Products_V1_Controller. Find a better way to do this.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	protected function get_column_value_images( $product ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

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
			$thumbnail = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'srcset'            => (string) wp_get_attachment_image_srcset( $attachment_id, 'full' ),
				'sizes'             => (string) wp_get_attachment_image_sizes( $attachment_id, 'full' ),
				'thumbnail'         => current( $thumbnail ),
			);
		}

		return $images;
	}

	/**
	 * Export downloads.
	 *
	 * @param WC_Product $product Product being exported.
	 * @param array      $row     Row being exported.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_downloads_for_export( $product, &$row ) {
		if ( $product->is_downloadable() && $this->is_column_exporting( 'downloads' ) ) {
			$downloads = $product->get_downloads( 'edit' );

			if ( $downloads ) {
				$download_data = array();
				foreach ( $downloads as $download ) {
					$download_data[] = array(
						'id'   => $download->get_id(),
						'name' => $download->get_name(),
						'url'  => $download->get_file(),
					);
				}
				$row['downloads'] = $download_data;
			}
		}
	}

	/**
	 * Export attributes data.
	 *
	 * @param WC_Product $product Product being exported.
	 * @param array      $row     Row being exported.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_attributes_for_export( $product, &$row ) {
		if ( $this->is_column_exporting( 'attributes' ) ) {
			$attributes = $this->get_attributes( $product );
			$row['attributes'] = $attributes;
		}
	}

	/**
	 * NOTE: from WC_REST_Products_V1_Controller
	 * Get the attributes for a product or product variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product Product instance.
	 * @return array
	 */
	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( ProductType::VARIATION ) ) {
			// Variation attributes.
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( ! $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_label( $name ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $name,
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				if ( $attribute['is_taxonomy'] ) {
					$attributes[] = array(
						'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
						'name'      => $this->get_attribute_taxonomy_label( $attribute['name'] ),
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				} else {
					$attributes[] = array(
						'id'        => 0,
						'name'      => $attribute['name'],
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				}
			}
		}

		return $attributes;
	}

	/**
	 * NOTE: from WC_REST_Products_V1_Controller
	 * Get attribute taxonomy label.
	 *
	 * @param  string $name Taxonomy name.
	 * @return string
	 */
	protected function get_attribute_taxonomy_label( $name ) {
		$tax    = get_taxonomy( $name );
		$labels = get_taxonomy_labels( $tax );

		return $labels->singular_name;
	}

	/**
	 * NOTE: from WC_REST_Products_V1_Controller
	 * Get attribute options.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $attribute  Attribute data.
	 * @return array
	 */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	}

	/**
	 * Export meta data.
	 *
	 * @param WC_Product $product Product being exported.
	 * @param array      $row Row data.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_meta_for_export( $product, &$row ) {
		if ( $this->enable_meta_export ) {
			$meta_data = $product->get_meta_data();

			if ( count( $meta_data ) ) {
				$meta_keys_to_skip = apply_filters( 'woocommerce_product_export_skip_meta_keys', array(), $product );
				$meta_export = array();

				foreach ( $meta_data as $meta ) {
					if ( in_array( $meta->key, $meta_keys_to_skip, true ) ) {
						continue;
					}

					$meta_value = apply_filters( 'woocommerce_product_export_meta_value', $meta->value, $meta, $product, $row );

					if ( ! is_scalar( $meta_value ) ) {
						continue;
					}

					$meta_export[ $meta->key ] = $meta_value;
				}

				if ( ! empty( $meta_export ) ) {
					$row['meta'] = $meta_export;
				}
			}
		}
	}

	/**
	 * Override parent format_data to preserve our API type casting.
	 *
	 * @param mixed $data Data to format.
	 * @return mixed Formatted data.
	 */
	public function format_data( $data ) {
		if ( is_a( $data, 'WC_Datetime' ) ) {
			return $data->date( 'Y-m-d H:i:s' );
		}

		// Don't auto-convert numeric strings to numbers - preserve our type casting
		return $data;
	}
}