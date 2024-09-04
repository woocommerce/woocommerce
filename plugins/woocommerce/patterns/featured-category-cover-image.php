<?php
/**
 * Title: Featured Category Cover Image
 * Slug: woocommerce-blocks/featured-category-cover-image
 * Categories: WooCommerce, Intro
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/table-wood-house-chair-floor-window.jpg' );

$category_title = $content['titles'][0]['default'] ?? '';
$description    = $content['descriptions'][0]['default'] ?? '';
$button         = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:group {"metadata":{"name":"Featured Category Cover Image"}, "align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))">
	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:cover {"url":"<?php echo esc_url( $image1 ); ?>","dimRatio":50,"focalPoint":{"x":0.5,"y":1},"minHeight":600,"align":"full","style":{"spacing":{"padding":{"top":"3%","right":"0%","bottom":"10%","left":"5%"},"margin":{"top":"0","bottom":"0"},"blockGap":"1%"}},"layout":{"type":"default"}} -->
	<div class="wp-block-cover alignfull" style="margin-top:0;margin-bottom:0;padding-top:3%;padding-right:0%;padding-bottom:10%;padding-left:5%;min-height:600px">
		<span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span>
		<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $image1 ); ?>" style="object-position:50% 100%" data-object-fit="cover" data-object-position="50% 100%"/>
		<div class="wp-block-cover__inner-container">
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php echo esc_html( $category_title ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)">
				<!-- wp:button -->
				<div class="wp-block-button">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link wp-element-button">
						<?php echo esc_html( $button ); ?>
					</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
	</div>
	<!-- /wp:cover -->

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
