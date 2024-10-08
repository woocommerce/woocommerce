<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Class containing utility methods for dealing with the Cart and Checkout blocks.
 */
class CartCheckoutUtils {

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
		$country_locales    = wc()->countries->get_country_locale();
		$country_states     = wc()->countries->get_states();
		$all_countries      = self::deep_sort_with_accents( array_unique( array_merge( $billing_countries, $shipping_countries ) ) );

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
	 * @param array $array Array of values to sort.
	 * @return array Sorted array.
	 */
	protected static function deep_sort_with_accents( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return $array;
		}

		$array_without_accents = array_map(
			function ( $value ) {
				return is_array( $value )
					? self::deep_sort_with_accents( $value )
					: remove_accents( wc_strtolower( html_entity_decode( $value ) ) );
			},
			$array
		);

		asort( $array_without_accents );
		return array_replace( $array_without_accents, $array );
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
