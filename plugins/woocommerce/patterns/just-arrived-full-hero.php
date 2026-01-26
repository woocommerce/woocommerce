<?php
/**
 * Title: Just Arrived Full Hero
 * Slug: woocommerce-blocks/just-arrived-full-hero
 * Categories: WooCommerce, Intro
 */

$pattern_title       = __( 'Sound like no other', 'woocommerce' );
$pattern_description = __( 'Experience your music like never before with our latest generation of hi-fidelity headphones.', 'woocommerce' );
$pattern_button      = __( 'Shop now', 'woocommerce' );
$pattern_image       = plugins_url( 'assets/images/pattern-placeholders/man-person-music-black-and-white-white-photography.jpg', WC_PLUGIN_FILE );
?>

<!-- wp:cover {"url":"<?php echo esc_url( $pattern_image ); ?>","dimRatio":50,"focalPoint":{"x":0.5,"y":0.21},"minHeight":739,"contentPosition":"center right","align":"full"} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-center-right" style="min-height:739px">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span>
	<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $pattern_image ); ?>" style="object-position:50% 21%" data-object-fit="cover" data-object-position="50% 21%" />
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"style":{"spacing":{"padding":{"right":"60px","left":"60px"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
		<div class="wp-block-group" style="padding-right:60px;padding-left:60px">
			<!-- wp:heading -->
			<h2 class="wp-block-heading" id="just-arrived"><?php echo esc_html( $pattern_title ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $pattern_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button">
					<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo esc_html( $pattern_button ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
