<?php
/**
 * Show a grid of thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/widgets/brand-thumbnails-description.php.
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
?>
<ul class="brand-thumbnails-description">

	<?php
	foreach ( $brands as $index => $brand ) :

		/**
		 * Filter the brand's thumbnail size.
		 *
		 * @since 9.4.0
		 * @param string $size Defaults to 'shop_catalog'
		 */
		$thumbnail = wc_get_brand_thumbnail_url( $brand->term_id, apply_filters( 'woocommerce_brand_thumbnail_size', 'shop_catalog' ) );

		if ( ! $thumbnail ) {
			$thumbnail = wc_placeholder_img_src();
		}

		$class = '';

		if ( 0 === $index || 0 === $index % $columns ) {
			$class = 'first';
		} elseif ( 0 === ( $index + 1 ) % $columns ) {
			$class = 'last';
		}

		$width = floor( ( ( 100 - ( ( $columns - 1 ) * 2 ) ) / $columns ) * 100 ) / 100;
		?>
		<li class="<?php echo esc_attr( $class ); ?>" style="width: <?php echo esc_attr( $width ); ?>%;">
			<a href="<?php echo esc_url( get_term_link( $brand->slug, 'product_brand' ) ); ?>" title="<?php echo esc_attr( $brand->name ); ?>" class="term-thumbnail">
				<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $brand->name ); ?>" />
			</a>
			<div id="term-<?php echo esc_attr( $brand->term_id ); ?>" class="term-description">
				<?php echo wp_kses_post( wpautop( wptexturize( $brand->description ) ) ); ?>
			</div>
		</li>

	<?php endforeach; ?>

</ul>
