<?php
/**
 * Brands Helper Functions
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @package WooCommerce
 * @version 9.4.0
 */

declare( strict_types = 1);

/**
 * Helper function :: wc_get_brand_thumbnail_url function.
 *
 * @param  int    $brand_id Brand ID.
 * @param  string $size     Thumbnail image size.
 * @return string
 */
function wc_get_brand_thumbnail_url( $brand_id, $size = 'full' ) {
	$thumbnail_id = get_term_meta( $brand_id, 'thumbnail_id', true );

	if ( $thumbnail_id ) {
		$thumb_src = wp_get_attachment_image_src( $thumbnail_id, $size );
	}

	return ! empty( $thumb_src ) ? current( $thumb_src ) : '';
}

/**
 * Helper function :: wc_get_brand_thumbnail_image function.
 *
 * @since 9.4.0
 *
 * @param  object $brand Brand term.
 * @param  string $size  Thumbnail image size.
 * @return string
 */
function wc_get_brand_thumbnail_image( $brand, $size = '' ) {
	$thumbnail_id = get_term_meta( $brand->term_id, 'thumbnail_id', true );

	if ( '' === $size || 'brand-thumb' === $size ) {
		/**
		 * Filter the brand's thumbnail size.
		 *
		 * @since 9.4.0
		 *
		 * @param string $size Brand's thumbnail size.
		 */
		$size = apply_filters( 'woocommerce_brand_thumbnail_size', 'shop_catalog' );
	}

	if ( $thumbnail_id ) {
		$image_src    = wp_get_attachment_image_src( $thumbnail_id, $size );
		$image_src    = $image_src[0];
		$dimensions   = wc_get_image_size( $size );
		$image_srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $thumbnail_id, $size ) : false;
		$image_sizes  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $thumbnail_id, $size ) : false;
	} else {
		$image_src    = wc_placeholder_img_src();
		$dimensions   = wc_get_image_size( $size );
		$image_srcset = false;
		$image_sizes  = false;
	}

	// Add responsive image markup if available.
	if ( $image_srcset && $image_sizes ) {
		$image = '<img src="' . esc_url( $image_src ) . '" alt="' . esc_attr( $brand->name ) . '" class="brand-thumbnail" width="' . esc_attr( $dimensions['width'] ) . '" height="' . esc_attr( $dimensions['height'] ) . '" srcset="' . esc_attr( $image_srcset ) . '" sizes="' . esc_attr( $image_sizes ) . '" />';
	} else {
		$image = '<img src="' . esc_url( $image_src ) . '" alt="' . esc_attr( $brand->name ) . '" class="brand-thumbnail" width="' . esc_attr( $dimensions['width'] ) . '" height="' . esc_attr( $dimensions['height'] ) . '" />';
	}

	return $image;
}

/**
 * Retrieves product's brands.
 *
 * @param  int    $post_id Post ID (default: 0).
 * @param  string $sep     Seperator (default: ').
 * @param  string $before  Before item (default: '').
 * @param  string $after   After item (default: '').
 * @return array  List of terms
 */
function wc_get_brands( $post_id = 0, $sep = ', ', $before = '', $after = '' ) {
	global $post;

	if ( ! $post_id ) {
		$post_id = $post->ID;
	}

	return get_the_term_list( $post_id, 'product_brand', $before, $sep, $after );
}

/**
 * Polyfills for backwards compatibility with the WooCommerce Brands plugin.
 */

if ( ! function_exists( 'get_brand_thumbnail_url' ) ) {

	/**
	 * Polyfill for get_brand_thumbnail_image.
	 *
	 * @param int    $brand_id Brand ID.
	 * @param string $size Thumbnail image size.
	 * @return string
	 */
	function get_brand_thumbnail_url( $brand_id, $size = 'full' ) {
		return wc_get_brand_thumbnail_url( $brand_id, $size );
	}
}

if ( ! function_exists( 'get_brand_thumbnail_image' ) ) {

	/**
	 * Polyfill for get_brand_thumbnail_image.
	 *
	 * @param object $brand Brand term.
	 * @param string $size Thumbnail image size.
	 * @return string
	 */
	function get_brand_thumbnail_image( $brand, $size = '' ) {
		return wc_get_brand_thumbnail_image( $brand, $size );
	}
}

if ( ! function_exists( 'get_brands' ) ) {

	/**
	 * Polyfill for get_brands.
	 *
	 * @param  int    $post_id Post ID (default: 0).
	 * @param  string $sep     Seperator (default: ').
	 * @param  string $before  Before item (default: '').
	 * @param  string $after   After item (default: '').
	 * @return array  List of terms
	 */
	function get_brands( $post_id = 0, $sep = ', ', $before = '', $after = '' ) {
		return wc_get_brands( $post_id, $sep, $before, $after );
	}
}
