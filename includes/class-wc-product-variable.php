<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable Product Class.
 *
 * The WooCommerce product class handles individual product data.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Variable extends WC_Product {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'children'                         => array(),
		'visible_children'                 => array(),
		'variation_prices'                 => array(),
		'variation_prices_including_taxes' => array(),
		'variation_attributes'             => array(),
	);

	/**
	 * Merges variable product data into the parent object.
	 *
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		$this->data = array_merge( $this->data, $this->extra_data );
		parent::__construct( $product );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'variable';
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return a products child ids.
	 *
	 * @param  string $context
	 * @return array Children ids
	 */
	public function get_children( $context = 'view' ) {
		if ( is_bool( $context ) ) {
			wc_deprecated_argument( 'visible_only', '2.7', 'WC_Product_Variable::get_visible_children' );
			return $context ? $this->get_visible_children() : $this->get_children();
		}
		if ( has_filter( 'woocommerce_get_children' ) ) {
			wc_deprecated_function( 'The woocommerce_get_children filter', '', 'woocommerce_product_get_children or woocommerce_product_get_visible_children' );
		}
		return apply_filters( 'woocommerce_get_children', $this->get_prop( 'children', $context ), $this, false );
	}

	/**
	 * Return a products child ids - visible only.
	 *
	 * @since 2.7.0
	 * @param  string $context
	 * @return array Children ids
	 */
	public function get_visible_children( $context = 'view' ) {
		if ( has_filter( 'woocommerce_get_children' ) ) {
			wc_deprecated_function( 'The woocommerce_get_children filter', '', 'woocommerce_product_get_children or woocommerce_product_get_visible_children' );
		}
		return apply_filters( 'woocommerce_get_children', $this->get_prop( 'visible_children', $context ), $this, true );
	}

	/**
	 * Return an array of attributes used for variations, as well as their possible values.
	 *
	 * @param  string $context
	 * @return array Attributes and their available values
	 */
	public function get_variation_attributes( $context = 'view' ) {
		return $this->get_prop( 'variation_attributes', $context );
	}

	/**
	 * Get an array of all sale and regular prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * @param  string $context
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_prices( $context = 'view' ) {
		if ( is_bool( $context ) ) {
			wc_deprecated_argument( 'display', '2.7', 'Use WC_Product_Variable::get_variation_prices_including_taxes' );
			return $context ? $this->get_variation_prices_including_taxes() : $this->get_variation_prices();
		}
		return $this->get_prop( 'variation_prices', $context );
	}

	/**
	 * Get an array of all sale and regular prices from all variations, includes taxes.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_prices_including_taxes( $context = 'view' ) {
		return $this->get_prop( 'variation_prices_including_taxes', $context );
	}

	/**
	 * Get the min or max variation regular price.
	 *
	 * @param  string  $min_or_max    Min or max price.
	 * @param  boolean $include_taxes Should the price include taxes?
	 * @return string
	 */
	public function get_variation_regular_price( $min_or_max = 'min', $include_taxes = false ) {
		$prices = $include_taxes ? $this->get_variation_prices_including_taxes() : $this->get_variation_prices();
		$price  = 'min' === $min_or_max ? current( $prices['regular_price'] ) : end( $prices['regular_price'] );
		return apply_filters( 'woocommerce_get_variation_regular_price', $price, $this, $min_or_max, $include_taxes );
	}

	/**
	 * Get the min or max variation sale price.
	 *
	 * @param  string  $min_or_max    Min or max price.
	 * @param  boolean $include_taxes Should the price include taxes?
	 * @return string
	 */
	public function get_variation_sale_price( $min_or_max = 'min', $include_taxes = false ) {
		$prices = $include_taxes ? $this->get_variation_prices_including_taxes() : $this->get_variation_prices();
		$price  = 'min' === $min_or_max ? current( $prices['sale_price'] ) : end( $prices['sale_price'] );
		return apply_filters( 'woocommerce_get_variation_sale_price', $price, $this, $min_or_max, $include_taxes );
	}

	/**
	 * Get the min or max variation (active) price.
	 *
	 * @param  string  $min_or_max    Min or max price.
	 * @param  boolean $include_taxes Should the price include taxes?
	 * @return string
	 */
	public function get_variation_price( $min_or_max = 'min', $include_taxes = false ) {
		$prices = $include_taxes ? $this->get_variation_prices_including_taxes() : $this->get_variation_prices();
		$price  = 'min' === $min_or_max ? current( $prices['price'] ) : end( $prices['price'] );
		return apply_filters( 'woocommerce_get_variation_price', $price, $this, $min_or_max, $include_taxes );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$prices = $this->get_variation_prices_including_taxes();

		if ( empty( $prices['price'] ) ) {
			return apply_filters( 'woocommerce_variable_empty_price_html', '', $this );
		}

		$min_price = current( $prices['price'] );
		$max_price = end( $prices['price'] );

		if ( $min_price !== $max_price ) {
			$price = apply_filters( 'woocommerce_variable_price_html', wc_format_price_range( $min_price, $max_price ), $this );
		} else {
			$price = apply_filters( 'woocommerce_variable_price_html', wc_price( $min_price ) . $this->get_price_suffix(), $this );
		}
		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	/**
	 * If set, get the default attributes for a variable product.
	 *
	 * @param string $attribute_name
	 * @return string
	 */
	public function get_variation_default_attribute( $attribute_name ) {
		$defaults       = $this->get_default_attributes();
		$attribute_name = sanitize_title( $attribute_name );
		return isset( $defaults[ $attribute_name ] ) ? $defaults[ $attribute_name ] : '';
	}

	/**
	 * Variable products themselves cannot be downloadable.
	 */
	public function get_downloadable( $context = 'view' ) {
		return false;
	}

	/**
	 * Variable products themselves cannot be virtual.
	 */
	public function get_virtual( $context = 'view' ) {
		return false;
	}

	/**
	 * Get an array of available variations for the current product.
	 *
	 * @return array
	 */
	public function get_available_variations() {
		$available_variations = array();

		foreach ( $this->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );

			// Hide out of stock variations if 'Hide out of stock items from the catalog' is checked
			if ( ! $variation->exists() || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) ) {
				continue;
			}

			// Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price)
			if ( apply_filters( 'woocommerce_hide_invisible_variations', false, $this->get_id(), $variation ) && ! $variation->variation_is_visible() ) {
				continue;
			}

			$available_variations[] = $this->get_available_variation( $variation );
		}

		return $available_variations;
	}

	/**
	 * Returns an array of data for a variation. Used in the add to cart form.
	 * @since  2.4.0
	 * @param  WC_Product $variation Variation product object or ID
	 * @return array
	 */
	public function get_available_variation( $variation ) {
		if ( is_numeric( $variation ) ) {
			$variation = wc_get_product( $variation );
		}

		return apply_filters( 'woocommerce_available_variation', array_merge( $variation->get_data(), array(
			'attributes'            => $variation->get_variation_attributes(),
			'image'                 => wc_get_product_attachment_props( $variation->get_image_id() ),
			'weight_html'           => wc_format_weight( $variation->get_weight() ),
			'dimensions_html'       => wc_format_dimensions( $variation->get_dimensions( false ) ),
			'price_html'            => apply_filters( 'woocommerce_show_variation_price', $variation->get_price() === "" || $this->get_variation_price( 'min' ) !== $this->get_variation_price( 'max' ), $this, $variation ) ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
			'availability_html'     => wc_get_stock_html( $variation ),
			'variation_id'          => $variation->get_id(),
			'variation_is_visible'  => $variation->variation_is_visible(),
			'variation_is_active'   => $variation->variation_is_active(),
			'is_purchasable'        => $variation->is_purchasable(),
			'display_price'         => wc_get_price_to_display( $variation ),
			'display_regular_price' => wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ),
			'dimensions'            => wc_format_dimensions( $variation->get_dimensions( false ) ),
			'min_qty'               => 1,
			'max_qty'               => $variation->backorders_allowed() ? '' : $variation->get_stock_quantity(),
			'backorders_allowed'    => $variation->backorders_allowed(),
			'is_in_stock'           => $variation->is_in_stock(),
			'is_downloadable'       => $variation->is_downloadable(),
			'is_virtual'            => $variation->is_virtual(),
			'is_sold_individually'  => $variation->is_sold_individually() ? 'yes' : 'no',
			'variation_description' => $variation->get_description(),
		) ), $this, $variation );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sets an array of variation prices.
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_variation_prices( $variation_prices ) {
		$this->set_prop( 'variation_prices', $variation_prices );
	}

	/**
	 * Sets an array of variation prices, including taxes.
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_variation_prices_including_taxes( $variation_prices_including_taxes ) {
		$this->set_prop( 'variation_prices_including_taxes', $variation_prices_including_taxes );
	}

	/**
	 * Sets an array of variation attributes
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_variation_attributes( $variation_attributes ) {
		$this->set_prop( 'variation_attributes', $variation_attributes );
	}

	/**
	 * Sets an array of children for the product.
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_children( $children ) {
		$this->set_prop( 'children', array_filter( wp_parse_id_list( (array) $children ) ) );
	}

	/**
	 * Sets an array of visible children only.
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_visible_children( $visible_children ) {
		$this->set_prop( 'visible_children', array_filter( wp_parse_id_list( (array) $visible_children ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Ensure properties are set correctly before save.
	 * @since 2.7.0
	 */
	public function validate_props() {
		// Before updating, ensure stock props are all aligned. Qty and backorders are not needed if not stock managed.
		if ( ! $this->get_manage_stock() ) {
			$this->set_stock_quantity( '' );
			$this->set_backorders( 'no' );

		// If we are stock managing and we don't have stock, force out of stock status.
		} elseif ( $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$this->set_stock_status( 'outofstock' );

		// If the stock level is changing and we do now have enough, force in stock status.
		} elseif ( $this->get_stock_quantity() > get_option( 'woocommerce_notify_no_stock_amount' ) && array_key_exists( 'stock_quantity', $this->get_changes() ) ) {
			$this->set_stock_status( 'instock' );

		// Otherwise revert to status the children have.
		} else {
			$this->set_stock_status( $this->child_is_in_stock() ? 'instock' : 'outofstock' );
		}
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing product).
	 *
	 * @since 2.7.0
	 */
	public function save() {
		$this->validate_props();
		if ( $this->data_store ) {
			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			$this->data_store->sync_managed_variation_stock_status( $this );

			return $this->get_id();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns whether or not the product is on sale.
	 * @return bool
	 */
	public function is_on_sale() {
		$data_store = WC_Data_Store::load( 'product-variable' );
		$prices = $data_store->read_price_data( $this );
		return apply_filters( 'woocommerce_product_is_on_sale', $prices['regular_price'] !== $prices['sale_price'] && $prices['sale_price'] === $prices['price'], $this );
	}

	/**
	 * Is a child in stock?
	 * @return boolean
	 */
	public function child_is_in_stock() {
		$transient_name = 'wc_child_is_in_stock_' . $this->get_id();
		$in_stock       = get_transient( $transient_name );

		if ( false === $in_stock ) {
			$in_stock = $this->data_store->child_is_in_stock( $this );
			set_transient( $transient_name, $in_stock, DAY_IN_SECONDS * 30 );
		}
		return (bool) $in_stock;
	}

	/**
	 * Does a child have a weight set?
	 * @return boolean
	 */
	public function child_has_weight() {
		$transient_name = 'wc_child_has_weight_' . $this->get_id();
		$has_weight     = get_transient( $transient_name );

		if ( false === $has_weight ) {
			$has_weight = $this->data_store->child_has_weight( $this );
			set_transient( $transient_name, $has_weight, DAY_IN_SECONDS * 30 );
		}
		return (bool) $has_weight;
	}

	/**
	 * Does a child have dimensions set?
	 * @return boolean
	 */
	public function child_has_dimensions() {
		$transient_name = 'wc_child_has_dimensions_' . $this->get_id();
		$has_dimension  = get_transient( $transient_name );

		if ( false === $has_dimension ) {
			$has_dimension = $this->data_store->child_has_dimensions( $this );
			set_transient( $transient_name, $has_dimension, DAY_IN_SECONDS * 30 );
		}
		return (bool) $has_dimension;
	}

	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @return bool
	 */
	public function has_dimensions() {
		return parent::has_dimensions() || $this->child_has_dimensions();
	}

	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @return bool
	 */
	public function has_weight() {
		return parent::has_weight() || $this->child_has_weight();
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', $this->is_purchasable() ? __( 'Select options', 'woocommerce' ) : __( 'Read more', 'woocommerce' ), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Sync with child variations.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sync a variable product with it's children. These sync functions sync
	 * upwards (from child to parent) when the variation is saved.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool $save If true, the prouduct object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync( $product, $save = true ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		if ( is_a( $product, 'WC_Product_Variable' ) ) {
			$data_store = WC_Data_Store::load( 'product-' . $product->get_type() );
			$data_store->sync_price( $product );
			$data_store->sync_stock_status( $product );
			self::sync_attributes( $product ); // Legacy update of attributes.

			do_action( 'woocommerce_variable_product_sync', $product->get_id(), $product->get_visible_children( 'edit' ), $save );

			if ( $save ) {
				$product->save();
			}
		}
		return $product;
	}

	/**
	 * Sync parent stock status with the status of all children and save.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool $save If true, the prouduct object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync_stock_status( $product, $save = true ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		if ( is_a( $product, 'WC_Product_Variable' ) ) {
			$data_store = WC_Data_Store::load( 'product-' . $product->get_type() );
			$data_store->sync_stock_status( $product );

			if ( $save ) {
				$product->save();
			}
		}
		return $product;
	}
}
