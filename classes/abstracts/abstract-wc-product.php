<?php
/**
 * Abstract Product Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @class 		WC_Product
 * @version		2.0.0
 * @package		WooCommerce/Abstracts
 * @category	Abstract Class
 * @author 		WooThemes
 */
class WC_Product {

	/** @var int The product (post) ID. */
	public $id;

	/** @var object The actual post object. */
	public $post;

	/** @var string The product's type (simple, variable etc). */
	public $product_type = null;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {

		if ( ( function_exists( 'get_called_class' ) && get_called_class() == 'WC_Product' ) || ( ! function_exists( 'get_called_class' ) && is_null( $this->product_type ) ) ) {
			_doing_it_wrong( 'WC_Product', __( 'The <code>WC_Product</code> class is now abstract. Use <code>get_product()</code> to instantiate an instance of a product instead of calling this class directly.', 'woocommerce' ), '2.0 of WooCommerce' );

			$product = get_product( $product );
		}

		if ( is_object( $product ) ) {
			$this->id   = absint( $product->ID );
			$this->post = $product;
		} else {
			$this->id   = absint( $product );
			$this->post = get_post( $this->id );
		}
	}

	/**
	 * __isset function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {

		// Get values or default if not set
		if ( in_array( $key, array( 'downloadable', 'virtual', 'backorders', 'manage_stock', 'featured', 'sold_individually' ) ) )
			$value = ( $value = get_post_meta( $this->id, '_' . $key, true ) ) ? $value : 'no';

		elseif( in_array( $key, array( 'product_attributes', 'crosssell_ids', 'upsell_ids' ) ) )
			$value = ( $value = get_post_meta( $this->id, '_' . $key, true ) ) ? $value : array();

		elseif ( 'visibility' == $key )
			$value = ( $value = get_post_meta( $this->id, '_visibility', true ) ) ? $value : 'hidden';

		elseif ( 'stock' == $key )
			$value = ( $value = get_post_meta( $this->id, '_stock', true ) ) ? $value : 0;

		elseif ( 'stock_status' == $key )
			$value = ( $value = get_post_meta( $this->id, '_stock_status', true ) ) ? $value : 'instock';

		elseif ( 'tax_status' == $key )
			$value = ( $value = get_post_meta( $this->id, '_tax_status', true ) ) ? $value : 'taxable';

		else
			$value = get_post_meta( $this->id, '_' . $key, true );

		return $value;
	}

	/**
	 * Get the product's post data.
	 *
	 * @access public
	 * @return object
	 */
	public function get_post_data() {
		return $this->post;
	}

	/**
	 * get_gallery_attachment_ids function.
	 *
	 * @access public
	 * @return array
	 */
	function get_gallery_attachment_ids() {
		if ( ! isset( $this->product_image_gallery ) ) {
			// Backwards compat
			$attachment_ids = array_diff( get_posts( 'post_parent=' . $this->id . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids' ), array( get_post_thumbnail_id() ) );
			$this->product_image_gallery = implode( ',', $attachment_ids );
		}

		return array_filter( (array) explode( ',', $this->product_image_gallery ) );
	}

	/**
     * Get SKU (Stock-keeping unit) - product unique ID.
     *
     * @return string
     */
    function get_sku() {
        return $this->sku;
    }


    /**
     * Returns number of items available for sale.
     *
     * @access public
     * @return int
     */
    function get_stock_quantity() {
        return $this->managing_stock() ? apply_filters( 'woocommerce_stock_amount', $this->stock ) : '';
    }


    /**
     * Get total stock.
     *
     * @access public
     * @return int
     */
    function get_total_stock() {
		return $this->get_stock_quantity();
    }


	/**
	 * Set stock level of the product.
	 *
	 * @access public
	 * @param mixed $amount (default: null)
	 * @return int Stock
	 */
	function set_stock( $amount = null ) {
		global $woocommerce;

		if ( $this->managing_stock() && ! is_null( $amount ) ) {
			$this->stock = intval( $amount );
			update_post_meta( $this->id, '_stock', $this->stock );

			// Out of stock attribute
			if ( ! $this->backorders_allowed() && $this->get_total_stock() <= 0 )
				$this->set_stock_status( 'outofstock' );
			elseif ( $this->backorders_allowed() || $this->get_total_stock() > 0 )
				$this->set_stock_status( 'instock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return $this->get_stock_quantity();
		}
	}


	/**
	 * Reduce stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to reduce by.
	 * @return int Stock
	 */
	function reduce_stock( $by = 1 ) {
		global $woocommerce;

		if ( $this->managing_stock() ) {
			$this->stock = $this->stock - $by;
			update_post_meta( $this->id, '_stock', $this->stock );

			// Out of stock attribute
			if ( ! $this->backorders_allowed() && $this->get_total_stock() <= 0 )
				$this->set_stock_status( 'outofstock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return $this->get_stock_quantity();
		}
	}


	/**
	 * Increase stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to increase by
	 * @return int Stock
	 */
	function increase_stock( $by = 1 ) {
		global $woocommerce;

		if ( $this->managing_stock() ) {
			$this->stock = $this->stock + $by;
			update_post_meta( $this->id, '_stock', $this->stock );

			// Out of stock attribute
			if ( $this->backorders_allowed() || $this->get_total_stock() > 0 )
				$this->set_stock_status( 'instock' );

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return $this->get_stock_quantity();
		}
	}


	/**
	 * set_stock_status function.
	 *
	 * @access public
	 * @return void
	 */
	function set_stock_status( $status ) {
		$status = 'outofstock' ? 'outofstock' : 'instock';

		if ( $this->stock_status != $status ) {
			update_post_meta( $this->id, '_stock_status', $status );

			do_action( 'woocommerce_product_set_stock_status', $this->id, $status );
		}
	}


	/**
	 * Checks the product type.
	 *
	 * Backwards compat with downloadable/virtual.
	 *
	 * @access public
	 * @param mixed $type Array or string of types
	 * @return bool
	 */
	function is_type( $type ) {
		return ( $this->product_type == $type || ( is_array( $type ) && in_array( $this->product_type, $type ) ) ) ? true : false;
	}


	/**
	 * Checks if a product is downloadable
	 *
	 * @access public
	 * @return bool
	 */
	function is_downloadable() {
		return $this->downloadable == 'yes' ? true : false;
	}


	/**
	 * Check if downloadable product has a file attached.
	 *
	 * @since 1.6.2
	 *
	 * @access public
	 * @param string $download_id file identifier
	 * @return bool Whether downloadable product has a file attached.
	 */
	function has_file( $download_id = '' ) {
		return ( $this->is_downloadable() && $this->get_file_download_path( $download_id ) ) ? true : false;
	}


	/**
	 * Get file download path identified by $download_id
	 *
	 * @access public
	 * @param string $download_id file identifier
	 * @return array
	 */
	function get_file_download_path( $download_id ) {

		$file_paths = isset( $this->file_paths ) ? $this->file_paths : '';
		$file_paths = maybe_unserialize( $file_paths );
		$file_paths = apply_filters( 'woocommerce_file_download_paths', $file_paths, $this->id, null, null );

		if ( ! $download_id && count( $file_paths ) == 1 ) {
			// backwards compatibility for old-style download URLs and template files
			$file_path = array_shift( $file_paths );
		} elseif ( isset( $file_paths[ $download_id ] ) ) {
			$file_path = $file_paths[ $download_id ];
		} else {
			$file_path = '';
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_file_download_path', $file_path, $this->id, $download_id );
	}


	/**
	 * Checks if a product is virtual (has no shipping).
	 *
	 * @access public
	 * @return bool
	 */
	function is_virtual() {
		return $this->virtual == 'yes' ? true : false;
	}


	/**
	 * Checks if a product needs shipping.
	 *
	 * @access public
	 * @return bool
	 */
	function needs_shipping() {
		return $this->is_virtual() ? false : true;
	}


	/**
	 * Check if a product is sold individually (no quantities)
	 *
	 * @access public
	 * @return bool
	 */
	function is_sold_individually() {
		$return = false;

		if ( 'yes' == $this->sold_individually || ( ! $this->backorders_allowed() && $this->get_stock_quantity() == 1 ) ) {
			$return = true;
		}

		return apply_filters( 'woocommerce_is_sold_individually', $return, $this );
	}

	/**
	 * get_children function.
	 *
	 * @access public
	 * @return bool
	 */
	function get_children() {
		return array();
	}


	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @access public
	 * @return bool
	 */
	function has_child() {
		return false;
	}


	/**
	 * Returns whether or not the product post exists.
	 *
	 * @access public
	 * @return bool
	 */
	function exists() {
		return empty( $this->post ) ? false : true;
	}


	/**
	 * Returns whether or not the product is taxable.
	 *
	 * @access public
	 * @return bool
	 */
	function is_taxable() {
		return $this->tax_status == 'taxable' && get_option( 'woocommerce_calc_taxes' ) == 'yes' ? true : false;
	}


	/**
	 * Returns whether or not the product shipping is taxable.
	 *
	 * @access public
	 * @return bool
	 */
	function is_shipping_taxable() {
		return $this->tax_status=='taxable' || $this->tax_status=='shipping' ? true : false;
	}


	/**
	 * Get the title of the post.
	 *
	 * @access public
	 * @return string
	 */
	function get_title() {
		return apply_filters( 'woocommerce_product_title', apply_filters( 'the_title', $this->post->post_title, $this->id ), $this );
	}


	/**
	 * Get the parent of the post.
	 *
	 * @access public
	 * @return int
	 */
	function get_parent() {
		return apply_filters('woocommerce_product_parent', $this->post->post_parent, $this);
	}


	/**
	 * Get the add to url.
	 *
	 * @access public
	 * @return string
	 */
	function add_to_cart_url() {
		return apply_filters( 'woocommerce_add_to_cart_url', add_query_arg( 'add-to-cart', $this->id ) );
	}


	/**
	 * Returns whether or not the product is stock managed.
	 *
	 * @access public
	 * @return bool
	 */
	function managing_stock() {
		return ( ! isset( $this->manage_stock ) || $this->manage_stock == 'no' || get_option('woocommerce_manage_stock') != 'yes' ) ? false : true;
	}


	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @access public
	 * @return bool
	 */
	function is_in_stock() {
		if ( $this->managing_stock() ) {

			if ( $this->backorders_allowed() ) {
				return true;
			} else {
				if ( $this->get_total_stock() <  1 ) {
					return false;
				} else {
					if ( $this->stock_status == 'instock' )
						return true;
					else
						return false;
				}
			}

		} else {

			if ( $this->stock_status == 'instock' )
				return true;
			else
				return false;

		}
	}


	/**
	 * Returns whether or not the product can be backordered.
	 *
	 * @access public
	 * @return bool
	 */
	function backorders_allowed() {
		return $this->backorders == 'yes' || $this->backorders == 'notify' ? true : false;
	}


	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @access public
	 * @return bool
	 */
	function backorders_require_notification() {
		return $this->managing_stock() && $this->backorders == 'notify' ? true : false;
	}


	/**
	 * is_on_backorder function.
	 *
	 * @access public
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	function is_on_backorder( $qty_in_cart = 0 ) {
		return $this->managing_stock() && $this->backorders_allowed() && ( $this->get_total_stock() - $qty_in_cart ) < 0 ? true : false;
	}


	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @access public
	 * @param mixed $quantity
	 * @return bool
	 */
	function has_enough_stock( $quantity ) {
		return ! $this->managing_stock() || $this->backorders_allowed() || $this->stock >= $quantity ? true : false;
	}


	/**
	 * Returns the availability of the product.
	 *
	 * @access public
	 * @return string
	 */
	function get_availability() {

		$availability = $class = "";

		if ( $this->managing_stock() ) {
			if ( $this->is_in_stock() ) {

				if ( $this->get_total_stock() > 0 ) {

					$format_option = get_option( 'woocommerce_stock_format' );

					switch ( $format_option ) {
						case 'no_amount' :
							$format = __( 'In stock', 'woocommerce' );
						break;
						case 'low_amount' :
							$low_amount = get_option( 'woocommerce_notify_low_stock_amount' );

							$format = ( $this->get_total_stock() <= $low_amount ) ? __( 'Only %s left in stock', 'woocommerce' ) : __( 'In stock', 'woocommerce' );
						break;
						default :
							$format = __( '%s in stock', 'woocommerce' );
						break;
					}

					$availability = sprintf( $format, $this->stock );

					if ( $this->backorders_allowed() && $this->backorders_require_notification() )
						$availability .= ' ' . __( '(backorders allowed)', 'woocommerce' );

				} else {

					if ( $this->backorders_allowed() ) {
						if ( $this->backorders_require_notification() ) {
							$availability = __( 'Available on backorder', 'woocommerce' );
							$class        = 'available-on-backorder';
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					} else {
						$availability = __( 'Out of stock', 'woocommerce' );
						$class        = 'out-of-stock';
					}

				}

			} elseif ( $this->backorders_allowed() ) {
				$availability = __( 'Available on backorder', 'woocommerce' );
				$class        = 'available-on-backorder';
			} else {
				$availability = __( 'Out of stock', 'woocommerce' );
				$class        = 'out-of-stock';
			}
		} elseif ( ! $this->is_in_stock() ) {
			$availability = __( 'Out of stock', 'woocommerce' );
			$class        = 'out-of-stock';
		}

		return apply_filters( 'woocommerce_get_availability', array( 'availability' => $availability, 'class' => $class ), $this );
	}


	/**
	 * Returns whether or not the product is featured.
	 *
	 * @access public
	 * @return bool
	 */
	function is_featured() {
		return $this->featured == 'yes' ? true : false;
	}


	/**
	 * Returns whether or not the product is visible.
	 *
	 * @access public
	 * @return bool
	 */
	function is_visible() {

		$visible = true;

		// Out of stock visibility
		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) == 'yes' && ! $this->is_in_stock() ) $visible = false;

		// visibility setting
		elseif ( $this->visibility == 'hidden' ) $visible = false;
		elseif ( $this->visibility == 'visible' ) $visible = true;

		// Visibility in loop
		elseif ( $this->visibility == 'search' && is_search() ) $visible = true;
		elseif ( $this->visibility == 'search' && ! is_search() ) $visible = false;
		elseif ( $this->visibility == 'catalog' && is_search() ) $visible = false;
		elseif ( $this->visibility == 'catalog' && ! is_search() ) $visible = true;

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->id );
	}


	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @access public
	 * @return bool
	 */
	function is_on_sale() {
		return $this->sale_price && $this->sale_price == $this->price ? true : false;
	}


	/**
	 * Returns the product's weight.
	 *
	 * @access public
	 * @return string
	 */
	function get_weight() {
		if ( $this->weight ) return $this->weight;
	}


	/**
	 * Set a products price dynamically.
	 *
	 * @access public
	 * @param float $price Price to set.
	 * @return void
	 */
	function set_price( $price ) {
		$this->price = $price;
	}


	/**
	 * Adjust a products price dynamically.
	 *
	 * @access public
	 * @param mixed $price
	 * @return void
	 */
	function adjust_price( $price ) {
		if ( $price > 0 )
			$this->price += $price;
		else
			$this->price = $this->price - $price;
	}


	/**
	 * Returns the product's price.
	 *
	 * @access public
	 * @return string
	 */
	function get_price() {
		return apply_filters( 'woocommerce_get_price', $this->price, $this );
	}


	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 * @return cool
	 */
	function is_purchasable() {

		$purchasable = true;

		// Products must exist of course
		if ( ! $this->exists() )
			$purchasable = false;

		// Other products types need a price to be set
		elseif ( $this->get_price() === '' )
			$purchasable = false;

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}


	/**
	 * Returns the price (including tax). Uses customer tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @access public
	 * @return string
	 */
	function get_price_including_tax( $qty = 1 ) {
		global $woocommerce;

		$_tax  = new WC_Tax();
		$price = $this->get_price();

		if ( $this->is_taxable() ) {

			if ( get_option('woocommerce_prices_include_tax') == 'no' ) {

				$tax_rates  = $_tax->get_rates( $this->get_tax_class() );
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = $_tax->get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, 2 );

			} else {

				$tax_rates      = $_tax->get_rates( $this->get_tax_class() );
				$base_tax_rates = $_tax->get_shop_base_rate( $this->tax_class );

				if ( $woocommerce->customer->is_vat_exempt() ) {

					$base_taxes 		= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					$price      		= round( $price * $qty - $base_tax_amount, 2 );

				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes			= $_tax->calc_tax( $price * $qty, $base_tax_rates, true, true );
					$modded_taxes		= $_tax->calc_tax( $price * $qty - array_sum( $base_taxes ), $tax_rates, false );
					$price      		= round( $price * $qty - array_sum( $base_taxes ) + array_sum( $modded_taxes ), 2 );

				} else {

					$price = $price * $qty;

				}

			}

		} else {
			$price = $price * $qty;
		}

		return apply_filters( 'woocommerce_get_price_including_tax', $price, $qty, $this );
	}


	/**
	 * Returns the price (excluding tax) - ignores tax_class filters since the price may *include* tax and thus needs subtracting.
	 * Uses store base tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @access public
	 * @return string
	 */
	function get_price_excluding_tax( $qty = 1 ) {

		$price = $this->get_price();

		if ( $this->is_taxable() && get_option('woocommerce_prices_include_tax') == 'yes' ) {

			$_tax       = new WC_Tax();
			$tax_rates  = $_tax->get_shop_base_rate( $this->tax_class );
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$tax_amount = $_tax->get_tax_total( $taxes );
			$price      = round( $price * $qty - $tax_amount, 2 );

		} else {
			$price = $price * $qty;
		}

		return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $qty, $this );
	}


	/**
	 * Returns the tax class.
	 *
	 * @access public
	 * @return string
	 */
	function get_tax_class() {
		return apply_filters( 'woocommerce_product_tax_class', $this->tax_class, $this );
	}


	/**
	 * Returns the tax status.
	 *
	 * @access public
	 * @return string
	 */
	function get_tax_status() {
		return $this->tax_status;
	}


	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	function get_price_html( $price = '' ) {

		if ( $this->price > 0 ) {

			if ( $this->is_on_sale() && isset( $this->regular_price ) ) {

				$price .= $this->get_price_html_from_to( $this->regular_price, $this->get_price() );

				$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

			} else {

				$price .= woocommerce_price( $this->get_price() );

				$price = apply_filters( 'woocommerce_price_html', $price, $this );

			}
		} elseif ( $this->price === '' ) {

			$price = apply_filters( 'woocommerce_empty_price_html', '', $this );

		} elseif ( $this->price == 0 ) {

			if ( $this->is_on_sale() && isset( $this->regular_price ) ) {

				$price .= $this->get_price_html_from_to( $this->regular_price, __( 'Free!', 'woocommerce' ) );

				$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );

			} else {

				$price = __( 'Free!', 'woocommerce' );

				$price = apply_filters( 'woocommerce_free_price_html', $price, $this );

			}
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}


	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @access public
	 * @return string
	 */
	function get_price_html_from_text() {
		return '<span class="from">' . _x('From:', 'min_price', 'woocommerce') . ' </span>';
	}


	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @access public
	 * @return string
	 */
	function get_price_html_from_to( $from, $to ) {
		return '<del>' . ( ( is_numeric( $from ) ) ? woocommerce_price( $from ) : $from ) . '</del> <ins>' . ( ( is_numeric( $to ) ) ? woocommerce_price( $to ) : $to ) . '</ins>';
	}

	/**
	 * get_average_rating function.
	 *
	 * @access public
	 * @return void
	 */
	function get_average_rating() {
		if ( false === ( $average_rating = get_transient( 'wc_average_rating_' . $this->id ) ) ) {

			global $wpdb;

			$average_rating = '';
			$count          = $this->get_rating_count();

			if ( $count > 0 ) {

				$ratings = $wpdb->get_var( $wpdb->prepare("
					SELECT SUM(meta_value) FROM $wpdb->commentmeta
					LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
					WHERE meta_key = 'rating'
					AND comment_post_ID = %d
					AND comment_approved = '1'
				", $this->id ) );

				$average_rating = number_format( $ratings / $count, 2 );

			}

			set_transient( 'wc_average_rating_' . $this->id, $average_rating );
		}

		return $average_rating;
	}

	/**
	 * get_rating_count function.
	 *
	 * @access public
	 * @return void
	 */
	function get_rating_count() {
		if ( false === ( $count = get_transient( 'wc_rating_count_' . $this->id ) ) ) {

			global $wpdb;

			$count = $wpdb->get_var( $wpdb->prepare("
				SELECT COUNT(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
				AND meta_value > 0
			", $this->id ) );

			set_transient( 'wc_rating_count_' . $this->id, $count );
		}

		return $count;
	}

	/**
	 * Returns the product rating in html format - ratings are stored in transient cache.
	 *
	 * @access public
	 * @param string $location (default: '')
	 * @return void
	 */
	function get_rating_html( $location = '' ) {

		$average_rating = $this->get_average_rating();

		if ( $average_rating > 0 ) {

			if ( $location )
				$location = '_' . $location;

			$average_rating = $this->get_average_rating();

			$rating_html  = '<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $average_rating ) . '">';

			$rating_html .= '<span style="width:' . ( ( $average_rating / 5 ) * 100 ) . '%"><strong class="rating">' . $average_rating . '</strong> ' . __( 'out of 5', 'woocommerce' ) . '</span>';

			$rating_html .= '</div>';

			return $rating_html;
		}
	}


	/**
	 * Returns the upsell product ids.
	 *
	 * @access public
	 * @return array
	 */
	function get_upsells() {
		return (array) maybe_unserialize( $this->upsell_ids );
	}


	/**
	 * Returns the cross sell product ids.
	 *
	 * @access public
	 * @return array
	 */
	function get_cross_sells() {
		return (array) maybe_unserialize( $this->crosssell_ids );
	}


	/**
	 * Returns the product categories.
	 *
	 * @access public
	 * @param string $sep (default: ')
	 * @param mixed '
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return array
	 */
	function get_categories( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->id, 'product_cat', $before, $sep, $after );
	}


	/**
	 * Returns the product tags.
	 *
	 * @access public
	 * @param string $sep (default: ', ')
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return array
	 */
	function get_tags( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->id, 'product_tag', $before, $sep, $after );
	}


	/**
	 * Returns the product shipping class.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_class() {
		if ( ! $this->shipping_class ) {
			$classes = get_the_terms( $this->id, 'product_shipping_class' );
			if ( $classes && ! is_wp_error( $classes ) )
				$this->shipping_class = current( $classes )->slug;
			else
				$this->shipping_class = '';
		}
		return $this->shipping_class;
	}


	/**
	 * Returns the product shipping class ID.
	 *
	 * @access public
	 * @return int
	 */
	function get_shipping_class_id() {
		if ( ! $this->shipping_class_id ) {
			$classes = get_the_terms( $this->id, 'product_shipping_class' );
			if ( $classes && ! is_wp_error( $classes ) )
				$this->shipping_class_id = current( $classes )->term_id;
			else
				$this->shipping_class_id = 0;
		}
		return absint( $this->shipping_class_id );
	}


	/**
	 * Get and return related products.
	 *
	 * @access public
	 * @param int $limit (default: 5)
	 * @return array Array of post IDs
	 */
	function get_related( $limit = 5 ) {
		global $woocommerce;

		// Related products are found from category and tag
		$tags_array = array(0);
		$cats_array = array(0);

		// Get tags
		$terms = wp_get_post_terms($this->id, 'product_tag');
		foreach ( $terms as $term ) $tags_array[] = $term->term_id;

		// Get categories
		$terms = wp_get_post_terms($this->id, 'product_cat');
		foreach ( $terms as $term ) $cats_array[] = $term->term_id;

		// Don't bother if none are set
		if ( sizeof($cats_array)==1 && sizeof($tags_array)==1 ) return array();

		// Meta query
		$meta_query = array();
		$meta_query[] = $woocommerce->query->visibility_meta_query();
	    $meta_query[] = $woocommerce->query->stock_status_meta_query();

		// Get the posts
		$related_posts = get_posts( apply_filters('woocommerce_product_related_posts', array(
			'orderby'        => 'rand',
			'posts_per_page' => $limit,
			'post_type'      => 'product',
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
			'tax_query'      => array(
				'relation'      => 'OR',
				array(
					'taxonomy'     => 'product_cat',
					'field'        => 'id',
					'terms'        => $cats_array
				),
				array(
					'taxonomy'     => 'product_tag',
					'field'        => 'id',
					'terms'        => $tags_array
				)
			)
		) ) );

		$related_posts = array_diff( $related_posts, array( $this->id ), $this->get_upsells() );

		return $related_posts;
	}


	/**
	 * Returns a single product attribute.
	 *
	 * @access public
	 * @param mixed $attr
	 * @return string
	 */
	function get_attribute( $attr ) {
		$attributes = $this->get_attributes();

		$attr = sanitize_title( $attr );

		if ( isset( $attributes[ $attr ] ) || isset( $attributes[ 'pa_' . $attr ] ) ) {

			$attribute = isset( $attributes[ $attr ] ) ? $attributes[ $attr ] : $attributes[ 'pa_' . $attr ];

			if ( $attribute['is_taxonomy'] ) {

				return implode( ', ', woocommerce_get_product_terms( $this->id, $attribute['name'], 'names' ) );

			} else {

				return $attribute['value'];

			}

		}

		return '';
	}


	/**
	 * Returns product attributes.
	 *
	 * @access public
	 * @return array
	 */
	function get_attributes() {
		return (array) maybe_unserialize( $this->product_attributes );
	}


	/**
	 * Returns whether or not the product has any attributes set.
	 *
	 * @access public
	 * @return mixed
	 */
	function has_attributes() {
		if ( sizeof( $this->get_attributes() ) > 0 ) {
			foreach ( $this->get_attributes() as $attribute ) {
				if ( isset( $attribute['is_visible'] ) && $attribute['is_visible'] )
					return true;
			}
		}
		return false;
	}


	/**
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @access public
	 * @return bool
	 */
	function enable_dimensions_display() {
		return get_option( 'woocommerce_enable_dimension_product_attributes' ) == 'yes' ? true : false;
	}


	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @access public
	 * @return bool
	 */
	function has_dimensions() {
		return $this->get_dimensions() ? true : false;
	}


	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @access public
	 * @return bool
	 */
	function has_weight() {
		return $this->get_weight() ? true : false;
	}


	/**
	 * Returns dimensions.
	 *
	 * @access public
	 * @return string
	 */
	function get_dimensions() {
		if ( ! $this->dimensions ) {
			$dimensions = array();

			if ( $this->length )
				$dimensions[] = $this->length;

			if ( $this->width )
				$dimensions[] = $this->width;

			if ( $this->height )
				$dimensions[] = $this->height;

			$this->dimensions = implode( ' x ', $dimensions );

			if ( ! empty( $this->dimensions ) )
				$this->dimensions .= ' ' . get_option( 'woocommerce_dimension_unit' );

		}
		return $this->dimensions;
	}


	/**
	 * Lists a table of attributes for the product page.
	 *
	 * @access public
	 * @return void
	 */
	function list_attributes() {
		woocommerce_get_template( 'single-product/product-attributes.php', array(
			'product'    => $this
		) );
	}


    /**
     * Returns the main product image
     *
     * @access public
     * @param string $size (default: 'shop_thumbnail')
     * @return string
     */
    function get_image( $size = 'shop_thumbnail', $attr = array() ) {
    	global $woocommerce;

    	$image = '';

		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		} else {
			$image = woocommerce_placeholder_img( $size );
		}

		return $image;
    }
}