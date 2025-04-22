<?php
/**
 * Show a brands description when on a taxonomy page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/widgets/brand-description.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

declare( strict_types = 1);

global $woocommerce;

if ( $thumbnail ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wc_get_brand_thumbnail_image( $brand );
}

echo wp_kses_post( wpautop( wptexturize( term_description() ) ) );
