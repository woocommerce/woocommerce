<?php
/**
 * Legacy Abstract Product
 *
 * Legacy and deprecated functions are here to keep the WC_Abstract_Product
 * clean.
 * This class will be removed in future versions.
 *
 * @version  2.7.0
 * @package  WooCommerce/Abstracts
 * @category Abstract Class
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Abstract Product class.
 */
abstract class WC_Abstract_Legacy_Product extends WC_Data {

	/**
	 * Get and return related products.
	 * @deprecated 2.7.0 Use wc_get_related_products instead.
	 */
	public function get_related( $limit = 5 ) {
		_deprecated_function( 'WC_Product::get_related', '2.7', 'wc_get_related_products' );
		return wc_get_related_products( $this->get_id(), $limit );
	}

	/**
	 * Retrieves related product terms.
	 * @deprecated 2.7.0 Use wc_get_product_term_ids instead.
	 */
	protected function get_related_terms( $term ) {
		_deprecated_function( 'WC_Product::get_related_terms', '2.7', 'wc_get_product_term_ids' );
		return array_merge( array( 0 ), wc_get_product_term_ids( $this->get_id(), $term ) );
	}

	/**
	 * Builds the related posts query.
	 * @deprecated 2.7.0 Use wc_get_related_products_query instead.
	 */
	protected function build_related_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		_deprecated_function( 'WC_Product::build_related_query', '2.7', 'wc_get_related_products_query' );
		return wc_get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit );
	}

	/**
	 * Returns the child product.
	 * @deprecated 2.7.0 Use wc_get_product instead.
	 * @param mixed $child_id
	 * @return WC_Product|WC_Product|WC_Product_variation
	 */
	public function get_child( $child_id ) {
		_deprecated_function( 'WC_Product::get_child', '2.7', 'wc_get_product' );
		return wc_get_product( $child_id );
	}

	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_price_html_from_text() {
		_deprecated_function( 'WC_Product::get_price_html_from_text', '2.7' );
		$from = '<span class="from">' . _x( 'From:', 'min_price', 'woocommerce' ) . ' </span>';
		return apply_filters( 'woocommerce_get_price_html_from_text', $from, $this );
	}

	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @deprecated 2.7.0 Use wc_format_price_range instead.
	 * @param  string $from String or float to wrap with 'from' text
	 * @param  mixed $to String or float to wrap with 'to' text
	 * @return string
	 */
	public function get_price_html_from_to( $from, $to ) {
		_deprecated_function( 'WC_Product::get_price_html_from_to', '2.7', 'wc_format_price_range' );
		return apply_filters( 'woocommerce_get_price_html_from_to', wc_format_price_range( $from, $to ), $from, $to, $this );
	}

	/**
	 * Get the suffix to display after prices > 0.
	 *
	 * @deprecated 2.7.0 Use wc_get_price_suffix instead.
	 * @param  string  $price to calculate, left blank to just use get_price()
	 * @param  integer $qty   passed on to get_price_including_tax() or get_price_excluding_tax()
	 * @return string
	 */
	public function get_price_suffix( $price = '', $qty = 1 ) {
		_deprecated_function( 'WC_Product::get_price_suffix', '2.7', 'wc_get_price_suffix' );
		return wc_get_price_suffix( $this, $price, $qty );
	}

	/**
	 * Lists a table of attributes for the product page.
	 * @deprecated 2.7.0 Use wc_display_product_attributes instead.
	 */
	public function list_attributes() {
		_deprecated_function( 'WC_Product::list_attributes', '2.7', 'wc_display_product_attributes' );
		wc_display_product_attributes( $this );
	}

	/**
	 * Returns the price (including tax). Uses customer tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @deprecated 2.7.0 Use wc_get_price_including_tax instead.
	 * @param  int $qty
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_including_tax( $qty = 1, $price = '' ) {
		_deprecated_function( 'WC_Product::get_price_including_tax', '2.7', 'wc_get_price_including_tax' );
		return wc_get_price_including_tax( $this, array( 'qty' => $qty, 'price' => $price ) );
	}

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @deprecated 2.7.0 Use wc_get_price_to_display instead.
	 * @param  string  $price to calculate, left blank to just use get_price()
	 * @param  integer $qty   passed on to get_price_including_tax() or get_price_excluding_tax()
	 * @return string
	 */
	public function get_display_price( $price = '', $qty = 1 ) {
		_deprecated_function( 'WC_Product::get_display_price', '2.7', 'wc_get_price_to_display' );
		return wc_get_price_to_display( $this, array( 'qty' => $qty, 'price' => $price ) );
	}

	/**
	 * Returns the price (excluding tax) - ignores tax_class filters since the price may *include* tax and thus needs subtracting.
	 * Uses store base tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @deprecated 2.7.0 Use wc_get_price_excluding_tax instead.
	 * @param  int $qty
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_excluding_tax( $qty = 1, $price = '' ) {
		_deprecated_function( 'WC_Product::get_price_excluding_tax', '2.7', 'wc_get_price_excluding_tax' );
		return wc_get_price_excluding_tax( $this, array( 'qty' => $qty, 'price' => $price ) );
	}

	/**
	 * Adjust a products price dynamically.
	 *
	 * @deprecated 2.7.0
	 * @param mixed $price
	 */
	public function adjust_price( $price ) {
		_deprecated_function( 'WC_Product::adjust_price', '2.7', 'WC_Product::set_price / WC_Product::get_price' );
		$this->data['price'] = $this->data['price'] + $price;
	}

	/**
	 * Returns the product categories.
	 *
	 * @deprecated 2.7.0
	 * @param string $sep (default: ', ').
	 * @param string $before (default: '').
	 * @param string $after (default: '').
	 * @return string
	 */
	public function get_categories( $sep = ', ', $before = '', $after = '' ) {
		_deprecated_function( 'WC_Product::get_categories', '2.7', 'wc_get_product_category_list' );
		return wc_get_product_category_list( $this->get_id(), $sep, $before, $after );
	}

	/**
	 * Returns the product tags.
	 *
	 * @deprecated 2.7.0
	 * @param string $sep (default: ', ').
	 * @param string $before (default: '').
	 * @param string $after (default: '').
	 * @return array
	 */
	public function get_tags( $sep = ', ', $before = '', $after = '' ) {
		_deprecated_function( 'WC_Product::get_tags', '2.7', 'wc_get_product_tag_list' );
		return wc_get_product_tag_list( $this->get_id(), $sep, $before, $after );
	}

	/**
	 * Returns the availability of the product.
	 *
	 * If stock management is enabled at global and product level, a stock message
	 * will be shown. e.g. In stock, In stock x10, Out of stock.
	 *
	 * If stock management is disabled at global or product level, out of stock
	 * will be shown when needed, but in stock will be hidden from view.
	 *
	 * This can all be changed through use of the woocommerce_get_availability filter.
	 *
	 * @return string
	 */
	public function get_availability() {
		return apply_filters( 'woocommerce_get_availability', array(
			'availability' => $this->get_availability_text(),
			'class'        => $this->get_availability_class(),
		), $this );
	}

	/**
	 * Get availability text based on stock status.
	 *
	 * @return string
	 */
	protected function get_availability_text() {
		if ( ! $this->is_in_stock() ) {
			$availability = __( 'Out of stock', 'woocommerce' );
		} elseif ( $this->managing_stock() && $this->is_on_backorder( 1 ) ) {
			$availability = $this->backorders_require_notification() ? __( 'Available on backorder', 'woocommerce' ) : __( 'In stock', 'woocommerce' );
		} elseif ( $this->managing_stock() ) {
			switch ( get_option( 'woocommerce_stock_format' ) ) {
				case 'no_amount' :
					$availability = __( 'In stock', 'woocommerce' );
				break;
				case 'low_amount' :
					if ( $this->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
						$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $this->get_total_stock() );

						if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
							$availability .= ' ' . __( '(also available on backorder)', 'woocommerce' );
						}
					} else {
						$availability = __( 'In stock', 'woocommerce' );
					}
				break;
				default :
					$availability = sprintf( __( '%s in stock', 'woocommerce' ), $this->get_total_stock() );

					if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
						$availability .= ' ' . __( '(also available on backorder)', 'woocommerce' );
					}
				break;
			}
		} else {
			$availability = '';
		}
		return apply_filters( 'woocommerce_get_availability_text', $availability, $this );
	}

	/**
	 * Get availability classname based on stock status.
	 *
	 * @return string
	 */
	protected function get_availability_class() {
		if ( ! $this->is_in_stock() ) {
			$class = 'out-of-stock';
		} elseif ( $this->managing_stock() && $this->is_on_backorder( 1 ) && $this->backorders_require_notification() ) {
			$class = 'available-on-backorder';
		} else {
			$class = 'in-stock';
		}

		return apply_filters( 'woocommerce_get_availability_class', $class, $this );
	}

	/**
	 * The product's type (simple, variable etc).
	 *
	 * @var string
	 */
	public $product_type = null;

	/**
	 * Product shipping class.
	 *
	 * @var string
	 */
	protected $shipping_class = '';

	/**
	 * ID of the shipping class this product has.
	 *
	 * @var int
	 */
	protected $shipping_class_id = 0;

	/** @public string The product's total stock, including that of its children. */
	public $total_stock;


	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 *
	 * @param  string $key Key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * Magic __get method for backwards compatibility.Maps legacy vars to new getters.
	 *
	 * @param  string $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, __( 'Product properties should not be accessed directly.', 'woocommerce' ), '2.7' );

		switch ( $key ) {
			case 'id' :
				$value = $this->get_id();
				break;
			case 'product_attributes' :
				$value = isset( $this->data['attributes'] ) ? $this->data['attributes'] : '';
				break;
			case 'visibility' :
				$value = $this->get_catalog_visibility();
				break;
			case 'sale_price_dates_from' :
				$value = $this->get_date_on_sale_from();
				break;
			case 'sale_price_dates_to' :
				$value = $this->get_date_on_sale_to();
				break;
			case 'post' :
				$value = get_post( $this->get_id() );
				break;
			case 'product_type' :  // @todo What do we do with 3rd party use of product_type now it's hardcoded?
				$value = $this->get_type();
				break;
			case 'product_image_gallery' :
				$value = $this->get_gallery_attachment_ids();
				break;
			default :
				$value = get_post_meta( $this->id, '_' . $key, true );

				// Get values or default if not set.
				if ( in_array( $key, array( 'downloadable', 'virtual', 'backorders', 'manage_stock', 'featured', 'sold_individually' ) ) ) {
					$value = $value ? $value : 'no';
				} elseif ( in_array( $key, array( 'product_attributes', 'crosssell_ids', 'upsell_ids' ) ) ) {
					$value = $value ? $value : array();
				} elseif ( 'stock' === $key ) {
					$value = $value ? $value : 0;
				} elseif ( 'stock_status' === $key ) {
					$value = $value ? $value : 'instock';
				} elseif ( 'tax_status' === $key ) {
					$value = $value ? $value : 'taxable';
				}
				break;
		}

		return $value;
	}

	/**
	 * Get the product's post data.
	 *
	 * @deprecated 2.7.0
	 * @return WP_Post
	 */
	public function get_post_data() {
		return get_post( $this->get_id() );
	}

	/**
	 * Get the title of the post.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_product_title', $this->get_post_data() ? $this->get_post_data()->post_title : '', $this );
	}

	/**
	 * Get the parent of the post.
	 *
	 * @deprecated 2.7.0
	 * @return int
	 */
	public function get_parent() {
		_deprecated_function( 'WC_Product::get_parent', '2.7', 'WC_Product::get_parent_id' );
		return apply_filters( 'woocommerce_product_parent', absint( $this->get_post_data()->post_parent ), $this );
	}

	/**
	 * Returns the upsell product ids.
	 *
	 * @deprecated 2.7.0
	 * @return array
	 */
	public function get_upsells() {
		_deprecated_function( 'WC_Product::get_upsells', '2.7', 'WC_Product::get_upsell_ids' );
		return apply_filters( 'woocommerce_product_upsell_ids', (array) maybe_unserialize( $this->upsell_ids ), $this );
	}

	/**
	 * Returns the cross sell product ids.
	 *
	 * @deprecated 2.7.0
	 * @return array
	 */
	public function get_cross_sells() {
		_deprecated_function( 'WC_Product::get_cross_sells', '2.7', 'WC_Product::get_cross_sell_ids' );
		return apply_filters( 'woocommerce_product_crosssell_ids', (array) maybe_unserialize( $this->crosssell_ids ), $this );
	}

	/**
	 * Check if variable product has default attributes set.
	 *
	 * @deprecated 2.7.0
	 * @return bool
	 */
	public function has_default_attributes() {
		_deprecated_function( 'WC_Product_Variable::has_default_attributes', '2.7', 'Check WC_Product::get_default_attributes directly' );
		if ( ! $this->get_default_attributes() ) {
			return true;
		}
		return false;
	}
}
