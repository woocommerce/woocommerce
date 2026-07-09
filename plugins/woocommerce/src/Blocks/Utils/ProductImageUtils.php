<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used by Product Image consumers.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductImageUtils {

	/**
	 * Get the store thumbnail aspect ratio from WooCommerce Customizer settings.
	 *
	 * @return string|null CSS aspect ratio value, or null when uncropped.
	 */
	public static function get_store_thumbnail_aspect_ratio() {
		$cropping = get_option( 'woocommerce_thumbnail_cropping', '1:1' );

		if ( 'uncropped' === $cropping ) {
			return null;
		}

		if ( 'custom' === $cropping ) {
			$width  = max( 1, (float) get_option( 'woocommerce_thumbnail_cropping_custom_width', '4' ) );
			$height = max( 1, (float) get_option( 'woocommerce_thumbnail_cropping_custom_height', '3' ) );

			return $width . '/' . $height;
		}

		return str_replace( ':', '/', $cropping );
	}

	/**
	 * Resolve the aspect ratio for a Product Image block.
	 *
	 * Block-level overrides take priority over store thumbnail cropping settings.
	 *
	 * @param array $attributes Product Image block attributes.
	 * @return string|null CSS aspect ratio value, or null when no ratio should be applied.
	 */
	public static function resolve_aspect_ratio( $attributes ) {
		if ( ! empty( $attributes['style']['dimensions']['aspectRatio'] ) ) {
			return $attributes['style']['dimensions']['aspectRatio'];
		}

		if ( ! empty( $attributes['aspectRatio'] ) ) {
			return $attributes['aspectRatio'];
		}

		$image_sizing = $attributes['imageSizing'] ?? 'single';

		if ( 'thumbnail' === $image_sizing || 'cropped' === $image_sizing ) {
			return self::get_store_thumbnail_aspect_ratio();
		}

		return null;
	}
}
