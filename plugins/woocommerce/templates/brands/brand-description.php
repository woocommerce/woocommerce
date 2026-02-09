<?php
/**
 * Brand description
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/brand-description.php.
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

$image_size = wc_get_image_size( 'shop_catalog' ); ?>
<div class="term-description brand-description">

	<?php if ( $thumbnail ) : ?>

		<img src="<?php echo esc_url( $thumbnail ); ?>" alt="Thumbnail" class="wp-post-image alignright fr brand-thumbnail" width="<?php echo esc_attr( $image_size['width'] ); ?>" />

	<?php endif; ?>

	<div class="text">

		<?php echo do_shortcode( wpautop( wptexturize( term_description() ) ) ); ?>

	</div>

</div>
