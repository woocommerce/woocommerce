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
}
