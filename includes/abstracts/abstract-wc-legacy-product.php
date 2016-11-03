<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
abstract class WC_Abstract_Legacy_Product extends WC_Data {

	/**
	 * The product's type (simple, variable etc).
	 * @deprecated 2.7.0 get_type() method should return string instead since this prop should not be changed or be public.
	 * @var string
	 */
	public $product_type = 'simple';

	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 *
	 * @param  string $key Key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return
			in_array( $key, array_merge( array(
				'variation_id',
				'variation_data',
				'variation_has_stock',
				'variation_shipping_class_id',
				'product_attributes',
				'visibility',
				'sale_price_dates_from',
				'sale_price_dates_to',
				'post',
				'download_type',
				'product_image_gallery',
				'variation_shipping_class',
				'shipping_class',
				'total_stock',
				'crosssell_ids',
				'parent',
			), array_keys( $this->data ) ) ) || metadata_exists( 'post', $this->get_id(), '_' . $key ) || metadata_exists( 'post', $this->get_parent_id(), '_' . $key );
	}

	/**
	 * Magic __get method for backwards compatibility. Maps legacy vars to new getters.
	 *
	 * @param  string $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, __( 'Product properties should not be accessed directly.', 'woocommerce' ), '2.7' );

		switch ( $key ) {
			case 'id' :
				$value = $this->is_type( 'variation' ) ? $this->get_parent_id() : $this->get_id();
				break;
			case 'variation_id' :
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
			case 'download_type' :
				return 'standard';
				break;
			case 'product_image_gallery' :
				$value = $this->get_gallery_image_ids();
				break;
			case 'variation_shipping_class' :
			case 'shipping_class' :
				$value = $this->get_shipping_class();
				break;
			case 'total_stock' :
				$value = $this->get_total_stock();
				break;
			case 'downloadable' :
			case 'virtual' :
			case 'manage_stock' :
			case 'featured' :
			case 'sold_individually' :
				$value = $this->{"get_$key"}() ? 'yes' : 'no';
				break;
			case 'crosssell_ids' :
				$value = $this->get_cross_sell_ids();
				break;
			case 'upsell_ids' :
				$value = $this->get_upsell_ids();
				break;
			case 'parent' :
				$value = wc_get_product( $this->get_parent_id() );
				break;
			case 'variation_data' :
				$value = wc_get_product_variation_attributes( $this->get_id() );
				break;
			case 'variation_has_stock' :
				$value = $this->managing_stock();
				break;
			case 'variation_shipping_class_id' :
				$value = $this->get_shipping_class_id();
				break;
			default :
				if ( in_array( $key, array_keys( $this->data ) ) ) {
					$value = $this->{"get_$key"}();
				} else {
					$value = get_post_meta( $this->id, '_' . $key, true );
				}
				break;
		}
		return $value;
	}

	/**
	 * If set, get the default attributes for a variable product.
	 *
	 * @deprecated 2.7.0
	 * @return array
	 */
	public function get_variation_default_attributes() {
		_deprecated_function( 'WC_Product_Variable::get_variation_default_attributes', '2.7', 'WC_Product::get_default_attributes' );
		return apply_filters( 'woocommerce_product_default_attributes', array_filter( (array) maybe_unserialize( $this->get_default_attributes() ) ), $this );
	}

	/**
	 * Returns the gallery attachment ids.
	 *
	 * @deprecated 2.7.0
	 * @return array
	 */
	public function get_gallery_attachment_ids() {
		_deprecated_function( 'WC_Product::get_gallery_attachment_ids', '2.7', 'WC_Product::get_gallery_image_ids' );
		return $this->get_gallery_image_ids();
	}

	/**
	 * Set stock level of the product.
	 *
	 * @deprecated 2.7.0
	 */
	public function set_stock( $amount = null, $mode = 'set' ) {
		_deprecated_function( 'WC_Product::set_stock', '2.7', 'wc_update_product_stock' );
		return wc_update_product_stock( $this, $amount, $mode );
	}

	/**
	 * Reduce stock level of the product.
	 *
	 * @deprecated 2.7.0
	 * @param int $amount Amount to reduce by. Default: 1
	 * @return int new stock level
	 */
	public function reduce_stock( $amount = 1 ) {
		_deprecated_function( 'WC_Product::reduce_stock', '2.7', 'wc_update_product_stock' );
		wc_update_product_stock( $this, $amount, 'subtract' );
	}

	/**
	 * Increase stock level of the product.
	 *
	 * @deprecated 2.7.0
	 * @param int $amount Amount to increase by. Default 1.
	 * @return int new stock level
	 */
	public function increase_stock( $amount = 1 ) {
		_deprecated_function( 'WC_Product::increase_stock', '2.7', 'wc_update_product_stock' );
		wc_update_product_stock( $this, $amount, 'add' );
	}

	/**
	 * Check if the stock status needs changing.
	 *
	 * @deprecated 2.7.0
	 */
	public function check_stock_status() {
		_deprecated_function( 'WC_Product::check_stock_status', '2.7', 'wc_check_product_stock_status' );
		wc_check_product_stock_status( $this );
	}

	/**
	 * Returns the availability of the product.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_availability() {
		_deprecated_function( 'WC_Product::get_availability', '2.7', 'Handled in stock.php template file and wc_format_stock_for_display function.' );
		return apply_filters( 'woocommerce_get_availability', array(
			'availability' => $this->get_availability_text(),
			'class'        => $this->get_availability_class(),
		), $this );
	}

	/**
	 * Get availability text based on stock status.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	protected function get_availability_text() {
		_deprecated_function( 'WC_Product::get_availability_text', '2.7' );
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
	 * @deprecated 2.7.0
	 * @return string
	 */
	protected function get_availability_class() {
		_deprecated_function( 'WC_Product::get_availability_class', '2.7' );
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
	 * Get the product's post data.
	 *
	 * @deprecated 2.7.0
	 * @return WP_Post
	 */
	public function get_post_data() {
		_deprecated_function( 'WC_Product::get_post_data', '2.7', 'get_post' );
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

	/**
	 * Get variation ID.
	 *
	 * @deprecated 2.7.0
	 * @return int
	 */
	public function get_variation_id() {
		_deprecated_function( 'WC_Product::get_variation_id', '2.7', 'WC_Product::get_id() will always be the variation ID if this is a variation.' );
		return $this->get_id();
	}

	/**
	 * Get product variation description.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_variation_description() {
		_deprecated_function( 'WC_Product::get_variation_description', '2.7', 'WC_Product::get_description()' );
		return $this->get_description();
	}

	/**
	 * Check if all variation's attributes are set.
	 *
	 * @deprecated 2.7.0
	 * @return boolean
	 */
	public function has_all_attributes_set() {
		_deprecated_function( 'WC_Product::has_all_attributes_set', '2.7' );
		$set = true;

		// undefined attributes have null strings as array values
		foreach ( $this->get_variation_attributes() as $att ) {
			if ( ! $att ) {
				$set = false;
				break;
			}
		}
		return $set;
	}

	/**
	 * Returns whether or not the variations parent is visible.
	 *
	 * @deprecated 2.7.0
	 * @return bool
	 */
	public function parent_is_visible() {
		_deprecated_function( 'WC_Product::parent_is_visible', '2.7' );
		return $this->is_visible();
	}

	/**
	 * Get total stock - This is the stock of parent and children combined.
	 *
	 * @deprecated 2.7.0
	 * @return int
	 */
	public function get_total_stock() {
		_deprecated_function( 'WC_Product::get_total_stock', '2.7', 'Use get_stock_quantity on each child. Beware of performance issues in doing so.' );
		if ( empty( $this->total_stock ) ) {
			if ( sizeof( $this->get_children() ) > 0 ) {
				$this->total_stock = max( 0, $this->get_stock_quantity() );

				foreach ( $this->get_children() as $child_id ) {
					if ( 'yes' === get_post_meta( $child_id, '_manage_stock', true ) ) {
						$stock = get_post_meta( $child_id, '_stock', true );
						$this->total_stock += max( 0, wc_stock_amount( $stock ) );
					}
				}
			} else {
				$this->total_stock = $this->get_stock_quantity();
			}
		}
		return wc_stock_amount( $this->total_stock );
	}

	/**
	 * Get formatted variation data with WC < 2.4 back compat and proper formatting of text-based attribute names.
	 *
	 * @deprecated 2.7.0
	 * @return string
	 */
	public function get_formatted_variation_attributes( $flat = false ) {
		_deprecated_function( 'WC_Product::get_formatted_variation_attributes', '2.7', 'wc_get_formatted_variation' );
		return wc_get_formatted_variation( $this->get_variation_attributes(), $flat );
	}
}
