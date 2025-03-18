<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Class containing utility methods for dealing with the Cart and Checkout blocks.
 */
class CartCheckoutUtils {
	/**
	 * Caches if we're on the cart page.
	 *
	 * @var bool
	 */
	private static $is_cart_page = null;

	/**
	 * Caches if we're on the checkout page.
	 *
	 * @var bool
	 */
	private static $is_checkout_page = null;

	/**
	 * Returns true if:
	 * - The cart page is being viewed.
	 * - The page contains a cart block, cart shortcode or classic shortcode block with the cart attribute.
	 *
	 * @return bool
	 */
	public static function is_cart_page() {
		if ( null !== self::$is_cart_page ) {
			return self::$is_cart_page;
		}

		$page_id      = wc_get_page_id( 'cart' );
		$is_cart_page = $page_id && is_page( $page_id );

		if ( $is_cart_page ) {
			self::$is_cart_page = true;
		} else {
			global $post;
			// Check page contents for block/shortcode.
			self::$is_cart_page = is_a( $post, 'WP_Post' ) && ( wc_post_content_has_shortcode( 'woocommerce_cart' ) || self::has_block_variation( 'woocommerce/classic-shortcode', 'shortcode', 'cart', $post->post_content ) );
		}

		return self::$is_cart_page;
	}

	/**
	 * Returns true if shipping methods exist in the store. Excludes local pickup and only counts enabled shipping methods.
	 *
	 * @return bool true if shipping methods exist.
	 */
	public static function shipping_methods_exist() {
		// Local pickup is included with legacy shipping methods since they do not support shipping zones.
		$local_pickup_count = count(
			array_filter(
				WC()->shipping()->get_shipping_methods(),
				function ( $method ) {
					return isset( $method->enabled ) && 'yes' === $method->enabled && ! $method->supports( 'shipping-zones' ) && $method->supports( 'local-pickup' );
				}
			)
		);

		$shipping_methods_count = wc_get_shipping_method_count( true, true ) - $local_pickup_count;
		return $shipping_methods_count > 0;
	}

	/**
	 * Returns true if:
	 * - The checkout page is being viewed.
	 * - The page contains a checkout block, checkout shortcode or classic shortcode block with the checkout attribute.
	 *
	 * @return bool
	 */
	public static function is_checkout_page() {
		if ( null !== self::$is_checkout_page ) {
			return self::$is_checkout_page;
		}

		$page_id          = wc_get_page_id( 'checkout' );
		$is_checkout_page = $page_id && is_page( $page_id );

		if ( $is_checkout_page ) {
			self::$is_checkout_page = true;
		} else {
			global $post;
			// Check page contents for block/shortcode.
			self::$is_checkout_page = is_a( $post, 'WP_Post' ) && ( wc_post_content_has_shortcode( 'woocommerce_checkout' ) || self::has_block_variation( 'woocommerce/classic-shortcode', 'shortcode', 'checkout', $post->post_content ) );
		}

		return self::$is_checkout_page;
	}

	/**
	 * Check if the post content contains a block with a specific attribute value.
	 *
	 * @param string $block_id The block ID to check for.
	 * @param string $attribute The attribute to check.
	 * @param string $value The value to check for.
	 * @param string $post_content The post content to check.
	 * @return boolean
	 */
	public static function has_block_variation( $block_id, $attribute, $value, $post_content ) {
		if ( ! $post_content ) {
			return false;
		}

		if ( has_block( $block_id, $post_content ) ) {
			$blocks = (array) parse_blocks( $post_content );

			foreach ( $blocks as $block ) {
				$block_name = $block['blockName'] ?? '';

				if ( $block_name !== $block_id ) {
					continue;
				}

				if ( isset( $block['attrs'][ $attribute ] ) && $value === $block['attrs'][ $attribute ] ) {
					return true;
				}

				// `Cart` is default for `woocommerce/classic-shortcode` so it will be empty in the block attributes.
				if ( 'woocommerce/classic-shortcode' === $block_id && 'shortcode' === $attribute && 'cart' === $value && ! isset( $block['attrs']['shortcode'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if the default cart page is using the Cart block.
	 *
	 * @return bool true if the WC cart page is using the Cart block.
	 */
	public static function is_cart_block_default() {
		if ( wc_current_theme_is_fse_theme() ) {
			// Ignore the pages and check the templates.
			$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( 'cart' ), 'wp_template' );
			foreach ( $templates_from_db as $template ) {
				if ( has_block( 'woocommerce/cart', $template->content ) ) {
					return true;
				}
			}
		}
		$cart_page_id = wc_get_page_id( 'cart' );
		return $cart_page_id && has_block( 'woocommerce/cart', $cart_page_id );
	}

	/**
	 * Checks if the default checkout page is using the Checkout block.
	 *
	 * @return bool true if the WC checkout page is using the Checkout block.
	 */
	public static function is_checkout_block_default() {
		if ( wc_current_theme_is_fse_theme() ) {
			// Ignore the pages and check the templates.
			$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( 'checkout' ), 'wp_template' );
			foreach ( $templates_from_db as $template ) {
				if ( has_block( 'woocommerce/checkout', $template->content ) ) {
					return true;
				}
			}
		}
		$checkout_page_id = wc_get_page_id( 'checkout' );
		return $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );
	}

	/**
	 * Migrate checkout block field visibility attributes to settings when using the checkout block.
	 *
	 * This migration routine is called if the options (woocommerce_checkout_phone_field, woocommerce_checkout_company_field,
	 * woocommerce_checkout_address_2_field) are not set. They are not set by default; they were orignally set by the
	 * customizer interface of the legacy shortcode based checkout.
	 *
	 * Once migration is initiated, the settings will be updated and will not trigger this routine again.
	 *
	 * Note: The block only stores non-default attributes. Not all attributes will be present.
	 *
	 * e.g. `{"showCompanyField":true,"requireCompanyField":true,"showApartmentField":false,"className":"wc-block-checkout"}`
	 *
	 * If the attributes are missing, we assume default values are needed.
	 */
	protected static function migrate_checkout_block_field_visibility_attributes() {
		// Before migrating attributes, migrate the "default" options checkout block uses into the settings.
		update_option( 'woocommerce_checkout_phone_field', 'optional' );
		update_option( 'woocommerce_checkout_company_field', 'hidden' );
		update_option( 'woocommerce_checkout_address_2_field', 'optional' );

		// Parse the block from the checkout page.
		$checkout_blocks = \WC_Blocks_Utils::get_blocks_from_page( 'woocommerce/checkout', 'checkout' );

		if ( empty( $checkout_blocks ) || ! isset( $checkout_blocks[0]['attrs'] ) ) {
			return;
		}

		// Combine actual attributes with default values.
		$block_attributes = wp_parse_args(
			$checkout_blocks[0]['attrs'],
			array(
				'showPhoneField'        => true,
				'requirePhoneField'     => false,
				'showCompanyField'      => false,
				'requireCompanyField'   => false,
				'showApartmentField'    => true,
				'requireApartmentField' => false,
			)
		);

		if ( $block_attributes['showPhoneField'] ) {
			update_option( 'woocommerce_checkout_phone_field', $block_attributes['requirePhoneField'] ? 'required' : 'optional' );
		} else {
			update_option( 'woocommerce_checkout_phone_field', 'hidden' );
		}

		if ( $block_attributes['showCompanyField'] ) {
			update_option( 'woocommerce_checkout_company_field', $block_attributes['requireCompanyField'] ? 'required' : 'optional' );
		} else {
			update_option( 'woocommerce_checkout_company_field', 'hidden' );
		}

		if ( $block_attributes['showApartmentField'] ) {
			update_option( 'woocommerce_checkout_address_2_field', $block_attributes['requireApartmentField'] ? 'required' : 'optional' );
		} else {
			update_option( 'woocommerce_checkout_address_2_field', 'hidden' );
		}
	}

	/**
	 * Get the default visibility for the address_2 field.
	 *
	 * @return string
	 */
	public static function get_company_field_visibility() {
		$option_value = get_option( 'woocommerce_checkout_company_field' );

		if ( $option_value ) {
			return $option_value;
		}

		if ( self::is_checkout_block_default() ) {
			self::migrate_checkout_block_field_visibility_attributes();
			return get_option( 'woocommerce_checkout_company_field', 'hidden' );
		}

		return 'optional';
	}

	/**
	 * Get the default visibility for the address_2 field.
	 *
	 * @return string
	 */
	public static function get_address_2_field_visibility() {
		$option_value = get_option( 'woocommerce_checkout_address_2_field' );

		if ( $option_value ) {
			return $option_value;
		}

		if ( self::is_checkout_block_default() ) {
			self::migrate_checkout_block_field_visibility_attributes();
			return get_option( 'woocommerce_checkout_address_2_field', 'optional' );
		}

		return 'optional';
	}

	/**
	 * Get the default visibility for the address_2 field.
	 *
	 * @return string
	 */
	public static function get_phone_field_visibility() {
		$option_value = get_option( 'woocommerce_checkout_phone_field' );

		if ( $option_value ) {
			return $option_value;
		}

		if ( self::is_checkout_block_default() ) {
			self::migrate_checkout_block_field_visibility_attributes();
			return get_option( 'woocommerce_checkout_phone_field', 'optional' );
		}

		return 'required';
	}

	/**
	 * Checks if the template overriding the page loads the page content or not.
	 * Templates by default load the page content, but if that block is deleted the content can get out of sync with the one presented in the page editor.
	 *
	 * @param string $block The block to check.
	 *
	 * @return bool true if the template has out of sync content.
	 */
	public static function is_overriden_by_custom_template_content( string $block ): bool {

		$block = str_replace( 'woocommerce/', '', $block );

		if ( wc_current_theme_is_fse_theme() ) {
			$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( 'page-' . $block ) );
			foreach ( $templates_from_db as $template ) {
				if ( ! has_block( 'woocommerce/page-content-wrapper', $template->content ) ) {
					// Return true if the template does not load the page content via the  woocommerce/page-content-wrapper block.
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Gets country codes, names, states, and locale information.
	 *
	 * @return array
	 */
	public static function get_country_data() {
		$billing_countries  = WC()->countries->get_allowed_countries();
		$shipping_countries = WC()->countries->get_shipping_countries();
		$country_states     = wc()->countries->get_states();
		$all_countries      = self::deep_sort_with_accents( array_unique( array_merge( $billing_countries, $shipping_countries ) ) );
		$country_locales    = array_map(
			function ( $locale ) {
				foreach ( $locale as $field => $field_data ) {
					if ( isset( $field_data['priority'] ) ) {
						$locale[ $field ]['index'] = $field_data['priority'];
						unset( $locale[ $field ]['priority'] );
					}
					if ( isset( $field_data['class'] ) ) {
						unset( $locale[ $field ]['class'] );
					}
				}
				return $locale;
			},
			WC()->countries->get_country_locale()
		);

		$country_data = [];

		foreach ( array_keys( $all_countries ) as $country_code ) {
			$country_data[ $country_code ] = [
				'allowBilling'  => isset( $billing_countries[ $country_code ] ),
				'allowShipping' => isset( $shipping_countries[ $country_code ] ),
				'states'        => $country_states[ $country_code ] ?? [],
				'locale'        => $country_locales[ $country_code ] ?? [],
			];
		}

		return $country_data;
	}

	/**
	 * Removes accents from an array of values, sorts by the values, then returns the original array values sorted.
	 *
	 * @param array $sort_array Array of values to sort.
	 * @return array Sorted array.
	 */
	protected static function deep_sort_with_accents( $sort_array ) {
		if ( ! is_array( $sort_array ) || empty( $sort_array ) ) {
			return $sort_array;
		}

		$array_without_accents = array_map(
			function ( $value ) {
				return is_array( $value )
					? self::deep_sort_with_accents( $value )
					: remove_accents( wc_strtolower( html_entity_decode( $value ) ) );
			},
			$sort_array
		);

		asort( $array_without_accents );
		return array_replace( $array_without_accents, $sort_array );
	}

	/**
	 * Retrieves formatted shipping zones from WooCommerce.
	 *
	 * @return array An array of formatted shipping zones.
	 */
	public static function get_shipping_zones() {
		$shipping_zones             = \WC_Shipping_Zones::get_zones();
		$formatted_shipping_zones   = array_reduce(
			$shipping_zones,
			function ( $acc, $zone ) {
				$acc[] = [
					'id'          => $zone['id'],
					'title'       => $zone['zone_name'],
					'description' => $zone['formatted_zone_location'],
				];
				return $acc;
			},
			[]
		);
		$formatted_shipping_zones[] = [
			'id'          => 0,
			'title'       => __( 'International', 'woocommerce' ),
			'description' => __( 'Locations outside all other zones', 'woocommerce' ),
		];
		return $formatted_shipping_zones;
	}

	/**
	 * Recursively search the checkout block to find the express checkout block and
	 * get the button style attributes
	 *
	 * @param array  $blocks Blocks to search.
	 * @param string $cart_or_checkout The block type to check.
	 */
	public static function find_express_checkout_attributes( $blocks, $cart_or_checkout ) {
		$express_block_name = 'woocommerce/' . $cart_or_checkout . '-express-payment-block';
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && $express_block_name === $block['blockName'] && ! empty( $block['attrs'] ) ) {
				return $block['attrs'];
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$answer = self::find_express_checkout_attributes( $block['innerBlocks'], $cart_or_checkout );
				if ( $answer ) {
					return $answer;
				}
			}
		}
	}

	/**
	 * Given an array of blocks, find the express payment block and update its attributes.
	 *
	 * @param array  $blocks Blocks to search.
	 * @param string $cart_or_checkout The block type to check.
	 * @param array  $updated_attrs The new attributes to set.
	 */
	public static function update_blocks_with_new_attrs( &$blocks, $cart_or_checkout, $updated_attrs ) {
		$express_block_name = 'woocommerce/' . $cart_or_checkout . '-express-payment-block';
		foreach ( $blocks as $key => &$block ) {
			if ( ! empty( $block['blockName'] ) && $express_block_name === $block['blockName'] ) {
				$blocks[ $key ]['attrs'] = $updated_attrs;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::update_blocks_with_new_attrs( $block['innerBlocks'], $cart_or_checkout, $updated_attrs );
			}
		}
	}
}
