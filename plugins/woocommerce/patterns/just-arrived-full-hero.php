<?php
/**
 * Title: Just Arrived Full Hero
 * Slug: woocommerce-blocks/just-arrived-full-hero
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$pattern_title       = $content['titles'][0]['default'] ?? '';
$pattern_description = $content['descriptions'][0]['default'] ?? '';
$pattern_button      = $content['buttons'][0]['default'] ?? '';
$pattern_image       = PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/man-person-music-black-and-white-white-photography.jpg' );
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
					<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php echo esc_html( $pattern_button ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
