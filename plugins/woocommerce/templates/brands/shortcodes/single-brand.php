<?php
/**
 * Single Brand
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/shortcodes/single-brand.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 *
 * @see WC_Brands::output_product_brand()
 *
 * @var WP_Term $term      The term object.
 * @var string  $thumbnail The URL to the brand thumbnail.
 * @var string  $class     The class to apply to the thumbnail image.
 * @var string  $width     The width of the image.
 * @var string  $height    The height of the image.
 *
 * Ignore space indent sniff for this file, as it is used for alignment rather than actual indents.
 * phpcs:ignoreFile Generic.WhiteSpace.DisallowSpaceIndent
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @usedby  [product_brand]
 * @version 9.4.0
 */

declare( strict_types = 1);
?>
<a href="<?php echo esc_url( get_term_link( $term, 'product_brand' ) ); ?>">
	<img src="<?php echo esc_url( $thumbnail ); ?>"
	     alt="<?php echo esc_attr( $term->name ); ?>"
	     class="<?php echo esc_attr( $class ); ?>"
	     style="width: <?php echo esc_attr( $width ); ?>; height: <?php echo esc_attr( $height ); ?>;"/>
</a>
