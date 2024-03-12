<?php
/**
 * Variable Product
 *
 * The WooCommerce product class handles individual product data.
 *
 * @version 3.0.0
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variable product class.
 */
class WC_Product_Variable extends WC_Product {

	/**
	 * Array of children variation IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $children = null;

	/**
	 * Array of visible children variation IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $visible_children = null;

	/**
	 * Array of variation attributes IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $variation_attributes = null;

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
	 * Get the aria-describedby description for the add to cart button.
	 *
	 * @return string
	 */
	public function add_to_cart_aria_describedby() {
		/**
		 * This filter is documented in includes/abstracts/abstract-wc-product.php.
		 *
		 * @since 7.8.0
		 */
		return apply_filters( 'woocommerce_product_add_to_cart_aria_describedby', $this->is_purchasable() ? __( 'This product has multiple variants. The options may be chosen on the product page', 'woocommerce' ) : '', $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', $this->is_purchasable() ? __( 'Select options', 'woocommerce' ) : __( 'Read more', 'woocommerce' ), $this );
	}

	/**
	 * Get the add to cart button text description - used in aria tags.
	 *
	 * @since 3.3.0
	 * @return string
	 */
	public function add_to_cart_description() {
		/* translators: %s: Product title */
		return apply_filters( 'woocommerce_product_add_to_cart_description', sprintf( __( 'Select options for &ldquo;%s&rdquo;', 'woocommerce' ), $this->get_name() ), $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * Note: Variable prices do not show suffixes like other product types. This
	 * is due to some things like tax classes being set at variation level which
	 * could differ from the parent price. The only way to show accurate prices
	 * would be to load the variation and get it's price, which adds extra
	 * overhead and still has edge cases where the values would be inaccurate.
	 *
	 * Additionally, ranges of prices no longer show 'striked out' sale prices
	 * due to the strings being very long and unclear/confusing. A single range
	 * is shown instead.
	 *
	 * @param string $price Price (default: '').
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$prices = $this->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			$price = apply_filters( 'woocommerce_variable_empty_price_html', '', $this );
		} else {
			$min_price     = current( $prices['price'] );
			$max_price     = end( $prices['price'] );
			$min_reg_price = current( $prices['regular_price'] );
			$max_reg_price = end( $prices['regular_price'] );

			if ( $min_price !== $max_price ) {
				$price = wc_format_price_range( $min_price, $max_price );
			} elseif ( $this->is_on_sale() && $min_reg_price === $max_reg_price ) {
				$price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
			} else {
				$price = wc_price( $min_price );
			}

			$price = apply_filters( 'woocommerce_variable_price_html', $price . $this->get_price_suffix(), $this );
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	/**
	 * Get the suffix to display after prices > 0.
	 *
	 * This is skipped if the suffix
	 * has dynamic values such as {price_excluding_tax} for variable products.
	 *
	 * @see get_price_html for an explanation as to why.
	 * @param  string  $price Price to calculate, left blank to just use get_price().
	 * @param  integer $qty   Quantity passed on to get_price_including_tax() or get_price_excluding_tax().
	 * @return string
	 */
	public function get_price_suffix( $price = '', $qty = 1 ) {
		$suffix = get_option( 'woocommerce_price_display_suffix' );

		if ( strstr( $suffix, '{' ) ) {
			return apply_filters( 'woocommerce_get_price_suffix', '', $this, $price, $qty );
		} else {
			return parent::get_price_suffix( $price, $qty );
		}
	}

	/**
	 * Variable products themselves cannot be downloadable.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_downloadable( $context = 'view' ) {
		return false;
	}

	/**
	 * Variable products themselves cannot be virtual.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_virtual( $context = 'view' ) {
		return false;
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Ensure properties are set correctly before save.
	 *
	 * @since 3.0.0
	 */
	public function validate_props() {
		parent::validate_props();

		if ( ! $this->get_manage_stock() ) {
			$this->data_store->sync_stock_status( $this );
		}
	}

	/**
	 * Do any extra processing needed before the actual product save
	 * (but after triggering the 'woocommerce_before_..._object_save' action)
	 *
	 * @return mixed A state value that will be passed to after_data_store_save_or_update.
	 */
	protected function before_data_store_save_or_update() {
		// Get names before save.
		$previous_name = $this->data['name'];
		$new_name      = $this->get_name( 'edit' );

		return array(
			'previous_name' => $previous_name,
			'new_name'      => $new_name,
		);
	}

	/**
	 * Do any extra processing needed after the actual product save
	 * (but before triggering the 'woocommerce_after_..._object_save' action)
	 *
	 * @param mixed $state The state object that was returned by before_data_store_save_or_update.
	 */
	protected function after_data_store_save_or_update( $state ) {
		$this->data_store->sync_variation_names( $this, $state['previous_name'], $state['new_name'] );
		$this->data_store->sync_managed_variation_stock_status( $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit. What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function is_on_sale( $context = 'view' ) {
		$prices  = $this->get_variation_prices();
		$on_sale = $prices['regular_price'] !== $prices['sale_price'] && $prices['sale_price'] === $prices['price'];

		return 'view' === $context ? apply_filters( 'woocommerce_product_is_on_sale', $on_sale, $this ) : $on_sale;
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

	/**
	 * Returns whether or not the product has additional options that need
	 * selecting before adding to cart.
	 *
	 * @since  3.0.0
	 * @return boolean
	 */
	public function has_options() {
		return apply_filters( 'woocommerce_product_has_options', true, $this );
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
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
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

			do_action( 'woocommerce_variable_product_sync_data', $product );

			if ( $save ) {
				$product->save();
			}

			wc_do_deprecated_action(
				'woocommerce_variable_product_sync',
				array(
					$product->get_id(),
					$product->get_visible_children(),
				),
				'3.0',
				'woocommerce_variable_product_sync_data, woocommerce_new_product or woocommerce_update_product'
			);
		}

		return $product;
	}

	/**
	 * Sync parent stock status with the status of all children and save.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
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
