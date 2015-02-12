<?php
/**
 * Abstract Product Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @class       WC_Product
 * @var         WP_Post
 * @version     2.1.0
 * @package     WooCommerce/Abstracts
 * @category    Abstract Class
 * @author      WooThemes
 *
 * @property    string $width Product width
 * @property    string $length Product length
 * @property    string $height Product height
 * @property    string $weight Product weight
 * @property    string $price Product price
 * @property    string $regular_price Product regular price
 * @property    string $sale_price Product sale price
 * @property    string $product_image_gallery String of image IDs in the gallery
 * @property    string $sku Product SKU
 * @property    string $stock Stock amount
 * @property    string $downloadable Shows/define if the product is downloadable
 * @property    string $virtual Shows/define if the product is virtual
 * @property    string $sold_individually Allow one item to be bought in a single order
 * @property    string $tax_status Tax status
 * @property    string $tax_class Tax class
 * @property    string $manage_stock Shows/define if can manage the product stock
 * @property    string $stock_status Stock status
 * @property    string $backorders Whether or not backorders are allowed
 * @property    string $featured Featured product
 * @property    string $visibility Product visibility
 * @property    string $variation_id Variation ID when dealing with variations
 */
class WC_Product {

	/**
	 * The product (post) ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * $post Stores post data
	 *
	 * @var $post WP_Post
	 */
	public $post = null;

	/**
	 * The product's type (simple, variable etc)
	 *
	 * @var string
	 */
	public $product_type = null;

	/**
	 * String of dimensions (imploded with X)
	 *
	 * @var string
	 */
	protected $dimensions = '';

	/**
	 * Prouduct shipping class
	 *
	 * @var string
	 */
	protected $shipping_class    = '';

	/**
	 * ID of the shipping class this product has
	 *
	 * @var int
	 */
	protected $shipping_class_id = 0;

	/**
	 * Constructor gets the post object and sets the ID for the loaded product.
	 *
	 * @param int|WC_Product|object $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		if ( is_numeric( $product ) ) {
			$this->id   = absint( $product );
			$this->post = get_post( $this->id );
		} elseif ( $product instanceof WC_Product ) {
			$this->id   = absint( $product->id );
			$this->post = $product->post;
		} elseif ( isset( $product->ID ) ) {
			$this->id   = absint( $product->ID );
			$this->post = $product;
		}
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		$value = get_post_meta( $this->id, '_' . $key, true );

		// Get values or default if not set
		if ( in_array( $key, array( 'downloadable', 'virtual', 'backorders', 'manage_stock', 'featured', 'sold_individually' ) ) ) {
			$value = $value ? $value : 'no';

		} elseif ( in_array( $key, array( 'product_attributes', 'crosssell_ids', 'upsell_ids' ) ) ) {
			$value = $value ? $value : array();

		} elseif ( 'visibility' === $key ) {
			$value = $value ? $value : 'hidden';

		} elseif ( 'stock' === $key ) {
			$value = $value ? $value : 0;

		} elseif ( 'stock_status' === $key ) {
			$value = $value ? $value : 'instock';

		} elseif ( 'tax_status' === $key ) {
			$value = $value ? $value : 'taxable';

		}

		if ( ! empty( $value ) ) {
			$this->$key = $value;
		}

		return $value;
	}

	/**
	 * Get the product's post data.
	 *
	 * @return object
	 */
	public function get_post_data() {
		return $this->post;
	}

	/**
	 * get_gallery_attachment_ids function.
	 *
	 * @return array
	 */
	public function get_gallery_attachment_ids() {
		return apply_filters( 'woocommerce_product_gallery_attachment_ids', array_filter( (array) explode( ',', $this->product_image_gallery ) ), $this );
	}

	/**
	 * Wrapper for get_permalink
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->id );
	}

	/**
	 * Get SKU (Stock-keeping unit) - product unique ID.
	 *
	 * @return string
	 */
	public function get_sku() {
		return apply_filters( 'woocommerce_get_sku', $this->sku, $this );
	}

	/**
	 * Returns number of items available for sale.
	 *
	 * @return int
	 */
	public function get_stock_quantity() {
		return $this->managing_stock() ? wc_stock_amount( $this->stock ) : '';
	}

	/**
	 * Get total stock.
	 *
	 * @return int
	 */
	public function get_total_stock() {
		return $this->get_stock_quantity();
	}

	/**
	 * Check if the stock status needs changing
	 */
	protected function check_stock_status() {

		// Update stock status
		if ( ! $this->backorders_allowed() && $this->get_total_stock() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$this->set_stock_status( 'outofstock' );

		} elseif ( $this->backorders_allowed() || $this->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$this->set_stock_status( 'instock' );
		}
	}

	/**
	 * Set stock level of the product.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * We cannot rely on the original loaded value in case another order was made since then.
	 *
	 * @param int $amount (default: null)
	 * @param string $mode can be set, add, or subtract
	 * @return int new stock level
	 */
	public function set_stock( $amount = null, $mode = 'set' ) {
		global $wpdb;

		if ( ! is_null( $amount ) && $this->managing_stock() ) {

			// Ensure key exists
			add_post_meta( $this->id, '_stock', 0, true );

			// Update stock in DB directly
			switch ( $mode ) {
				case 'add' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->id ) );
				break;
				case 'subtract' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->id ) );
				break;
				default :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->id ) );
				break;
			}

			// Clear caches
			wp_cache_delete( $this->id, 'post_meta' );

			// Stock status
			$this->check_stock_status();

			// Trigger action
			do_action( 'woocommerce_product_set_stock', $this );
		}

		return $this->get_stock_quantity();
	}

	/**
	 * Reduce stock level of the product.
	 *
	 * @param int $amount Amount to reduce by. Default: 1
	 * @return int new stock level
	 */
	public function reduce_stock( $amount = 1 ) {
		return $this->set_stock( $amount, 'subtract' );
	}

	/**
	 * Increase stock level of the product.
	 *
	 * @param int $amount Amount to increase by. Default 1.
	 * @return int new stock level
	 */
	public function increase_stock( $amount = 1 ) {
		return $this->set_stock( $amount, 'add' );
	}

	/**
	 * set_stock_status function.
	 *
	 * @param string $status
	 * @return void
	 */
	public function set_stock_status( $status ) {

		$status = ( 'outofstock' === $status ) ? 'outofstock' : 'instock';

		// Sanity check
		if ( $this->managing_stock() ) {
			if ( ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		}

		if ( update_post_meta( $this->id, '_stock_status', $status ) ) {
			do_action( 'woocommerce_product_set_stock_status', $this->id, $status );
		}
	}

	/**
	 * Checks the product type.
	 *
	 * Backwards compat with downloadable/virtual.
	 *
	 * @param string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->product_type == $type || ( is_array( $type ) && in_array( $this->product_type, $type ) ) ) ? true : false;
	}

	/**
	 * Checks if a product is downloadable
	 *
	 * @return bool
	 */
	public function is_downloadable() {
		return $this->downloadable == 'yes' ? true : false;
	}

	/**
	 * Check if downloadable product has a file attached.
	 *
	 * @since 1.6.2
	 *
	 * @param string $download_id file identifier
	 * @return bool Whether downloadable product has a file attached.
	 */
	public function has_file( $download_id = '' ) {
		return ( $this->is_downloadable() && $this->get_file( $download_id ) ) ? true : false;
	}

	/**
	 * Gets an array of downloadable files for this product.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	public function get_files() {

		$downloadable_files = array_filter( isset( $this->downloadable_files ) ? (array) maybe_unserialize( $this->downloadable_files ) : array() );

		if ( $downloadable_files ) {

			foreach ( $downloadable_files as $key => $file ) {

				if ( ! is_array( $file ) ) {
					$downloadable_files[ $key ] = array(
						'file' => $file,
						'name' => ''
					);
				}

				// Set default name
				if ( empty( $file['name'] ) ) {
					$downloadable_files[ $key ]['name'] = wc_get_filename_from_url( $file['file'] );
				}

				// Filter URL
				$downloadable_files[ $key ]['file'] = apply_filters( 'woocommerce_file_download_path', $downloadable_files[ $key ]['file'], $this, $key );
			}
		}

		return apply_filters( 'woocommerce_product_files', $downloadable_files, $this );
	}

	/**
	 * Get a file by $download_id
	 *
	 * @param string $download_id file identifier
	 * @return array|false if not found
	 */
	public function get_file( $download_id = '' ) {

		$files = $this->get_files();

		if ( '' === $download_id ) {
			$file = sizeof( $files ) ? current( $files ) : false;
		} elseif ( isset( $files[ $download_id ] ) ) {
			$file = $files[ $download_id ];
		} else {
			$file = false;
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_product_file', $file, $this, $download_id );
	}

	/**
	 * Get file download path identified by $download_id
	 *
	 * @param string $download_id file identifier
	 * @return string
	 */
	public function get_file_download_path( $download_id ) {
		$files = $this->get_files();

		if ( isset( $files[ $download_id ] ) ) {
			$file_path = $files[ $download_id ]['file'];
		} else {
			$file_path = '';
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_product_file_download_path', $file_path, $this, $download_id );
	}

	/**
	 * Checks if a product is virtual (has no shipping).
	 *
	 * @return bool
	 */
	public function is_virtual() {
		return $this->virtual == 'yes' ? true : false;
	}

	/**
	 * Checks if a product needs shipping.
	 *
	 * @return bool
	 */
	public function needs_shipping() {
		return apply_filters( 'woocommerce_product_needs_shipping', $this->is_virtual() ? false : true, $this );
	}

	/**
	 * Check if a product is sold individually (no quantities)
	 *
	 * @return bool
	 */
	public function is_sold_individually() {

		$return = false;

		if ( 'yes' == $this->sold_individually ) {
			$return = true;
		}

		return apply_filters( 'woocommerce_is_sold_individually', $return, $this );
	}

	/**
	 * get_child function.
	 *
	 * @param mixed $child_id
	 * @return WC_Product WC_Product or WC_Product_variation
	 */
	public function get_child( $child_id ) {
		return wc_get_product( $child_id );
	}

	/**
	 * get_children function.
	 *
	 * @return array
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @return bool
	 */
	public function has_child() {
		return false;
	}

	/**
	 * Returns whether or not the product post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return empty( $this->post ) ? false : true;
	}

	/**
	 * Returns whether or not the product is taxable.
	 *
	 * @return bool
	 */
	public function is_taxable() {
		$taxable = $this->tax_status == 'taxable' && wc_tax_enabled() ? true : false;
		return apply_filters( 'woocommerce_product_is_taxable', $taxable, $this );
	}

	/**
	 * Returns whether or not the product shipping is taxable.
	 *
	 * @return bool
	 */
	public function is_shipping_taxable() {
		return $this->tax_status=='taxable' || $this->tax_status=='shipping' ? true : false;
	}

	/**
	 * Get the title of the post.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_product_title', $this->post ? $this->post->post_title : '', $this );
	}

	/**
	 * Get the parent of the post.
	 *
	 * @return int
	 */
	public function get_parent() {
		return apply_filters( 'woocommerce_product_parent', absint( $this->post->post_parent ), $this );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', get_permalink( $this->id ), $this );
	}

	/**
	 * Get the add to cart button text for the single page
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Add to cart', 'woocommerce' ), $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Read more', 'woocommerce' ), $this );
	}

	/**
	 * Returns whether or not the product is stock managed.
	 *
	 * @return bool
	 */
	public function managing_stock() {
		return ( ! isset( $this->manage_stock ) || $this->manage_stock == 'no' || get_option( 'woocommerce_manage_stock' ) !== 'yes' ) ? false : true;
	}

	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @return bool
	 */
	public function is_in_stock() {

		if ( $this->managing_stock() && $this->backorders_allowed() ) {
			return true;
		} elseif ( $this->managing_stock() && $this->get_total_stock() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			return false;
		} else {
			return $this->stock_status === 'instock';
		}
	}

	/**
	 * Returns whether or not the product can be backordered.
	 *
	 * @return bool
	 */
	public function backorders_allowed() {
		return apply_filters( 'woocommerce_product_backorders_allowed', $this->backorders === 'yes' || $this->backorders === 'notify' ? true : false, $this->id );
	}

	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @return bool
	 */
	public function backorders_require_notification() {
		return $this->managing_stock() && $this->backorders === 'notify' ? true : false;
	}

	/**
	 * Check if a product is on backorder
	 *
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	public function is_on_backorder( $qty_in_cart = 0 ) {
		return $this->managing_stock() && $this->backorders_allowed() && ( $this->get_total_stock() - $qty_in_cart ) < 0 ? true : false;
	}

	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @param mixed $quantity
	 * @return bool
	 */
	public function has_enough_stock( $quantity ) {
		return ! $this->managing_stock() || $this->backorders_allowed() || $this->stock >= $quantity ? true : false;
	}

	/**
	 * Returns the availability of the product.
	 *
	 * @return string
	 */
	public function get_availability() {
		$availability = $class = '';

		if ( $this->managing_stock() ) {

			if ( $this->is_in_stock() && $this->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( 'In stock', 'woocommerce' );
					break;

					case 'low_amount' :
						if ( $this->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $this->get_total_stock() );

							if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
								$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
							}
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					break;

					default :
						$availability = sprintf( __( '%s in stock', 'woocommerce' ), $this->get_total_stock() );

						if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
							$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
						}
					break;
				}

				$class        = 'in-stock';

			} elseif ( $this->backorders_allowed() && $this->backorders_require_notification() ) {

				$availability = __( 'Available on backorder', 'woocommerce' );
				$class        = 'available-on-backorder';

			} elseif ( $this->backorders_allowed() ) {

				$availability = __( 'In stock', 'woocommerce' );
				$class        = 'in-stock';

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
	 * @return bool
	 */
	public function is_featured() {
		return $this->featured === 'yes' ? true : false;
	}

	/**
	 * Returns whether or not the product is visible in the catalog.
	 *
	 * @return bool
	 */
	public function is_visible() {
		if ( ! $this->post ) {
			$visible = false;

		// Published/private
		} elseif ( $this->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
			$visible = false;

		// Out of stock visibility
		} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $this->is_in_stock() ) {
			$visible = false;

		// visibility setting
		} elseif ( 'hidden' === $this->visibility ) {
			$visible = false;
		} elseif ( 'visible' === $this->visibility ) {
			$visible = true;

		// Visibility in loop
		} elseif ( is_search() ) {
			$visible = 'search' === $this->visibility;
		} else {
			$visible = 'catalog' === $this->visibility;
		}

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->id );
	}

	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @return bool
	 */
	public function is_on_sale() {
		return apply_filters( 'woocommerce_product_is_on_sale', ( $this->get_sale_price() !== $this->get_regular_price() && $this->get_sale_price() === $this->get_price() ), $this );
	}

	/**
	 * Returns the product's weight.
	 * @todo   refactor filters in this class to naming woocommerce_product_METHOD
	 * @return string
	 */
	public function get_weight() {
		return apply_filters( 'woocommerce_product_get_weight', $this->weight ? $this->weight : '' );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {

		$purchasable = true;

		// Products must exist of course
		if ( ! $this->exists() ) {
			$purchasable = false;

		// Other products types need a price to be set
		} elseif ( $this->get_price() === '' ) {
			$purchasable = false;

		// Check the product is published
		} elseif ( $this->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
			$purchasable = false;
		}

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * Set a products price dynamically.
	 *
	 * @param float $price Price to set.
	 * @return void
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * Adjust a products price dynamically.
	 *
	 * @param mixed $price
	 * @return void
	 */
	public function adjust_price( $price ) {
		$this->price = $this->price + $price;
	}

	/**
	 * Returns the product's sale price.
	 *
	 * @return string price
	 */
	public function get_sale_price() {
		return apply_filters( 'woocommerce_get_sale_price', $this->sale_price, $this );
	}

	/**
	 * Returns the product's regular price.
	 *
	 * @return string price
	 */
	public function get_regular_price() {
		return apply_filters( 'woocommerce_get_regular_price', $this->regular_price, $this );
	}

	/**
	 * Returns the product's active price.
	 *
	 * @return string price
	 */
	public function get_price() {
		return apply_filters( 'woocommerce_get_price', $this->price, $this );
	}

	/**
	 * Returns the price (including tax). Uses customer tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_including_tax( $qty = 1, $price = '' ) {

		if ( $price === '' ) {
			$price = $this->get_price();
		}

		if ( $this->is_taxable() ) {

			if ( get_option( 'woocommerce_prices_include_tax' ) === 'no' ) {

				$tax_rates  = WC_Tax::get_rates( $this->get_tax_class() );
				$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = WC_Tax::get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, wc_get_price_decimals() );

			} else {

				$tax_rates      = WC_Tax::get_rates( $this->get_tax_class() );
				$base_tax_rates = WC_Tax::get_base_tax_rates( $this->tax_class );

				if ( ! empty( WC()->customer ) && WC()->customer->is_vat_exempt() ) {

					$base_taxes         = WC_Tax::calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount    = array_sum( $base_taxes );
					$price              = round( $price * $qty - $base_tax_amount, wc_get_price_decimals() );

				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes         = WC_Tax::calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes       = WC_Tax::calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price              = round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), wc_get_price_decimals() );

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
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_excluding_tax( $qty = 1, $price = '' ) {

		if ( $price === '' ) {
			$price = $this->get_price();
		}

		if ( $this->is_taxable() && get_option( 'woocommerce_prices_include_tax' ) === 'yes' ) {
			$tax_rates  = WC_Tax::get_base_tax_rates( $this->tax_class );
			$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, true );
			$price      = WC_Tax::round( $price * $qty - array_sum( $taxes ) );
		} else {
			$price = $price * $qty;
		}

		return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $qty, $this );
	}

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  string  $price to calculate, left blank to just use get_price()
	 * @param  integer $qty   passed on to get_price_including_tax() or get_price_excluding_tax()
	 * @return string
	 */
	public function get_display_price( $price = '', $qty = 1 ) {

		if ( $price === '' ) {
			$price = $this->get_price();
		}

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$display_price    = $tax_display_mode == 'incl' ? $this->get_price_including_tax( $qty, $price ) : $this->get_price_excluding_tax( $qty, $price );

		return $display_price;
	}

	/**
	 * Get the suffix to display after prices > 0
	 *
	 * @return string
	 */
	public function get_price_suffix() {

		$price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );

		if ( $price_display_suffix ) {

			$price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

			$find = array(
				'{price_including_tax}',
				'{price_excluding_tax}'
			);

			$replace = array(
				wc_price( $this->get_price_including_tax() ),
				wc_price( $this->get_price_excluding_tax() )
			);

			$price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
		}

		return apply_filters( 'woocommerce_get_price_suffix', $price_display_suffix, $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$display_price         = $this->get_display_price();
		$display_regular_price = $this->get_display_price( $this->get_regular_price() );

		if ( $this->get_price() > 0 ) {

			if ( $this->is_on_sale() && $this->get_regular_price() ) {

				$price .= $this->get_price_html_from_to( $display_regular_price, $display_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

			} else {

				$price .= wc_price( $display_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_price_html', $price, $this );

			}

		} elseif ( $this->get_price() === '' ) {

			$price = apply_filters( 'woocommerce_empty_price_html', '', $this );

		} elseif ( $this->get_price() == 0 ) {

			if ( $this->is_on_sale() && $this->get_regular_price() ) {

				$price .= $this->get_price_html_from_to( $display_regular_price, __( 'Free!', 'woocommerce' ) );

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
	 * @return string
	 */
	public function get_price_html_from_text() {
		$from = '<span class="from">' . _x( 'From:', 'min_price', 'woocommerce' ) . ' </span>';

		return apply_filters( 'woocommerce_get_price_html_from_text', $from, $this );
	}

	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @param  string $from String or float to wrap with 'from' text
	 * @param  mixed $to String or float to wrap with 'to' text
	 * @return string
	 */
	public function get_price_html_from_to( $from, $to ) {
		$price = '<del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> <ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

		return apply_filters( 'woocommerce_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Returns the tax class.
	 *
	 * @return string
	 */
	public function get_tax_class() {
		return apply_filters( 'woocommerce_product_tax_class', $this->tax_class, $this );
	}

	/**
	 * Returns the tax status.
	 *
	 * @return string
	 */
	public function get_tax_status() {
		return $this->tax_status;
	}

	/**
	 * Get the average rating of product.
	 *
	 * @return string
	 */
	public function get_average_rating() {
		$transient_name = 'wc_average_rating_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' );

		if ( false === ( $average_rating = get_transient( $transient_name ) ) ) {

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
					AND meta_value > 0
				", $this->id ) );

				$average_rating = number_format( $ratings / $count, 2 );
			}

			set_transient( $transient_name, $average_rating, YEAR_IN_SECONDS );
		}

		return $average_rating;
	}

	/**
	 * Get the total amount (COUNT) of ratings.
	 *
	 * @param  int $value Optional. Rating value to get the count for. By default
	 *                              returns the count of all rating values.
	 * @return int
	 */
	public function get_rating_count( $value = null ) {
		$value          = intval( $value );
		$value_suffix   = $value ? '_' . $value : '';
		$transient_name = 'wc_rating_count_' . $this->id . $value_suffix . WC_Cache_Helper::get_transient_version( 'product' );

		if ( false === ( $count = get_transient( $transient_name ) ) ) {

			global $wpdb;

			$where_meta_value = $value ? $wpdb->prepare( " AND meta_value = %d", $value ) : " AND meta_value > 0";

			$count = $wpdb->get_var( $wpdb->prepare("
				SELECT COUNT(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
			", $this->id ) . $where_meta_value );

			set_transient( $transient_name, $count, YEAR_IN_SECONDS );
		}

		return $count;
	}

	/**
	 * Returns the product rating in html format.
	 *
	 * @param string $rating (default: '')
	 *
	 * @return string
	 */
	public function get_rating_html( $rating = null ) {
		$rating_html = '';

		if ( ! is_numeric( $rating ) ) {
			$rating = $this->get_average_rating();
		}

		if ( $rating > 0 ) {

			$rating_html  = '<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating ) . '">';

			$rating_html .= '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%"><strong class="rating">' . $rating . '</strong> ' . __( 'out of 5', 'woocommerce' ) . '</span>';

			$rating_html .= '</div>';
		}

		return apply_filters( 'woocommerce_product_get_rating_html', $rating_html, $rating );
	}


	/**
	 * Get the total amount (COUNT) of reviews.
	 *
	 * @since 2.3.2
	 * @return int The total numver of product reviews
	 */
	public function get_review_count() {

		$transient_name = 'wc_review_count_' . $this->id . WC_Cache_Helper::get_transient_version( 'product' );

		if ( false === ( $count = get_transient( $transient_name ) ) ) {

			global $wpdb;

			$count = $wpdb->get_var( $wpdb->prepare("
				SELECT COUNT(*) FROM $wpdb->comments
				WHERE comment_parent = 0
				AND comment_post_ID = %d
				AND comment_approved = '1'
			", $this->id ) );

			set_transient( $transient_name, $count, YEAR_IN_SECONDS );
		}

		return apply_filters( 'woocommerce_product_review_count', $count, $this );
	}


	/**
	 * Returns the upsell product ids.
	 *
	 * @return array
	 */
	public function get_upsells() {
		return (array) maybe_unserialize( $this->upsell_ids );
	}

	/**
	 * Returns the cross sell product ids.
	 *
	 * @return array
	 */
	public function get_cross_sells() {
		return (array) maybe_unserialize( $this->crosssell_ids );
	}

	/**
	 * Returns the product categories.
	 *
	 * @param string $sep (default: ', ')
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return string
	 */
	public function get_categories( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->id, 'product_cat', $before, $sep, $after );
	}

	/**
	 * Returns the product tags.
	 *
	 * @param string $sep (default: ', ')
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return array
	 */
	public function get_tags( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->id, 'product_tag', $before, $sep, $after );
	}

	/**
	 * Returns the product shipping class.
	 *
	 * @return string
	 */
	public function get_shipping_class() {

		if ( ! $this->shipping_class ) {

			$classes = get_the_terms( $this->id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->shipping_class = current( $classes )->slug;
			} else {
				$this->shipping_class = '';
			}

		}

		return $this->shipping_class;
	}

	/**
	 * Returns the product shipping class ID.
	 *
	 * @return int
	 */
	public function get_shipping_class_id() {

		if ( ! $this->shipping_class_id ) {

			$classes = get_the_terms( $this->id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->shipping_class_id = current( $classes )->term_id;
			} else {
				$this->shipping_class_id = 0;
			}
		}

		return absint( $this->shipping_class_id );
	}

	/**
	 * Get and return related products.
	 *
	 * @param int $limit (default: 5)
	 * @return array Array of post IDs
	 */
	public function get_related( $limit = 5 ) {
		global $wpdb;

		// Related products are found from category and tag
		$tags_array = array(0);
		$cats_array = array(0);

		// Get tags
		$terms = wp_get_post_terms( $this->id, 'product_tag' );
		foreach ( $terms as $term ) {
			$tags_array[] = $term->term_id;
		}

		// Get categories
		$terms = wp_get_post_terms( $this->id, 'product_cat' );
		foreach ( $terms as $term ) {
			$cats_array[] = $term->term_id;
		}

		// Don't bother if none are set
		if ( sizeof( $cats_array ) == 1 && sizeof( $tags_array ) == 1 ) {
			return array();
		}

		// Sanitize
		$cats_array  = array_map( 'absint', $cats_array );
		$tags_array  = array_map( 'absint', $tags_array );
		$exclude_ids = array_map( 'absint', array_merge( array( 0, $this->id ), $this->get_upsells() ) );

		// Generate query
		$query           = array();
		$query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
		$query['join']   = " INNER JOIN {$wpdb->postmeta} pm ON ( pm.post_id = p.ID AND pm.meta_key='_visibility' )";
		$query['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
		$query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
		$query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";

		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
			$query['join'] .= " INNER JOIN {$wpdb->postmeta} pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key='_stock_status' )";
		}

		$query['where']  = " WHERE 1=1";
		$query['where'] .= " AND p.post_status = 'publish'";
		$query['where'] .= " AND p.post_type = 'product'";
		$query['where'] .= " AND p.ID NOT IN ( " . implode( ',', $exclude_ids ) . " )";
		$query['where'] .= " AND pm.meta_value IN ( 'visible', 'catalog' )";

		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
			$query['where'] .= " AND pm2.meta_value = 'instock'";
		}

		if ( apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $this->id ) ) {
			$query['where'] .= " AND ( tt.taxonomy = 'product_cat' AND t.term_id IN ( " . implode( ',', $cats_array ) . " ) )";
			$andor = 'OR';
		} else {
			$andor = 'AND';
		}

		// when query is OR - need to check against excluded ids again
		if ( apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $this->id ) ) {
			$query['where'] .= " {$andor} ( ( tt.taxonomy = 'product_tag' AND t.term_id IN ( " . implode( ',', $tags_array ) . " ) )";
			$query['where'] .= " AND p.ID NOT IN ( " . implode( ',', $exclude_ids ) . " ) )";
		}

		$query['orderby']  = " ORDER BY RAND()";
		$query['limits']   = " LIMIT " . absint( $limit ) . " ";

		// Get the posts
		$related_posts = $wpdb->get_col( implode( ' ', apply_filters( 'woocommerce_product_related_posts_query', $query, $this->id ) ) );

		return $related_posts;
	}

	/**
	 * Returns a single product attribute.
	 *
	 * @param mixed $attr
	 * @return string
	 */
	public function get_attribute( $attr ) {

		$attributes = $this->get_attributes();

		$attr = sanitize_title( $attr );

		if ( isset( $attributes[ $attr ] ) || isset( $attributes[ 'pa_' . $attr ] ) ) {

			$attribute = isset( $attributes[ $attr ] ) ? $attributes[ $attr ] : $attributes[ 'pa_' . $attr ];

			if ( $attribute['is_taxonomy'] ) {

				return implode( ', ', wc_get_product_terms( $this->id, $attribute['name'], array( 'fields' => 'names' ) ) );

			} else {

				return $attribute['value'];
			}

		}

		return '';
	}

	/**
	 * Returns product attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return apply_filters( 'woocommerce_get_product_attributes', (array) maybe_unserialize( $this->product_attributes ) );
	}

	/**
	 * Returns whether or not the product has any attributes set.
	 *
	 * @return boolean
	 */
	public function has_attributes() {

		if ( sizeof( $this->get_attributes() ) > 0 ) {

			foreach ( $this->get_attributes() as $attribute ) {

				if ( isset( $attribute['is_visible'] ) && $attribute['is_visible'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @return bool
	 */
	public function enable_dimensions_display() {
		return apply_filters( 'wc_product_enable_dimensions_display', true );
	}

	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @return bool
	 */
	public function has_dimensions() {
		return $this->get_dimensions() ? true : false;
	}

	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @return bool
	 */
	public function has_weight() {
		return $this->get_weight() ? true : false;
	}

	/**
	 * Returns dimensions.
	 *
	 * @return string
	 */
	public function get_dimensions() {

		if ( ! $this->dimensions ) {
			$dimensions = array();

			if ( $this->length ) {
				$dimensions[] = $this->length;
			}

			if ( $this->width ) {
				$dimensions[] = $this->width;
			}

			if ( $this->height ){
				$dimensions[] = $this->height;
			}

			$this->dimensions = implode( ' x ', $dimensions );

			if ( ! empty( $this->dimensions ) ) {
				$this->dimensions .= ' ' . get_option( 'woocommerce_dimension_unit' );
			}

		}

		return $this->dimensions;
	}

	/**
	 * Lists a table of attributes for the product page.
	 */
	public function list_attributes() {
		wc_get_template( 'single-product/product-attributes.php', array(
			'product'    => $this
		) );
	}

	/**
	 * Gets the main product image ID.
	 *
	 * @return int
	 */
	public function get_image_id() {

		if ( has_post_thumbnail( $this->id ) ) {
			$image_id = get_post_thumbnail_id( $this->id );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_id = get_post_thumbnail_id( $parent_id );
		} else {
			$image_id = 0;
		}

		return $image_id;
	}

	/**
	 * Returns the main product image
	 *
	 * @param string $size (default: 'shop_thumbnail')
	 * @return string
	 */
	public function get_image( $size = 'shop_thumbnail', $attr = array() ) {
		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		} else {
			$image = wc_placeholder_img( $size );
		}

		return $image;
	}

	/**
	 * Get product name with SKU or ID. Used within admin.
	 *
	 * @return string Formatted product name
	 */
	public function get_formatted_name() {

		if ( $this->get_sku() ) {
			$identifier = $this->get_sku();
		} else {
			$identifier = '#' . $this->id;
		}

		return sprintf( __( '%s &ndash; %s', 'woocommerce' ), $identifier, $this->get_title() );
	}
}
