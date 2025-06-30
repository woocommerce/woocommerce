<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used for the Mini Cart block.
 */
class MiniCartUtils {
	/**
	 * Migrate attributes to color panel component format.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 * @return array Reformatted attributes that are compatible with the color panel component.
	 */
	public static function migrate_attributes_to_color_panel( $attributes ) {
		if ( isset( $attributes['priceColorValue'] ) && ! isset( $attributes['priceColor'] ) ) {
			$attributes['priceColor'] = array(
				'color' => $attributes['priceColorValue'],
			);
			unset( $attributes['priceColorValue'] );
		}

		if ( isset( $attributes['iconColorValue'] ) && ! isset( $attributes['iconColor'] ) ) {
			$attributes['iconColor'] = array(
				'color' => $attributes['iconColorValue'],
			);
			unset( $attributes['iconColorValue'] );
		}

		if ( isset( $attributes['productCountColorValue'] ) && ! isset( $attributes['productCountColor'] ) ) {
			$attributes['productCountColor'] = array(
				'color' => $attributes['productCountColorValue'],
			);
			unset( $attributes['productCountColorValue'] );
		}

		return $attributes;
	}

	/**
	 * Get the SVG icon for the mini cart.
	 *
	 * @param string $icon_name The name of the icon.
	 * @param string $icon_color The color of the icon.
	 * @return string The SVG icon.
	 */
	public static function get_svg_icon( $icon_name, $icon_color = 'currentColor' ) {
		// Default "Cart" icon.
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="' . esc_attr( $icon_color ) . '" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><circle cx="12.667" cy="24.667" r="2"/><circle cx="23.333" cy="24.667" r="2"/><path fill-rule="evenodd" d="M9.285 10.036a1 1 0 0 1 .776-.37h15.272a1 1 0 0 1 .99 1.142l-1.333 9.333A1 1 0 0 1 24 21H12a1 1 0 0 1-.98-.797L9.083 10.87a1 1 0 0 1 .203-.834m2.005 1.63L12.814 19h10.319l1.047-7.333z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M5.667 6.667a1 1 0 0 1 1-1h2.666a1 1 0 0 1 .984.82l.727 4a1 1 0 1 1-1.967.359l-.578-3.18H6.667a1 1 0 0 1-1-1" clip-rule="evenodd"/></svg>';

		if ( isset( $icon_name ) ) {
			if ( 'bag' === $icon_name ) {
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="' . esc_attr( $icon_color ) . '" fill-rule="evenodd" d="M12.444 14.222a.89.89 0 0 1 .89.89 2.667 2.667 0 0 0 5.333 0 .889.889 0 1 1 1.777 0 4.444 4.444 0 1 1-8.888 0c0-.492.398-.89.888-.89M11.24 6.683a1 1 0 0 1 .76-.35h8a1 1 0 0 1 .76.35l4 4.666A1 1 0 0 1 24 13H8a1 1 0 0 1-.76-1.65zm1.22 1.65L10.174 11h11.652L19.54 8.333z" clip-rule="evenodd"/><path fill="' . esc_attr( $icon_color ) . '" fill-rule="evenodd" d="M7 12a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1zm2 1v11.333h14V13z" clip-rule="evenodd"/></svg>';
			} elseif ( 'bag-alt' === $icon_name ) {
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="' . esc_attr( $icon_color ) . '" fill-rule="evenodd" d="M19.556 12.333a.89.89 0 0 1-.89-.889c0-.707-.28-3.385-.78-3.885a2.667 2.667 0 0 0-3.772 0c-.5.5-.78 3.178-.78 3.885a.889.889 0 1 1-1.778 0c0-1.178.468-4.309 1.301-5.142a4.445 4.445 0 0 1 6.286 0c.833.833 1.302 3.964 1.302 5.142a.89.89 0 0 1-.89.89" clip-rule="evenodd"/><path fill="' . esc_attr( $icon_color ) . '" fill-rule="evenodd" d="M7.5 12a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zm2 1v11.333h13V13z" clip-rule="evenodd"/></svg>';
			}
		}

		return $icon;
	}
}
