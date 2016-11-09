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
	 * Cached & hashed prices array for child variations.
	 *
	 * @var array
	 */
	private $prices_array = array();

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
			_deprecated_argument( 'visible_only', '2.7', 'WC_Product_Variable::get_visible_children' );
			return $context ? $this->get_visible_children() : $this->get_children();
		}
		if ( has_filter( 'woocommerce_get_children' ) ) {
			_deprecated_function( 'The woocommerce_get_children filter', '', 'woocommerce_product_get_children or woocommerce_product_get_visible_children' );
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
			_deprecated_function( 'The woocommerce_get_children filter', '', 'woocommerce_product_get_children or woocommerce_product_get_visible_children' );
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
			_deprecated_argument( 'display', '2.7', 'Use WC_Product_Variable::get_variation_prices_including_taxes' );
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
	public function get_variation_sale_price( $min_or_max = 'min', $inclde_taxes = false ) {
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
			$price = apply_filters( 'woocommerce_variable_price_html', wc_format_price_range( $min_price, $max_price ) . wc_get_price_suffix( $this ), $this );
		} else {
			$price = apply_filters( 'woocommerce_variable_price_html', wc_price( $min_price ) . wc_get_price_suffix( $this ), $this );
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
			'image'                 => wc_get_product_attachment_props( $variation->get_image_id() ),
			'weight_html'           => $variation->get_weight() ? $variation->get_weight() . ' ' . esc_attr( get_option( 'woocommerce_weight_unit' ) ) : '',
			'dimensions_html'       => $variation->get_dimensions(),
			'price_html'            => apply_filters( 'woocommerce_show_variation_price', $variation->get_price() === "" || $this->get_variation_price( 'min' ) !== $this->get_variation_price( 'max' ), $this, $variation ) ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
			'availability_html'     => wc_get_stock_html( $variation ),
			'variation_id'          => $variation->get_id(),
			'variation_is_visible'  => $variation->variation_is_visible(),
			'variation_is_active'   => $variation->variation_is_active(),
			'is_purchasable'        => $variation->is_purchasable(),
			'display_price'         => wc_get_price_to_display( $variation ),
			'display_regular_price' => wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ),
			'dimensions'            => $variation->get_dimensions(),
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
			$this->set_stock_status( $product->child_is_in_stock() ? 'instock' : 'outofstock' );
		}
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing product).
	 *
	 * @since 2.7.0
	 */
	public function save() {
		$this->validate_props();

		if ( $this->get_id() ) {
			$this->update();
		} else {
			$this->create();
		}

		$this->sync_managed_variation_stock_status();
		$this->apply_changes();
		$this->update_product_type();
		$this->update_product_version();
		$this->update_term_counts();
		$this->clear_caches();

		return $this->get_id();
	}

	/**
	 * Read product data.
	 *
	 * @since 2.7.0
	 */
	public function read_product_data() {
		parent::read_product_data();

		$this->read_children();

		// Set directly since individual data needs changed at the WC_Product_Variation level -- these datasets just pull.
		$this->data['variation_prices']                 = $this->read_price_data();
		$this->data['variation_prices_including_taxes'] = $this->read_price_data( true );
		$this->data['variation_attributes']             = $this->read_variation_attributes();
	}

	/**
	 * Loads variation child IDs.
	 * @param  bool $force_read True to bypass the transient.
	 * @return array
	 */
	public function read_children( $force_read = false ) {
		$children_transient_name = 'wc_product_children_' . $this->get_id();
		$children                = get_transient( $children_transient_name );

		if ( empty( $children ) || ! is_array( $children ) || ! isset( $children['all'] ) || ! isset( $children['visible'] ) || $force_read ) {
			$all_args = $visible_only_args = array(
				'post_parent' => $this->get_id(),
				'post_type'   => 'product_variation',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'fields'      => 'ids',
				'post_status' => 'publish',
				'numberposts' => -1,
			);
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				$visible_only_args['meta_query'][] = array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				);
			}
			$children['all']     = get_posts( apply_filters( 'woocommerce_variable_children_args', $all_args, $this, false ) );
			$children['visible'] = get_posts( apply_filters( 'woocommerce_variable_children_args', $visible_only_args, $this, true ) );

			set_transient( $children_transient_name, $children, DAY_IN_SECONDS * 30 );
		}

		$this->data['children']         = wp_parse_id_list( (array) $children['all'] );
		$this->data['visible_children'] = wp_parse_id_list( (array) $children['visible'] );
	}

	/**
	 * Loads an array of attributes used for variations, as well as their possible values.
	 *
	 * @return array Attributes and their available values
	 */
	private function read_variation_attributes() {
		global $wpdb;

		$variation_attributes = array();
		$attributes           = $this->get_attributes();
		$child_ids            = $this->get_children();

		if ( ! empty( $child_ids ) && ! empty( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				if ( empty( $attribute['is_variation'] ) ) {
					continue;
				}

				// Get possible values for this attribute, for only visible variations.
				$values = array_unique( $wpdb->get_col( $wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN (" . implode( ',', array_map( 'esc_sql', $child_ids ) ) . ")",
					wc_variation_attribute_name( $attribute['name'] )
				) ) );

				// Empty value indicates that all options for given attribute are available.
				if ( in_array( '', $values ) || empty( $values ) ) {
					$values = $attribute['is_taxonomy'] ? wp_get_post_terms( $this->get_id(), $attribute['name'], array( 'fields' => 'slugs' ) ) : wc_get_text_attributes( $attribute['value'] );
				// Get custom attributes (non taxonomy) as defined.
				} elseif ( ! $attribute['is_taxonomy'] ) {
					$text_attributes          = wc_get_text_attributes( $attribute['value'] );
					$assigned_text_attributes = $values;
					$values                   = array();

					// Pre 2.4 handling where 'slugs' were saved instead of the full text attribute
					if ( version_compare( get_post_meta( $this->get_id(), '_product_version', true ), '2.4.0', '<' ) ) {
						$assigned_text_attributes = array_map( 'sanitize_title', $assigned_text_attributes );
						foreach ( $text_attributes as $text_attribute ) {
							if ( in_array( sanitize_title( $text_attribute ), $assigned_text_attributes ) ) {
								$values[] = $text_attribute;
							}
						}
					} else {
						foreach ( $text_attributes as $text_attribute ) {
							if ( in_array( $text_attribute, $assigned_text_attributes ) ) {
								$values[] = $text_attribute;
							}
						}
					}
				}
				$variation_attributes[ $attribute['name'] ] = array_unique( $values );
			}
		}
		return $variation_attributes;
	}

	/**
	 * Get an array of all sale and regular prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * Can be filtered by plugins which modify costs, but otherwise will include the raw meta costs unlike get_price() which runs costs through the woocommerce_get_price filter.
	 * This is to ensure modified prices are not cached, unless intended.
	 *
	 * @since  2.7.0
	 * @param  bool $include_taxes If taxes should be calculated or not.
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	private function read_price_data( $include_taxes = false ) {
		global $wp_filter;

		/**
		 * Transient name for storing prices for this product (note: Max transient length is 45)
		 * @since 2.5.0 a single transient is used per product for all prices, rather than many transients per product.
		 */
		$transient_name = 'wc_var_prices_' . $this->get_id();

		/**
		 * Create unique cache key based on the tax location (affects displayed/cached prices), product version and active price filters.
		 * DEVELOPERS should filter this hash if offering conditonal pricing to keep it unique.
		 * @var string
		 */
		$price_hash   = $include_taxes ? array( get_option( 'woocommerce_tax_display_shop', 'excl' ), WC_Tax::get_rates() ) : array( false );
		$filter_names = array( 'woocommerce_variation_prices_price', 'woocommerce_variation_prices_regular_price', 'woocommerce_variation_prices_sale_price' );

		foreach ( $filter_names as $filter_name ) {
			if ( ! empty( $wp_filter[ $filter_name ] ) ) {
				$price_hash[ $filter_name ] = array();

				foreach ( $wp_filter[ $filter_name ] as $priority => $callbacks ) {
					$price_hash[ $filter_name ][] = array_values( wp_list_pluck( $callbacks, 'function' ) );
				}
			}
		}

		$price_hash[] = WC_Cache_Helper::get_transient_version( 'product' );
		$price_hash   = md5( json_encode( apply_filters( 'woocommerce_get_variation_prices_hash', $price_hash, $this, $include_taxes ) ) );

		/**
		 * $this->prices_array is an array of values which may have been modified from what is stored in transients - this may not match $transient_cached_prices_array.
		 * If the value has already been generated, we don't need to grab the values again so just return them. They are already filtered.
		 */
		if ( ! empty( $this->prices_array[ $price_hash ] ) ) {
			return $this->prices_array[ $price_hash ];

		/**
		 * No locally cached value? Get the data from the transient or generate it.
		 */
		} else {
			// Get value of transient
			$transient_cached_prices_array = array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) );

			// If the product version has changed since the transient was last saved, reset the transient cache.
			if ( empty( $transient_cached_prices_array['version'] ) || WC_Cache_Helper::get_transient_version( 'product' ) !== $transient_cached_prices_array['version'] ) {
				$transient_cached_prices_array = array( 'version' => WC_Cache_Helper::get_transient_version( 'product' ) );
			}

			// If the prices are not stored for this hash, generate them and add to the transient.
			if ( empty( $transient_cached_prices_array[ $price_hash ] ) ) {
				$prices         = array();
				$regular_prices = array();
				$sale_prices    = array();
				$variation_ids  = $this->get_visible_children();
				foreach ( $variation_ids as $variation_id ) {
					if ( $variation = wc_get_product( $variation_id, array( 'parent_id' => $this->get_id(), 'parent' => $this ) ) ) {
						$price         = apply_filters( 'woocommerce_variation_prices_price', $variation->get_price(), $variation, $this );
						$regular_price = apply_filters( 'woocommerce_variation_prices_regular_price', $variation->get_regular_price(), $variation, $this );
						$sale_price    = apply_filters( 'woocommerce_variation_prices_sale_price', $variation->get_sale_price(), $variation, $this );

						// Skip empty prices
						if ( '' === $price ) {
							continue;
						}

						// If sale price does not equal price, the product is not yet on sale
						if ( $sale_price === $regular_price || $sale_price !== $price ) {
							$sale_price = $regular_price;
						}

						// If we are getting prices for display, we need to account for taxes
						if ( $include_taxes ) {
							if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
								$price         = '' === $price ? ''         : wc_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $price ) );
								$regular_price = '' === $regular_price ? '' : wc_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $regular_price ) );
								$sale_price    = '' === $sale_price ? ''    : wc_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $sale_price ) );
							} else {
								$price         = '' === $price ? ''         : wc_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $price ) );
								$regular_price = '' === $regular_price ? '' : wc_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $regular_price ) );
								$sale_price    = '' === $sale_price ? ''    : wc_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $sale_price ) );
							}
						}

						$prices[ $variation_id ]         = wc_format_decimal( $price, wc_get_price_decimals() );
						$regular_prices[ $variation_id ] = wc_format_decimal( $regular_price, wc_get_price_decimals() );
						$sale_prices[ $variation_id ]    = wc_format_decimal( $sale_price . '.00', wc_get_price_decimals() );
					}
				}

				asort( $prices );
				asort( $regular_prices );
				asort( $sale_prices );

				$transient_cached_prices_array[ $price_hash ] = array(
					'price'         => $prices,
					'regular_price' => $regular_prices,
					'sale_price'    => $sale_prices,
				);

				set_transient( $transient_name, json_encode( $transient_cached_prices_array ), DAY_IN_SECONDS * 30 );
			}

			/**
			 * Give plugins one last chance to filter the variation prices array which has been generated and store locally to the class.
			 * This value may differ from the transient cache. It is filtered once before storing locally.
			 */
			return $this->prices_array[ $price_hash ] = apply_filters( 'woocommerce_variation_prices', $transient_cached_prices_array[ $price_hash ], $this, $include_taxes );
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
		$prices = $this->read_price_data();
		return apply_filters( 'woocommerce_product_is_on_sale', $prices['regular_price'] !== $prices['sale_price'] && $prices['sale_price'] === $prices['price'], $this );
	}

	/**
	 * Is a child in stock?
	 * @return boolean
	 */
	public function child_is_in_stock() {
		global $wpdb;

		$transient_name = 'wc_child_is_in_stock_' . $this->get_id();
		$in_stock       = get_transient( $transient_name );

		if ( false === $in_stock ) {
			$children = $this->get_visible_children( 'edit' );
			$in_stock = $children ? $wpdb->get_var( "SELECT 1 FROM $wpdb->postmeta WHERE meta_key = '_stock_status' AND meta_value = 'instock' AND post_id IN ( " . implode( ',', array_map( 'absint', $children ) ) . " )" ) : false;
			set_transient( $transient_name, $in_stock, DAY_IN_SECONDS * 30 );
		}
		return (bool) $in_stock;
	}

	/**
	 * Does a child have a weight set?
	 * @return boolean
	 */
	public function child_has_weight() {
		global $wpdb;

		$transient_name = 'wc_child_has_weight_' . $this->get_id();
		$has_weight     = get_transient( $transient_name );

		if ( false === $has_weight ) {
			$children   = $this->get_visible_children( 'edit' );
			$has_weight = $children ? $wpdb->get_var( "SELECT 1 FROM $wpdb->postmeta WHERE meta_key = '_weight' AND meta_value > 0 AND post_id IN ( " . implode( ',', array_map( 'absint', $children ) ) . " )" ) : false;
			set_transient( $transient_name, $has_weight, DAY_IN_SECONDS * 30 );
		}
		return (bool) $has_weight;
	}

	/**
	 * Does a child have dimensions set?
	 * @return boolean
	 */
	public function child_has_dimensions() {
		global $wpdb;

		$transient_name = 'wc_child_has_dimensions_' . $this->get_id();
		$has_dimension  = get_transient( $transient_name );

		if ( false === $has_dimension ) {
			$children      = $this->get_visible_children( 'edit' );
			$has_dimension = $children ? $wpdb->get_var( "SELECT 1 FROM $wpdb->postmeta WHERE meta_key IN ( '_length', '_width', '_height' ) AND post_id IN ( " . implode( ',', array_map( 'absint', $children ) ) . " )" ) : false;
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
		return $this->get_length() || $this->get_height() || $this->get_width();
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
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @return bool
	 */
	public function enable_dimensions_display() {
		return apply_filters( 'wc_product_enable_dimensions_display', true ) && ( $this->has_dimensions() || $this->has_weight() || $this->child_has_weight() || $this->child_has_dimensions() );
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
	 * Stock managed at the parent level - update children being managed by this product.
	 *
	 * This sync function syncs downwards (from parent to child) when the variable product is saved.
	 */
	private function sync_managed_variation_stock_status() {
		global $wpdb;

		if ( $this->get_manage_stock() ) {
			$status           = $this->get_stock_status();
			$children         = $this->get_children();
			$managed_children = $children ? array_unique( $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_manage_stock' AND meta_value != 'yes' AND post_id IN ( " . implode( ',', array_map( 'absint', $children ) ) . " )" ) ) : array();
			$changed          = false;
			foreach ( $managed_children as $managed_child ) {
				if ( update_post_meta( $managed_child, '_stock_status', $status ) ) {
					$changed = true;
				}
			}
			if ( $changed ) {
				$this->read_children( true );
			}
		}
	}

	/**
	 * Sync the variable product with it's children.
	 *
	 * These sync functions sync upwards (from child to parent) when the variation is saved.
	 * @param WC_Product|int $product
	 * @param bool $saving If this is a sync event during save, this will be true. Avoid calling WC_Product::save() as this will be done for you.
	 */
	public static function sync( &$product, $saving = false ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		self::sync_stock_status( $product, $saving );
		self::sync_price( $product );
		self::sync_attributes( $product );
		do_action( 'woocommerce_variable_product_sync', $product->get_id(), $product->get_visible_children( 'edit' ), $saving );
	}

	/**
	 * Sync variable product prices with children.
	 * @since 2.7.0
	 * @param WC_Product|int $product
	 */
	protected static function sync_price( &$product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		global $wpdb;

		$children = $product->get_visible_children( 'edit' );
		$prices   = $children ? array_unique( $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_price' AND post_id IN ( " . implode( ',', array_map( 'absint', $children ) ) . " )" ) ) : array();

		delete_post_meta( $product->get_id(), '_price' );

		if ( $prices ) {
			sort( $prices );
			// To allow sorting and filtering by multiple values, we have no choice but to store child prices in this manner.
			foreach ( $prices as $price ) {
				add_post_meta( $product->get_id(), '_price', $price, false );
			}
		}
	}

	/**
	 * Sync VARIATIONS with the PARENT.
	 * @param WC_Product|int $product
	 */
	public static function sync_stock_status( &$product, $saving = false ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		$product->set_stock_status( $product->child_is_in_stock() ? 'instock' : 'outofstock' );

		if ( ! $saving ) {
			$product->save();
		}
	}
}
