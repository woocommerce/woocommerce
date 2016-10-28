<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Variation Class.
 *
 *
 *
 * @todo removed filters
 *       woocommerce_variation_is_in_stock
 *
 *
 *
 * The WooCommerce product variation class handles product variation data.
 *
 * @class       WC_Product_Variation
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Product_Variation extends WC_Product_Simple {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	 protected $data = array(
 		'name'                   => '',
 		'slug'                   => '',
 		'date_created'           => '',
 		'date_modified'          => '',
 		'status'                 => false,
 		'featured'               => false,
 		'catalog_visibility'     => 'hidden',
 		'description'            => '',
 		'short_description'      => '',
 		'sku'                    => '',
 		'price'                  => '',
 		'regular_price'          => '',
 		'sale_price'             => '',
 		'date_on_sale_from'      => '',
 		'date_on_sale_to'        => '',
 		'total_sales'            => '0',
 		'tax_status'             => 'taxable',
 		'tax_class'              => '',
 		'manage_stock'           => false,
 		'stock_quantity'         => null,
 		'stock_status'           => '',
 		'backorders'             => 'no',
 		'sold_individually'      => false,
 		'weight'                 => '',
 		'length'                 => '',
 		'width'                  => '',
 		'height'                 => '',
 		'upsell_ids'             => array(),
 		'cross_sell_ids'         => array(),
 		'parent_id'              => 0,
 		'reviews_allowed'        => true,
 		'purchase_note'          => '',
 		'attributes'             => array(),
 		'default_attributes'     => array(),
 		'menu_order'             => 0,
 		'virtual'                => false,
 		'downloadable'           => false,
 		'category_ids'           => array(),
 		'tag_ids'                => array(),
 		'shipping_class_id'      => 0,
 		'downloads'              => array(),
 		'thumbnail_id'           => '',
 		'gallery_attachment_ids' => array(),
 		'download_limit'         => -1,
 		'download_expiry'        => -1,
 		'download_type'          => 'standard',
 	);

	/**
	 * Merges external product data into the parent object.
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		$this->data = array_merge( $this->data, $this->extra_data );
		parent::__construct( $product );
	}

/*
id	integer	Variation ID. READ-ONLY
date_created	date-time	The date the variation was created, in the site’s timezone. READ-ONLY
date_modified	date-time	The date the variation was last modified, in the site’s timezone. READ-ONLY
permalink	string	Variation URL. READ-ONLY
sku	string	Unique identifier.
price	string	Current variation price. This is setted from regular_price and sale_price. READ-ONLY
regular_price	string	Variation regular price.
sale_price	string	Variation sale price.
date_on_sale_from	string	Start date of sale price. Date in the YYYY-MM-DD format.
date_on_sale_to	string	Start date of sale price. Date in the YYYY-MM-DD format.
on_sale	boolean	Shows if the variation is on sale. READ-ONLY
purchasable	boolean	Shows if the variation can be bought. READ-ONLY
virtual	boolean	If the variation is virtual. Virtual variations are intangible and aren’t shipped. Default is false.
downloadable	boolean	If the variation is downloadable. Downloadable variations give access to a file upon purchase. Default is false.
downloads	array	List of downloadable files. See Downloads properties.
download_limit	integer	Amount of times the variation can be downloaded, the -1 values means unlimited re-downloads. Default is -1.
download_expiry	integer	Number of days that the customer has up to be able to download the variation, the -1 means that downloads never expires. Default is -1.
tax_status	string	Tax status. Default is taxable. Options: taxable, shipping (Shipping only) and none.
tax_class	string	Tax class.
manage_stock	boolean	Stock management at variation level. Default is false.
stock_quantity	integer	Stock quantity. If is a variable variation this value will be used to control stock for all variations, unless you define stock at variation level.
in_stock	boolean	Controls whether or not the variation is listed as “in stock” or “out of stock” on the frontend. Default is true.
backorders	string	If managing stock, this controls if backorders are allowed. If enabled, stock quantity can go below 0. Default is no. Options are: no (Do not allow), notify (Allow, but notify customer), and yes (Allow).
backorders_allowed	boolean	Shows if backorders are allowed.“ READ-ONLY
backordered	boolean	Shows if a variation is on backorder (if the variation have the stock_quantity negative). READ-ONLY
weight	string	Variation weight in decimal format.
dimensions	object	Variation dimensions. See Dimensions properties.
shipping_class	string	Shipping class slug. Shipping classes are used by certain shipping methods to group similar products.
shipping_class_id	integer	Shipping class ID. READ-ONLY
image	array	Variation featured image. Only position 0 will be used. See Images properties.
attributes
*/
 */
	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'variation';
	}

	/**
	 * Get product name with SKU or ID. Used within admin.
	 *
	 * @return string Formatted product name
	 */
	public function get_formatted_name() {
		$formatted_attributes = $this->get_formatted_variation_attributes( true );
		return parent::get_formatted_name() . ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $this->get_price() );
	}

	/**
	 * Get variation attribute values.
	 *
	 * @return array of attributes and their values for this variation
	 */
	public function get_variation_attributes() {
		return $this->variation_data;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting product data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/



	/**
	 * Wrapper for get_permalink. Adds this variations attributes to the URL.
	 *
	 * @param  $item_object item array If a cart or order item is passed, we can get a link containing the exact attributes selected for the variation, rather than the default attributes.
	 * @return string
	 */
	public function get_permalink( $item_object = null ) {
		if ( ! empty( $item_object['variation'] ) ) {
			$data = $item_object['variation'];
		} elseif ( ! empty( $item_object['item_meta_array'] ) ) {
			$data_keys    = array_map( 'wc_variation_attribute_name', wp_list_pluck( $item_object['item_meta_array'], 'key' ) );
			$data_values  = wp_list_pluck( $item_object['item_meta_array'], 'value' );
			$data         = array_intersect_key( array_combine( $data_keys, $data_values ), $this->variation_data );
		} else {
			$data = $this->variation_data;
		}
		return add_query_arg( array_map( 'urlencode', array_filter( $data ) ), get_permalink( $this->id ) );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$variation_data = array_map( 'urlencode', $this->variation_data );
		$url            = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( array_merge( array( 'variation_id' => $this->variation_id, 'add-to-cart' => $this->id ), $variation_data ) ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Checks if this particular variation is visible. Invisible variations are enabled and can be selected, but no price / stock info is displayed.
	 * Instead, a suitable 'unavailable' message is displayed.
	 * Invisible by default: Disabled variations and variations with an empty price.
	 *
	 * @return bool
	 */
	public function variation_is_visible() {
		$visible = true;

		if ( get_post_status( $this->variation_id ) != 'publish' ) {

			// Published == enabled checkbox
			$visible = false;

		} elseif ( $this->get_price() === "" ) {

			// Price not set
			$visible = false;

		}

		return apply_filters( 'woocommerce_variation_is_visible', $visible, $this->variation_id, $this->id, $this );
	}






	/**
	 * Get variation price HTML. Prices are not inherited from parents.
	 *
	 * @return string containing the formatted price
	 */
	public function get_price_html( $price = '' ) {

		$display_price         = $this->get_display_price();
		$display_regular_price = $this->get_display_price( $this->get_regular_price() );
		$display_sale_price    = $this->get_display_price( $this->get_sale_price() );

		if ( $this->get_price() !== '' ) {
			if ( $this->is_on_sale() ) {
				$price = apply_filters( 'woocommerce_variation_sale_price_html', '<del>' . wc_price( $display_regular_price ) . '</del> <ins>' . wc_price( $display_sale_price ) . '</ins>' . $this->get_price_suffix(), $this );
			} elseif ( $this->get_price() > 0 ) {
				$price = apply_filters( 'woocommerce_variation_price_html', wc_price( $display_price ) . $this->get_price_suffix(), $this );
			} else {
				$price = apply_filters( 'woocommerce_variation_free_price_html', __( 'Free!', 'woocommerce' ), $this );
			}
		} else {
			$price = apply_filters( 'woocommerce_variation_empty_price_html', '', $this );
		}

		return apply_filters( 'woocommerce_get_variation_price_html', $price, $this );
	}

	/**
	 * Returns whether or not the product (or variation) is stock managed.
	 *
	 * @return bool|string Bool if managed at variation level, 'parent' if managed by the parent.
	 */
	public function managing_stock() {
		if ( 'yes' === get_option( 'woocommerce_manage_stock', 'yes' ) ) {
			if ( 'no' === $this->manage_stock ) {
				if ( $this->parent && $this->parent->managing_stock() ) {
					return 'parent';
				}
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns number of items available for sale from the variation, or parent.
	 *
	 * @return int
	 */
	public function get_stock_quantity() {
		return apply_filters( 'woocommerce_variation_get_stock_quantity', true === $this->managing_stock() ? wc_stock_amount( $this->stock ) : $this->parent->get_stock_quantity(), $this );
	}

	/**
	 * Set stock status.
	 *
	 * @param string $status
	 */
	public function set_stock_status( $status ) {
		$status = 'outofstock' === $status ? 'outofstock' : 'instock';

		// Sanity check
		if ( true === $this->managing_stock() ) {
			if ( ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		} elseif ( 'parent' === $this->managing_stock() ) {
			if ( ! $this->parent->backorders_allowed() && $this->parent->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		}

		if ( update_post_meta( $this->variation_id, '_stock_status', $status ) ) {
			do_action( 'woocommerce_variation_set_stock_status', $this->variation_id, $status );

			WC_Product_Variable::sync_stock_status( $this->id );
		}
	}

	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @return bool
	 */
	public function backorders_require_notification() {
		if ( true === $this->managing_stock() ) {
			return parent::backorders_require_notification();
		} else {
			return $this->parent->backorders_require_notification();
		}
	}

	/**
	 * Is on backorder?
	 *
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	public function is_on_backorder( $qty_in_cart = 0 ) {
		if ( true === $this->managing_stock() ) {
			return parent::is_on_backorder( $qty_in_cart );
		} else {
			return $this->parent->managing_stock() && $this->parent->backorders_allowed() && ( $this->parent->get_stock_quantity() - $qty_in_cart ) < 0;
		}
	}

	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @param mixed $quantity
	 * @return bool
	 */
	public function has_enough_stock( $quantity ) {
		if ( true === $this->managing_stock() ) {
			return parent::has_enough_stock( $quantity );
		} else {
			return $this->parent->has_enough_stock( $quantity );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/** @private array Data which is only at variation level - no inheritance plus their default values if left blank. */
	protected $variation_level_meta_data = array(
		'downloadable'          => 'no',
		'virtual'               => 'no',
		'manage_stock'          => 'no',
		'sale_price_dates_from' => '',
		'sale_price_dates_to'   => '',
		'price'                 => '',
		'regular_price'         => '',
		'sale_price'            => '',
		'stock'                 => 0,
		'stock_status'          => 'instock',
		'downloadable_files'    => array(),
	);

	/** @private array Data which can be at variation level, otherwise fallback to parent if not set. */
	protected $variation_inherited_meta_data = array(
		'tax_class'  => '',
		'backorders' => 'no',
		'sku'        => '',
		'weight'     => '',
		'length'     => '',
		'width'      => '',
		'height'     => '',
	);

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Product ID.
	 */
	public function read( $id ) {
		$this->set_defaults();

		/**
		 * 		if ( is_object( $variation ) ) {
		 			$this->variation_id = absint( $variation->ID );
		 		} else {
		 			$this->variation_id = absint( $variation );
		 		}

		 		/* Get main product data from parent (args) */
		 		$this->id = ! empty( $args['parent_id'] ) ? intval( $args['parent_id'] ) : wp_get_post_parent_id( $this->variation_id );

		 		// The post doesn't have a parent id, therefore its invalid and we should prevent this being created.
		 		if ( empty( $this->id ) ) {
		 			throw new Exception( sprintf( 'No parent product set for variation #%d', $this->variation_id ), 422 );
		 		}

		 		$this->parent       = ! empty( $args['parent'] ) ? $args['parent'] : wc_get_product( $this->id );
		 		$this->post         = ! empty( $this->parent->post ) ? $this->parent->post : array();

		 		// The post parent is not a valid variable product so we should prevent this being created.
		 		if ( ! is_a( $this->parent, 'WC_Product' ) ) {
		 			throw new Exception( sprintf( 'Invalid parent for variation #%d', $this->variation_id ), 422 );
		 		}

		 */

		if ( ! $id || ! ( $post_object = get_post( $id ) ) ) {
			return;
		}

		$this->set_id( $id );
		$this->set_props( array(
		) );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns false if the product cannot be bought.
	 * Override abstract method so that: i) Disabled variations are not be purchasable by admins. ii) Enabled variations are not purchasable if the parent product is not purchasable.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		// Published == enabled checkbox
		if ( get_post_status( $this->variation_id ) != 'publish' ) {
			$purchasable = false;
		} else {
			$purchasable = parent::is_purchasable();
		}
		return apply_filters( 'woocommerce_variation_is_purchasable', $purchasable, $this );
	}

	/**
	 * Controls whether this particular variation will appear greyed-out (inactive) or not (active).
	 * Used by extensions to make incompatible variations appear greyed-out, etc.
	 * Other possible uses: prevent out-of-stock variations from being selected.
	 *
	 * @return bool
	 */
	public function variation_is_active() {
		return apply_filters( 'woocommerce_variation_is_active', true, $this );
	}
}
