<?php
/**
 * Title: Featured Products: Fresh & Tasty
 * Slug: woocommerce-blocks/featured-products-fresh-and-tasty
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/sweet-organic-lemons.png' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'assets/images/pattern-placeholders/fresh-organic-tomatoes.png' );
$image3 = PatternsHelper::get_image_url( $images, 2, 'assets/images/pattern-placeholders/fresh-lettuce-washed.png' );
$image4 = PatternsHelper::get_image_url( $images, 3, 'assets/images/pattern-placeholders/russet-organic-potatoes.png' );

$first_title        = $content['titles'][0]['default'] ?? '';
$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
$third_description  = $content['descriptions'][2]['default'] ?? '';
$fourth_description = $content['descriptions'][3]['default'] ?? '';
?>

<!-- wp:heading {"level":3,"align":"wide"} -->
<h3 class="wp-block-heading alignwide"><?php echo esc_html( $first_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:columns {"verticalAlignment":null,"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column {"style":{"layout":{"type":"constrained"}} -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"full","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image alignfull size-full">
			<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 1 out of 4.', 'woocommerce' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php echo esc_html( $first_description ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $1.99', 'woocommerce' ); ?></p>
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
			<img src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 2 out of 4.', 'woocommerce' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php echo esc_html( $second_description ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $2.99', 'woocommerce' ); ?></p>
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
			<img src="<?php echo esc_url( $image3 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 3 out of 4.', 'woocommerce' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php echo esc_html( $third_description ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $0.99', 'woocommerce' ); ?></p>
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
			<img src="<?php echo esc_url( $image4 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in the featured products pattern. 4 out of 4.', 'woocommerce' ); ?>" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0"},"margin":{"top":"5px","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:5px;margin-bottom:0">
			<!-- wp:column {"width":"67%","style":{"typography":{"fontWeight":"600"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
			<div class="wp-block-column" style="font-weight:600;flex-basis:67%">
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php echo esc_html( $fourth_description ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33%","layout":{"type":"constrained","justifyContent":"right"}} -->
			<div class="wp-block-column" style="flex-basis:33%">
				<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
				<p class="has-text-align-left has-small-font-size"><?php esc_html_e( 'from $1.49', 'woocommerce' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
