<?php
/**
 * Title: Featured Category Cover Image
 * Slug: woocommerce-blocks/featured-category-cover-image
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/featured-category-cover-image' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/featured-category-cover-image' );

$image1 = PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/shop-jeans.png' );

$category_title = $content['titles'][0]['default'] ?? '';
$description    = $content['descriptions'][0]['default'] ?? '';
$button         = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:cover {"url":"<?php echo esc_url( $image1 ); ?>","dimRatio":50,"align":"full","style":{"spacing":{"padding":{"top":"3%","right":"0%","bottom":"10%","left":"5%"},"margin":{"top":"0","bottom":"80px"},"blockGap":"1%"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover alignfull" style="margin-top:0;margin-bottom:80px;padding-top:3%;padding-right:0%;padding-bottom:10%;padding-left:5%">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span>
	<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $image1 ); ?>" data-object-fit="cover"/>
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
