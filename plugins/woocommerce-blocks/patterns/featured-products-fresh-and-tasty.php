<?php
/**
 * Title: Featured Products: Fresh & Tasty
 * Slug: woocommerce-blocks/featured-products-fresh-and-tasty
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternImages;
$images = PatternImages::get_pattern_images( 'woocommerce-blocks/featured-products-fresh-and-tasty' );

$image1 = PatternImages::get_image_url( $images, 0, 'images/pattern-placeholders/sweet-organic-lemons.png' );
$image2 = PatternImages::get_image_url( $images, 1, 'images/pattern-placeholders/fresh-organic-tomatoes.png' );
$image3 = PatternImages::get_image_url( $images, 2, 'images/pattern-placeholders/fresh-lettuce-washed.png' );
$image4 = PatternImages::get_image_url( $images, 3, 'images/pattern-placeholders/russet-organic-potatoes.png' );
?>

<!-- wp:heading {"level":3,"align":"wide"} -->
<h3 class="wp-block-heading alignwide"><?php esc_html_e( 'Fresh &amp; tasty goods', 'woo-gutenberg-products-block' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:columns {"verticalAlignment":null,"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column {"style":{"layout":{"type":"constrained"}} -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"full","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image alignfull size-full">
			<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 1 out of 4.', 'woo-gutenberg-products-block' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Sweet Organic Lemons', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $1.99', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"layout":{"type":"constrained"}} -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"full","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image alignfull size-full">
			<img src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 2 out of 4.', 'woo-gutenberg-products-block' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Fresh Organic Tomatoes', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $2.99', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"layout":{"type":"constrained"}} -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"full","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image alignfull size-full">
			<img src="<?php echo esc_url( $image3 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 3 out of 4.', 'woo-gutenberg-products-block' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Fresh Lettuce (Washed)', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $0.99', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"layout":{"type":"constrained"}} -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"full","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image alignfull size-full">
			<img src="<?php echo esc_url( $image4 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 4 out of 4.', 'woo-gutenberg-products-block' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Russet Organic Potatoes', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $1.49', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
