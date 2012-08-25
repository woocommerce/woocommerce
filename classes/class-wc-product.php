<?php
/**
 * Product Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @class 		WC_Product
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Product {

	/** @var int The product (post) ID. */
	var $id;

	/** @var array Array of custom fields (meta) containing product data. */
	var $product_custom_fields;

	/** @var array Array of product attributes. */
	var $attributes;

	/** @var array Array of child products/posts/variations. */
	var $children;

	/** @var object The actual post object. */
	var $post;

	/** @var string "Yes" for downloadable products. */
	var $downloadable;

	/** @var string "Yes" for virtual products. */
	var $virtual;

	/** @var string The product SKU (stock keeping unit). */
	var $sku;

	/** @var string The product price. */
	var $price;

	/** @var string The product's visibility. */
	var $visibility;

	/** @var string The product's stock level (if applicable). */
	var $stock;

	/** @var string The product's stock status (instock or outofstock). */
	var $stock_status;

	/** @var string The product's backorder status. */
	var $backorders;

	/** @var bool True if the product is stock managed. */
	var $manage_stock;

	/** @var string The product's sale price. */
	var $sale_price;

	/** @var string The product's regular non-sale price. */
	var $regular_price;

	/** @var string The product's weight. */
	var $weight;

	/** @var string The product's length. */
	var $length;

	/** @var string The product's width. */
	var $width;

	/** @var string The product's height. */
	var $height;

	/** @var string The product's tax status. */
	var $tax_status;

	/** @var string The product's tax class. */
	var $tax_class;

	/** @var array Array of product ID's being up-sold. */
	var $upsell_ids;

	/** @var array Array of product ID's being cross-sold. */
	var $crosssell_ids;

	/** @var string The product's type (simple, variable etc). */
	var $product_type;

	/** @var string The product's total stock, including that of its children. */
	var $total_stock;

	/** @var string Date a sale starts. */
	var $sale_price_dates_from;

	/** @var string Data a sale ends. */
	var $sale_price_dates_to;

	/** @var string Used for variation prices. */
	var $min_variation_price;

	/** @var string Used for variation prices. */
	var $max_variation_price;

	/** @var string Used for variation prices. */
	var $min_variation_regular_price;

	/** @var string Used for variation prices. */
	var $max_variation_regular_price;

	/** @var string Used for variation prices. */
	var $min_variation_sale_price;

	/** @var string Used for variation prices. */
	var $max_variation_sale_price;

	/** @var string "Yes" for featured products. */
	var $featured;

	/** @var string Shipping class slug for the product. */
	var $shipping_class;

	/** @var int Shipping class ID for the product. */
	var $shipping_class_id;

	/** @var string Formatted LxWxH. */
	var $dimensions;

	/**
	 * Loads all product data from custom fields.
	 *
	 * @param int $id ID of the product to load
	 */
	function __construct( $id ) {

		$this->id = (int) $id;

		$this->product_custom_fields = get_post_custom( $this->id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'sku'			=> '',
			'downloadable' 	=> 'no',
			'virtual' 		=> 'no',
			'price' 		=> '',
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'		=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array(),
			'sale_price_dates_from' => '',
			'sale_price_dates_to' 	=> '',
			'min_variation_price'	=> '',
			'max_variation_price'	=> '',
			'min_variation_regular_price'	=> '',
			'max_variation_regular_price'	=> '',
			'min_variation_sale_price'	=> '',
			'max_variation_sale_price'	=> '',
			'featured'		=> 'no'
		);

		// Load the data from the custom fields
		foreach ($load_data as $key => $default) $this->$key = (isset($this->product_custom_fields['_' . $key][0]) && $this->product_custom_fields['_' . $key][0]!=='') ? $this->product_custom_fields['_' . $key][0] : $default;

		// Get product type
		$transient_name = 'wc_product_type_' . $this->id;

		if ( false === ( $this->product_type = get_transient( $transient_name ) ) ) :
			$terms = wp_get_object_terms( $id, 'product_type', array('fields' => 'names') );
			$this->product_type = (isset($terms[0])) ? sanitize_title($terms[0]) : 'simple';
			set_transient( $transient_name, $this->product_type );
		endif;

		// Check sale dates
		$this->check_sale_price();
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
     * Get total stock.
     *
     * This is the stock of parent and children combined.
     *
     * @access public
     * @return int
     */
    function get_total_stock() {

        if (is_null($this->total_stock)) :

        	$transient_name = 'wc_product_total_stock_' . $this->id;

        	if ( false === ( $this->total_stock = get_transient( $transient_name ) ) ) :

		        $this->total_stock = $this->stock;

				if (sizeof($this->get_children())>0) foreach ($this->get_children() as $child_id) :

					$stock = get_post_meta($child_id, '_stock', true);

					if ( $stock!='' ) :

						$this->total_stock += $stock;

					endif;

				endforeach;

				set_transient( $transient_name, $this->total_stock );

			endif;

		endif;

		return (int) $this->total_stock;
    }

	/**
	 * Return the products children posts.
	 *
	 * @access public
	 * @return array
	 */
	function get_children() {

		if (!is_array($this->children)) :

			$this->children = array();

			if ($this->is_type('variable') || $this->is_type('grouped')) :

				$child_post_type = ($this->is_type('variable')) ? 'product_variation' : 'product';

				$transient_name = 'wc_product_children_ids_' . $this->id;

	        	if ( false === ( $this->children = get_transient( $transient_name ) ) ) :

			        $this->children = get_posts( 'post_parent=' . $this->id . '&post_type=' . $child_post_type . '&orderby=menu_order&order=ASC&fields=ids&post_status=any&numberposts=-1' );

					set_transient( $transient_name, $this->children );

				endif;

			endif;

		endif;

		return (array) $this->children;
	}


	/**
	 * get_child function.
	 *
	 * @access public
	 * @param mixed $child_id
	 * @return object WC_Product or WC_Product_variation
	 */
	function get_child( $child_id ) {
		if ($this->is_type('variable')) :
			$child = new WC_Product_Variation( $child_id, $this->id, $this->product_custom_fields );
		else :
			$child = new WC_Product( $child_id );
		endif;
		return $child;
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

		if ($this->managing_stock()) :
			$this->stock = $this->stock - $by;
			$this->total_stock = $this->get_total_stock() - $by;
			update_post_meta($this->id, '_stock', $this->stock);

			// Out of stock attribute
			if ($this->managing_stock() && !$this->backorders_allowed() && $this->get_total_stock()<=0) :
				update_post_meta($this->id, '_stock_status', 'outofstock');
			endif;

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return $this->stock;
		endif;
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

		if ($this->managing_stock()) :
			$this->stock = $this->stock + $by;
			$this->total_stock = $this->get_total_stock() + $by;
			update_post_meta($this->id, '_stock', $this->stock);

			// Out of stock attribute
			if ($this->managing_stock() && ($this->backorders_allowed() || $this->get_total_stock()>0)) :
				update_post_meta($this->id, '_stock_status', 'instock');
			endif;

			$woocommerce->clear_product_transients( $this->id ); // Clear transient

			return $this->stock;
		endif;
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
		if ( is_array( $type ) && in_array( $this->product_type, $type ) ) return true;
		if ( $this->product_type == $type ) return true;
		return false;
	}


	/**
	 * Checks if a product is downloadable
	 *
	 * @access public
	 * @return bool
	 */
	function is_downloadable() {
		if ( $this->downloadable == 'yes' ) return true; else return false;
	}


	/**
	 * Check if downloadable product has a file attached.
	 *
	 * @since 1.6.2
	 *
	 * @return bool Whether downloadable product has a file attached.
	 */
	function has_file() {
		if ( ! $this->is_downloadable() )
			return false;

		if ( apply_filters( 'woocommerce_file_download_path', get_post_meta( $this->id, '_file_path', true ), $this->id ) )
			return true;

		return false;
	}


	/**
	 * Checks if a product is virtual (has no shipping).
	 *
	 * @access public
	 * @return bool
	 */
	function is_virtual() {
		if ( $this->virtual == 'yes' ) return true; else return false;
	}


	/**
	 * Checks if a product needs shipping.
	 *
	 * @access public
	 * @return bool
	 */
	function needs_shipping() {
		if ( $this->is_virtual() ) return false; else return true;
	}

	/**
	 * Check if a product is sold individually (no quantities)
	 *
	 * @access public
	 * @return bool
	 */
	function is_sold_individually() {
		$return = false;

		// Sold individually if downloadable, virtual, and the option is enabled
		if ( $this->is_downloadable() && $this->is_virtual() && get_option('woocommerce_limit_downloadable_product_qty') == 'yes' ) {
			$return = true;
		}

		return apply_filters( 'woocommerce_is_sold_individually', $return, $this );
	}


	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @access public
	 * @return bool
	 */
	function has_child() {
		return sizeof( $this->get_children() ) ? true : false;
	}


	/**
	 * Returns whether or not the product post exists.
	 *
	 * @access public
	 * @return bool
	 */
	function exists() {
		global $wpdb;
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d LIMIT 1;", $this->id ) ) > 0 ) return true;
		return false;
	}


	/**
	 * Returns whether or not the product is taxable.
	 *
	 * @access public
	 * @return bool
	 */
	function is_taxable() {
		if ($this->tax_status=='taxable' && get_option('woocommerce_calc_taxes')=='yes') return true;
		return false;
	}


	/**
	 * Returns whether or not the product shipping is taxable.
	 *
	 * @access public
	 * @return bool
	 */
	function is_shipping_taxable() {
		if ($this->tax_status=='taxable' || $this->tax_status=='shipping') return true;
		return false;
	}


	/**
	 * Get the product's post data.
	 *
	 * @access public
	 * @return object
	 */
	function get_post_data() {
		if (empty($this->post)) :
			$this->post = get_post( $this->id );
		endif;
		return $this->post;
	}


	/**
	 * Get the title of the post.
	 *
	 * @access public
	 * @return string
	 */
	function get_title() {
		$this->get_post_data();
		return apply_filters('woocommerce_product_title', apply_filters('the_title', $this->post->post_title), $this);
	}


	/**
	 * Get the parent of the post.
	 *
	 * @access public
	 * @return int
	 */
	function get_parent() {
		$this->get_post_data();
		return apply_filters('woocommerce_product_parent', $this->post->post_parent, $this);
	}


	/**
	 * Get the add to url.
	 *
	 * @access public
	 * @return string
	 */
	function add_to_cart_url() {
		global $woocommerce;

		if ($this->is_type('variable')) :
			$url = add_query_arg('add-to-cart', 'variation');
			$url = add_query_arg('product_id', $this->id, $url);
		elseif ( $this->has_child() ) :
			$url = add_query_arg('add-to-cart', 'group');
			$url = add_query_arg('product_id', $this->id, $url);
		else :
			$url = add_query_arg('add-to-cart', $this->id);
		endif;

		return apply_filters( 'woocommerce_add_to_cart_url', $url );
	}


	/**
	 * Returns whether or not the product is stock managed.
	 *
	 * @access public
	 * @return bool
	 */
	function managing_stock() {
		if ( ! isset( $this->manage_stock ) || $this->manage_stock == 'no' ) return false;
		if ( get_option('woocommerce_manage_stock') == 'yes' ) return true;
		return false;
	}


	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @access public
	 * @return bool
	 */
	function is_in_stock() {
		if ( $this->managing_stock() ) :
			if ( ! $this->backorders_allowed() ) :
				if ( $this->get_total_stock() <  1 ) :
					return false;
				else :
					if ( $this->stock_status == 'instock' ) return true;
					return false;
				endif;
			else :
				return true;
			endif;
		endif;
		if ( $this->stock_status == 'instock' ) return true;
		return false;
	}


	/**
	 * Returns whether or not the product can be backordered.
	 *
	 * @access public
	 * @return bool
	 */
	function backorders_allowed() {
		if ($this->backorders=='yes' || $this->backorders=='notify') return true;
		return false;
	}


	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @access public
	 * @return bool
	 */
	function backorders_require_notification() {
		if ($this->managing_stock() && $this->backorders=='notify') return true;
		return false;
	}


	/**
	 * is_on_backorder function.
	 *
	 * @access public
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	function is_on_backorder( $qty_in_cart = 0 ) {
		if ( $this->managing_stock() && $this->backorders_allowed() && ( $this->get_total_stock() - $qty_in_cart ) < 0 ) return true;
		return false;
	}


    /**
     * Returns number of items available for sale.
     *
     * @access public
     * @return int
     */
    function get_stock_quantity() {
    	if ( get_option( 'woocommerce_manage_stock' ) == 'no' )
    		return '';

        return (int) $this->stock;
    }


	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @access public
	 * @param mixed $quantity
	 * @return bool
	 */
	function has_enough_stock( $quantity ) {

		if (!$this->managing_stock()) return true;

		if ($this->backorders_allowed()) return true;

		if ($this->stock >= $quantity) :
			return true;
		endif;

		return false;

	}


	/**
	 * Returns the availability of the product.
	 *
	 * @access public
	 * @return string
	 */
	function get_availability() {

		$availability = "";
		$class = "";

		if (!$this->managing_stock()) :
			if (!$this->is_in_stock()) :
				$availability = __('Out of stock', 'woocommerce');
				$class = 'out-of-stock';
			endif;
		else :
			if ($this->is_in_stock()) :
				if ( $this->get_total_stock() > 0 ) :

					$format_option = get_option( 'woocommerce_stock_format' );

					switch ( $format_option ) {
						case 'no_amount' :
							$format = __('In stock', 'woocommerce');
						break;
						case 'low_amount' :
							$low_amount = get_option( 'woocommerce_notify_low_stock_amount' );

							$format = ( $this->get_total_stock() <= $low_amount ) ? __('Only %s left in stock', 'woocommerce') : __('In stock', 'woocommerce');
						break;
						default :
							$format = __('%s in stock', 'woocommerce');
						break;
					}

					$availability = sprintf( $format, $this->stock );

					if ($this->backorders_allowed() && $this->backorders_require_notification()) :
						$availability .= ' ' . __('(backorders allowed)', 'woocommerce');
					endif;

				else :

					if ($this->backorders_allowed()) :
						if ($this->backorders_require_notification()) :
							$availability = __('Available on backorder', 'woocommerce');
							$class = 'available-on-backorder';
						else :
							$availability = __('In stock', 'woocommerce');
						endif;
					else :
						$availability = __('Out of stock', 'woocommerce');
						$class = 'out-of-stock';
					endif;

				endif;
			else :
				if ($this->backorders_allowed()) :
					$availability = __('Available on backorder', 'woocommerce');
					$class = 'available-on-backorder';
				else :
					$availability = __('Out of stock', 'woocommerce');
					$class = 'out-of-stock';
				endif;
			endif;
		endif;

		return apply_filters( 'woocommerce_get_availability', array( 'availability' => $availability, 'class' => $class), $this );
	}


	/**
	 * Returns whether or not the product is featured.
	 *
	 * @access public
	 * @return bool
	 */
	function is_featured() {
		if ($this->featured=='yes') return true; else return false;
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
		if (get_option('woocommerce_hide_out_of_stock_items')=='yes' && !$this->is_in_stock()) $visible = false;

		// visibility setting
		elseif ($this->visibility=='hidden') $visible = false;
		elseif ($this->visibility=='visible') $visible = true;

		// Visibility in loop
		elseif ($this->visibility=='search' && is_search()) $visible = true;
		elseif ($this->visibility=='search' && !is_search()) $visible = false;
		elseif ($this->visibility=='catalog' && is_search()) $visible = false;
		elseif ($this->visibility=='catalog' && !is_search()) $visible = true;

		return apply_filters('woocommerce_product_is_visible', $visible, $this->id);
	}


	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @access public
	 * @return bool
	 */
	function is_on_sale() {
		if ($this->has_child()) :

			foreach ($this->get_children() as $child_id) :
				$sale_price = get_post_meta( $child_id, '_sale_price', true );
				if ( $sale_price!=="" && $sale_price >= 0 ) return true;
			endforeach;

		else :

			if ( $this->sale_price && $this->sale_price==$this->price ) return true;

		endif;
		return false;
	}


	/**
	 * Returns the product's weight.
	 *
	 * @access public
	 * @return string
	 */
	function get_weight() {
		if ($this->weight) return $this->weight;
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
	 * @param float $price Price to increase by.
	 * @return void
	 */
	function adjust_price( $price ) {
		if ( $price > 0 ) :
			$this->price += $price;
		endif;
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

		// External and grouped products cannot be bought
		elseif ( $this->is_type( array( 'grouped', 'external' ) ) )
			$purchasable = false;

		// Other products types need a price to be set
		elseif ( $this->get_price() === '' )
			$purchasable = false;

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}


	/**
	 * Returns the price (excluding tax) - ignores tax_class filters since the price may *include* tax and thus needs subtracting.
	 *
	 * @access public
	 * @return string
	 */
	function get_price_excluding_tax() {

		$price = $this->get_price();

		if ( $this->is_taxable() && get_option('woocommerce_prices_include_tax')=='yes' ) :

			$_tax = new WC_Tax();

			$tax_rates 		= $_tax->get_shop_base_rate( $this->tax_class );
			$taxes 			= $_tax->calc_tax( $price, $tax_rates, true );
			$tax_amount		= $_tax->get_tax_total( $taxes );
			$price 			= round( $price - $tax_amount, 2);

		endif;

		return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $this );
	}


	/**
	 * Returns the tax class.
	 *
	 * @access public
	 * @return string
	 */
	function get_tax_class() {
		return apply_filters('woocommerce_product_tax_class', $this->tax_class, $this);
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
		if ($this->is_type('grouped')) :

			$child_prices = array();

			foreach ($this->get_children() as $child_id) $child_prices[] = get_post_meta( $child_id, '_price', true );

			$child_prices = array_unique( $child_prices );

			if ( ! empty( $child_prices ) ) {
				$min_price = min( $child_prices );
			} else {
				$min_price = '';
			}

			if (sizeof($child_prices)>1) $price .= $this->get_price_html_from_text();

			$price .= woocommerce_price( $min_price );

			$price = apply_filters('woocommerce_grouped_price_html', $price, $this);

		elseif ($this->is_type('variable')) :

			// Ensure variation prices are synced with variations
			if ( $this->min_variation_price === '' || $this->min_variation_regular_price === '' )
				$this->variable_product_sync();

			// Get the price
			if ($this->price > 0) :
				if ($this->is_on_sale() && isset($this->min_variation_price) && $this->min_variation_regular_price !== $this->get_price()) :

					if ( !$this->min_variation_price || $this->min_variation_price !== $this->max_variation_price )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_variation_regular_price, $this->get_price() );

					$price = apply_filters('woocommerce_variable_sale_price_html', $price, $this);

				else :

					if ( ! $this->min_variation_price || $this->min_variation_price !== $this->max_variation_price )
						$price .= $this->get_price_html_from_text();

					$price .= woocommerce_price( $this->get_price() );

					$price = apply_filters('woocommerce_variable_price_html', $price, $this);

				endif;
			elseif ($this->price === '' ) :

				$price = apply_filters('woocommerce_variable_empty_price_html', '', $this);

			elseif ($this->price == 0 ) :

				if ($this->is_on_sale() && isset($this->min_variation_regular_price) && $this->min_variation_regular_price !== $this->get_price()) :

					if ( !$this->min_variation_price || $this->min_variation_price !== $this->max_variation_price )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_variation_regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters('woocommerce_variable_free_sale_price_html', $price, $this);

				else :

					if ( ! $this->min_variation_price || $this->min_variation_price !== $this->max_variation_price )
						$price .= $this->get_price_html_from_text();

					$price .= __('Free!', 'woocommerce');

					$price = apply_filters('woocommerce_variable_free_price_html', $price, $this);

				endif;

			endif;

		else :
			if ($this->price > 0) :
				if ($this->is_on_sale() && isset($this->regular_price)) :

					$price .= $this->get_price_html_from_to( $this->regular_price, $this->get_price() );

					$price = apply_filters('woocommerce_sale_price_html', $price, $this);

				else :

					$price .= woocommerce_price( $this->get_price() );

					$price = apply_filters('woocommerce_price_html', $price, $this);

				endif;
			elseif ($this->price === '' ) :

				$price = apply_filters('woocommerce_empty_price_html', '', $this);

			elseif ($this->price == 0 ) :

				if ($this->is_on_sale() && isset($this->regular_price)) :

					$price .= $this->get_price_html_from_to( $this->regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters('woocommerce_free_sale_price_html', $price, $this);

				else :

					$price = __('Free!', 'woocommerce');

					$price = apply_filters('woocommerce_free_price_html', $price, $this);

				endif;

			endif;
		endif;

		return apply_filters('woocommerce_get_price_html', $price, $this);
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
		return '<del>' . ((is_numeric($from)) ? woocommerce_price( $from ) : $from) . '</del> <ins>' . ((is_numeric($to)) ? woocommerce_price( $to ) : $to) . '</ins>';
	}


	/**
	 * Returns the product rating in html format - ratings are stored in transient cache.
	 *
	 * @access public
	 * @param string $location (default: '')
	 * @return void
	 */
	function get_rating_html( $location = '' ) {

		if ($location) $location = '_'.$location;
		$star_size = apply_filters('woocommerce_star_rating_size'.$location, 16);

		if ( false === ( $average_rating = get_transient( 'wc_average_rating_' . $this->id ) ) ) :

			global $wpdb;

			$count = $wpdb->get_var("
				SELECT COUNT(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $this->id
				AND comment_approved = '1'
				AND meta_value > 0
			");

			$ratings = $wpdb->get_var("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $this->id
				AND comment_approved = '1'
			");

			if ( $count>0 ) :
				$average_rating = number_format($ratings / $count, 2);
			else :
				$average_rating = '';
			endif;

			set_transient( 'wc_average_rating_' . $this->id, $average_rating );

		endif;

		if ( $average_rating>0 ) :
			return '<div class="star-rating" title="'.sprintf(__('Rated %s out of 5', 'woocommerce'), $average_rating).'"><span style="width:'.($average_rating*$star_size).'px"><span class="rating">'.$average_rating.'</span> '.__('out of 5', 'woocommerce').'</span></div>';
		else :
			return '';
		endif;
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
	 * Returns the crosssell product ids.
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
		return get_the_term_list($this->id, 'product_cat', $before, $sep, $after);
	}


	/**
	 * Returns the product tags.
	 *
	 * @access public
	 * @param string $sep (default: ')
	 * @param mixed '
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return array
	 */
	function get_tags( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list($this->id, 'product_tag', $before, $sep, $after);
	}


	/**
	 * Returns the product shipping class.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_class() {
		if ( ! $this->shipping_class ) :
			$classes = get_the_terms( $this->id, 'product_shipping_class' );
			if ($classes && !is_wp_error($classes)) $this->shipping_class = current($classes)->slug; else $this->shipping_class = '';
		endif;
		return $this->shipping_class;
	}


	/**
	 * Returns the product shipping class ID.
	 *
	 * @access public
	 * @return int
	 */
	function get_shipping_class_id() {
		if ( ! $this->shipping_class_id ) :
			$classes = get_the_terms( $this->id, 'product_shipping_class' );
			if ( $classes && ! is_wp_error( $classes ) )
				$this->shipping_class_id = current( $classes )->term_id;
			else
				$this->shipping_class_id = 0;
		endif;
		return (int) $this->shipping_class_id;
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
		foreach ($terms as $term) $tags_array[] = $term->term_id;

		// Get categories
		$terms = wp_get_post_terms($this->id, 'product_cat');
		foreach ($terms as $term) $cats_array[] = $term->term_id;

		// Don't bother if none are set
		if ( sizeof($cats_array)==1 && sizeof($tags_array)==1 ) return array();

		// Meta query
		$meta_query = array();
		$meta_query[] = $woocommerce->query->visibility_meta_query();
	    $meta_query[] = $woocommerce->query->stock_status_meta_query();

		// Get the posts
		$related_posts = get_posts(apply_filters('woocommerce_product_related_posts', array(
			'orderby' 		=> 'rand',
			'posts_per_page'=> $limit,
			'post_type' 	=> 'product',
			'fields' 		=> 'ids',
			'meta_query' 	=> $meta_query,
			'tax_query' 	=> array(
				'relation' => 'OR',
				array(
					'taxonomy' 	=> 'product_cat',
					'field' 	=> 'id',
					'terms' 	=> $cats_array
				),
				array(
					'taxonomy' 	=> 'product_tag',
					'field' 	=> 'id',
					'terms' 	=> $tags_array
				)
			)
		)));

		$related_posts = array_diff( $related_posts, array($this->id) );

		return $related_posts;
	}


	/**
	 * Returns a single product attribute.
	 *
	 * @access public
	 * @param mixed $attr
	 * @return mixed
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

		return false;
	}


	/**
	 * Returns product attributes.
	 *
	 * @access public
	 * @return array
	 */
	function get_attributes() {

		if ( ! is_array( $this->attributes ) ) {

			if (isset($this->product_custom_fields['_product_attributes'][0]))
				$this->attributes = maybe_unserialize( maybe_unserialize( $this->product_custom_fields['_product_attributes'][0] ));
			else
				$this->attributes = array();

		}

		return (array) $this->attributes;
	}


	/**
	 * Returns whether or not the product has any attributes set.
	 *
	 * @access public
	 * @return mixed
	 */
	function has_attributes() {
		if (sizeof($this->get_attributes())>0) :
			foreach ($this->get_attributes() as $attribute) :
				if (isset($attribute['is_visible']) && $attribute['is_visible']) return true;
			endforeach;
		endif;
		return false;
	}


	/**
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @access public
	 * @return bool
	 */
	function enable_dimensions_display() {
		if (get_option('woocommerce_enable_dimension_product_attributes')=='yes') return true;
		return false;
	}


	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @access public
	 * @return bool
	 */
	function has_dimensions() {
		if ($this->get_dimensions()) return true;
		return false;
	}


	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @access public
	 * @return bool
	 */
	function has_weight() {
		if ($this->get_weight()) return true;
		return false;
	}


	/**
	 * Returns dimensions.
	 *
	 * @access public
	 * @return string
	 */
	function get_dimensions() {
		if (!$this->dimensions) :
			$this->dimensions = '';

			// Show length
			if ($this->length) {
				$this->dimensions = $this->length;
				// Show width also
				if ($this->width) {
					$this->dimensions .= ' × '.$this->width;
					// Show height also
					if ($this->height) {
						$this->dimensions .= ' × '.$this->height;
					}
				}
				// Append the unit
				$this->dimensions .= ' '.get_option('woocommerce_dimension_unit');
			}
		endif;
		return $this->dimensions;
	}


	/**
	 * Lists a table of attributes for the product page.
	 *
	 * @access public
	 * @return void
	 */
	function list_attributes() {
		woocommerce_get_template('single-product/product-attributes.php', array(
			'product' => $this
		));
	}


	/**
	 * get_available_attribute_variations Deprecated - naming was confusing.
	 *
	 * @deprecated 1.5.7
	 * @access public
	 * @return void
	 */
	function get_available_attribute_variations() {
		_deprecated_function( 'get_available_attribute_variations', '1.5.7', 'get_variation_attributes' );
		return $this->get_variation_attributes();
	}


    /**
     * Return an array of attributes used for variations, as well as their possible values.
     *
     * @access public
     * @return array of attributes and their available values
     */
    function get_variation_attributes() {

	    $variation_attributes = array();

        if ( ! $this->is_type('variable') || ! $this->has_child() )
        	return $variation_attributes;

        $attributes = $this->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] )
            	continue;

            $values = array();
            $attribute_field_name = 'attribute_' . sanitize_title( $attribute['name'] );

            foreach ( $this->get_children() as $child_id ) {

                if ( get_post_status( $child_id ) != 'publish' )
                	continue; // Disabled

            	$child = $this->get_child( $child_id );

                $child_variation_attributes = $child->get_variation_attributes();

                foreach ( $child_variation_attributes as $name => $value )
                    if ( $name == $attribute_field_name )
                    	$values[] = $value;
            }

            // empty value indicates that all options for given attribute are available
            if ( in_array( '', $values ) ) {

            	$values = array();

            	// Get all options
            	if ( $attribute['is_taxonomy'] ) {
	            	$post_terms = wp_get_post_terms( $this->id, $attribute['name'] );
					foreach ( $post_terms as $term )
						$values[] = $term->slug;
				} else {
					$values = explode( '|', $attribute['value'] );
				}

				$values = array_unique( array_map( 'trim', $values ) );

			// Order custom attributes (non taxonomy) as defined
            } else {

	            if ( ! $attribute['is_taxonomy'] ) {
	            	$options 	= array_map( 'trim', explode( '|', $attribute['value'] ) );
	            	$values 	= array_intersect( $options, $values );
	            }

            }

            $variation_attributes[ $attribute['name'] ] = array_unique( $values );
        }

        return $variation_attributes;
    }

    /**
     * If set, get the default attributes for a variable product.
     *
     * @access public
     * @return array
     */
    function get_variation_default_attributes() {

    	$default = isset( $this->product_custom_fields['_default_attributes'][0] ) ? $this->product_custom_fields['_default_attributes'][0] : '';

	    return apply_filters( 'woocommerce_product_default_attributes', (array) maybe_unserialize( $default ), $this );
    }

    /**
     * Get an array of available variations for the current product.
     *
     * @access public
     * @return array
     */
    function get_available_variations() {

	    $available_variations = array();

		foreach ( $this->get_children() as $child_id ) {

			$variation = $this->get_child( $child_id );

			if ( $variation instanceof WC_Product_Variation ) {

				if ( get_post_status( $variation->get_variation_id() ) != 'publish' || ! $variation->is_visible() )
					continue; // Disabled or hidden

				$variation_attributes 	= $variation->get_variation_attributes();
				$availability 			= $variation->get_availability();
				$availability_html 		= empty( $availability['availability'] ) ? '' : apply_filters( 'woocommerce_stock_html', '<p class="stock ' . $availability['class'] . '">'. $availability['availability'].'</p>', $availability['availability']  );

				if ( has_post_thumbnail( $variation->get_variation_id() ) ) {
					$attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );

					$attachment = wp_get_attachment_image_src( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' )  );
					$image = $attachment ? current( $attachment ) : '';

					$attachment = wp_get_attachment_image_src( $attachment_id, 'full'  );
					$image_link = $attachment ? current( $attachment ) : '';

					$image_title = get_the_title( $attachment_id );
				} else {
					$image = $image_link = $image_title = '';
				}

				$available_variations[] = apply_filters( 'woocommerce_available_variation', array(
					'variation_id' 			=> $child_id,
					'attributes' 			=> $variation_attributes,
					'image_src' 			=> $image,
					'image_link' 			=> $image_link,
					'image_title'			=> $image_title,
					'price_html' 			=> $this->min_variation_price != $this->max_variation_price ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
					'availability_html' 	=> $availability_html,
					'sku' 					=> __( 'SKU:', 'woocommerce' ) . ' ' . $variation->get_sku(),
					'min_qty' 				=> 1,
					'max_qty' 				=> $this->backorders_allowed() ? '' : $variation->stock,
					'backorders_allowed' 	=> $this->backorders_allowed(),
					'is_in_stock'			=> $variation->is_in_stock(),
					'is_downloadable' 		=> $variation->is_downloadable() ,
					'is_virtual' 			=> $variation->is_virtual(),
					'is_sold_individually' 	=> $variation->is_sold_individually() ? 'yes' : 'no',
				), $this, $variation );
			}
		}

		return $available_variations;
    }


    /**
     * Returns the main product image
     *
     * @access public
     * @param string $size (default: 'shop_thumbnail')
     * @return string
     */
    function get_image( $size = 'shop_thumbnail' ) {
    	global $woocommerce;

    	$image = '';

		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size );
		} else {
			$image = '<img src="' . woocommerce_placeholder_img_src() . '" alt="Placeholder" width="' . $woocommerce->get_image_size( 'shop_thumbnail_image_width' ) . '" height="' . $woocommerce->get_image_size( 'shop_thumbnail_image_height' ) . '" />';
		}

		return $image;
    }


    /**
     * Checks sale data to see if the product is due to go on sale/sale has expired, and updates the main price.
     *
     * @access public
     * @return void
     */
    function check_sale_price() {

    	if ( $this->sale_price_dates_from && $this->sale_price_dates_from < current_time('timestamp') ) {

    		if ( $this->sale_price && $this->price !== $this->sale_price ) {

    			// Update price
    			$this->price = $this->sale_price;
    			update_post_meta( $this->id, '_price', $this->price );

    			// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
    		}

    	}

    	if ( $this->sale_price_dates_to && $this->sale_price_dates_to < current_time('timestamp') ) {

    		if ( $this->regular_price && $this->price !== $this->regular_price ) {

    			$this->price = $this->regular_price;
    			update_post_meta( $this->id, '_price', $this->price );

				// Sale has expired - clear the schedule boxes
				update_post_meta( $this->id, '_sale_price', '' );
				update_post_meta( $this->id, '_sale_price_dates_from', '' );
				update_post_meta( $this->id, '_sale_price_dates_to', '' );

				// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
			}

    	}
    }


	/**
	 * Sync grouped products with the childs lowest price (so they can be sorted by price accurately).
	 *
	 * @access public
	 * @return void
	 */
	function grouped_product_sync() {
		global $wpdb, $woocommerce;
		$post_parent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = $this->id;");

		if (!$post_parent) return;

		$children_by_price = get_posts( array(
			'post_parent' 	=> $post_parent,
			'orderby' 	=> 'meta_value_num',
			'order'		=> 'asc',
			'meta_key'	=> '_price',
			'posts_per_page' => 1,
			'post_type' 	=> 'product',
			'fields' 		=> 'ids'
		));
		if ($children_by_price) :
			foreach ($children_by_price as $child) :
				$child_price = get_post_meta($child, '_price', true);
				update_post_meta( $post_parent, '_price', $child_price );
			endforeach;
		endif;

		$woocommerce->clear_product_transients( $this->id );
	}


	/**
	 * Sync variable product prices with the childs lowest/highest prices.
	 *
	 * @access public
	 * @return void
	 */
	function variable_product_sync() {
		global $woocommerce;

		$children = get_posts( array(
			'post_parent' 	=> $this->id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		));

		$this->min_variation_price = $this->min_variation_regular_price = $this->min_variation_sale_price = $this->max_variation_price = $this->max_variation_regular_price = $this->max_variation_sale_price = '';

		if ($children) {
			foreach ( $children as $child ) {

				$child_price 		= get_post_meta($child, '_price', true);
				$child_sale_price 	= get_post_meta($child, '_sale_price', true);

				// Low price
				if (!is_numeric($this->min_variation_regular_price) || $child_price < $this->min_variation_regular_price) $this->min_variation_regular_price = $child_price;
				if ($child_sale_price!=='' && (!is_numeric($this->min_variation_sale_price) || $child_sale_price < $this->min_variation_sale_price)) $this->min_variation_sale_price = $child_sale_price;

				// High price
				if (!is_numeric($this->max_variation_regular_price) || $child_price > $this->max_variation_regular_price) $this->max_variation_regular_price = $child_price;
				if ($child_sale_price!=='' && (!is_numeric($this->max_variation_sale_price) || $child_sale_price > $this->max_variation_sale_price)) $this->max_variation_sale_price = $child_sale_price;
			}

	    	$this->min_variation_price = ($this->min_variation_sale_price==='' || $this->min_variation_regular_price < $this->min_variation_sale_price) ? $this->min_variation_regular_price : $this->min_variation_sale_price;
			$this->max_variation_price = ($this->max_variation_sale_price==='' || $this->max_variation_regular_price > $this->max_variation_sale_price) ? $this->max_variation_regular_price : $this->max_variation_sale_price;
		}

		update_post_meta( $this->id, '_price', $this->min_variation_price );
		update_post_meta( $this->id, '_min_variation_price', $this->min_variation_price );
		update_post_meta( $this->id, '_max_variation_price', $this->max_variation_price );
		update_post_meta( $this->id, '_min_variation_regular_price', $this->min_variation_regular_price );
		update_post_meta( $this->id, '_max_variation_regular_price', $this->max_variation_regular_price );
		update_post_meta( $this->id, '_min_variation_sale_price', $this->min_variation_sale_price );
		update_post_meta( $this->id, '_max_variation_sale_price', $this->max_variation_sale_price );

		$this->price = $this->min_variation_price;

		$woocommerce->clear_product_transients( $this->id );
	}
}

/**
 * woocommerce_product class.
 *
 * @extends 	WC_Product
 * @deprecated 	1.4
 * @package		WooCommerce/Classes
 */
class woocommerce_product extends WC_Product {
	public function __construct( $id ) {
		_deprecated_function( 'woocommerce_product', '1.4', 'WC_Product()' );
		parent::__construct( $id );
	}
}