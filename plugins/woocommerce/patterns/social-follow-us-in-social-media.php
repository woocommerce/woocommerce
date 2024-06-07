<?php
/**
 * Title: Social: Follow us on social media
 * Slug: woocommerce-blocks/social-follow-us-in-social-media
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'assets/images/pattern-placeholders/watch-hand-brand-jewellery-strap-platinum.jpg' );
$image3 = PatternsHelper::get_image_url( $images, 2, 'assets/images/pattern-placeholders/tree-branch-plant-wood-leaf-flower.jpg' );
$image4 = PatternsHelper::get_image_url( $images, 3, 'assets/images/pattern-placeholders/road-sport-vintage-wheel-retro-old.jpg' );

$social_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"0px","bottom":"80px"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:0px;margin-bottom:80px;padding-top:0;padding-right:var(--wp--preset--spacing--30);padding-bottom:0;padding-left:var(--wp--preset--spacing--30)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":3,"align":"wide"} -->
		<h3 class="wp-block-heading alignwide"><?php echo esc_html( $social_title ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:social-links {"iconColor":"primary","openInNewTab":true,"style":{"spacing":{"blockGap":{"top":"0","left":"16px"}}},"className":"has-icon-color is-style-logos-only","layout":{"type":"flex","justifyContent":"space-between","orientation":"horizontal"}} -->
		<ul class="wp-block-social-links has-icon-color is-style-logos-only">
			<!-- wp:social-link {"url":"https://x.com","service":"x"} /-->

			<!-- wp:social-link {"url":"https://www.instagram.com/","service":"instagram"} /-->

			<!-- wp:social-link {"url":"https://www.facebook.com/","service":"facebook"} /-->

			<!-- wp:social-link {"url":"https://www.twitch.tv/","service":"twitch"} /-->
		</ul>
		<!-- /wp:social-links -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image is-resized size-large">
				<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased under the social media icons. 1 out of 4.', 'woocommerce' ); ?>" style="aspect-ratio:1;object-fit:cover;"/>
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image is-resized size-large">
				<img src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased under the social media icons. 2 out of 4.', 'woocommerce' ); ?>" style="aspect-ratio:1;object-fit:cover;"/>
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image is-resized size-large">
				<img src="<?php echo esc_url( $image3 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased under the social media icons. 3 out of 4.', 'woocommerce' ); ?>" style="aspect-ratio:1;object-fit:cover;"/>
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image is-resized size-large">
				<img src="<?php echo esc_url( $image4 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased under the social media icons. 4 out of 4.', 'woocommerce' ); ?>" style="aspect-ratio:1;object-fit:cover;"/>
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
