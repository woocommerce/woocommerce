<?php
/**
 * Title: Hero Product Chessboard
 * Slug: woocommerce-blocks/hero-product-chessboard
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/hero-product-chessboard' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/hero-product-chessboard' );

$image1 = PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/sweet-restaurant-celebration-food-chocolate-cupcake.png' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'images/pattern-placeholders/dish-meal-food-breakfast-dessert-eat.png' );
?>

<!-- wp:group {"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":{"top":"0","left":"0"}}}} -->
	<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:cover {"url":"<?php echo esc_url( $image1 ); ?>","dimRatio":0,"focalPoint":{"x":0.54,"y":0.52},"isDark":false,"style":{"color":{}}} -->
			<div class="wp-block-cover is-light">
				<img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section. 1 out of 2.', 'woo-gutenberg-products-block' ); ?>" src="<?php echo esc_url( $image1 ); ?>" style="object-position:54% 52%" data-object-fit="cover" data-object-position="54% 52%"/>
				<div class="wp-block-cover__inner-container">
				</div>
			</div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"50px","right":"50px","bottom":"50px","left":"50px"}}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"stretch"}} -->
			<div class="wp-block-group" style="padding-top:50px;padding-right:50px;padding-bottom:50px;padding-left:50px">
				<!-- wp:heading {"textAlign":"left","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-left has-large-font-size" style="font-style:normal;font-weight:600"><?php echo esc_html( $content['titles'][0]['default'] ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}},"typography":{"fontSize":"14px"}}} -->
				<p style="margin-bottom:var(--wp--preset--spacing--40);font-size:14px"><?php echo esc_html( $content['descriptions'][0]['default'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"textAlign":"left","style":{"typography":{"fontSize":"16px"}}} -->
					<div class="wp-block-button has-custom-font-size" style="font-size:16px">
						<a class="wp-block-button__link has-text-align-left wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">Shop now</a>
					</div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":{"top":"0","left":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"50px","right":"50px","bottom":"50px","left":"50px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="padding-top:50px;padding-right:50px;padding-bottom:50px;padding-left:50px">
				<!-- wp:columns -->
				<div class="wp-block-columns">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize"><?php echo esc_html( $content['titles'][1]['default'] ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"var:preset|spacing|20","right":"0","bottom":"0","left":"0"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:12px"><?php echo esc_html( $content['descriptions'][1]['default'] ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize"><?php echo esc_html( $content['titles'][2]['default'] ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);font-size:12px"><?php echo esc_html( $content['descriptions'][2]['default'] ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->

				<!-- wp:columns -->
				<div class="wp-block-columns">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize"><?php echo esc_html( $content['titles'][3]['default'] ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);font-size:12px"><?php echo esc_html( $content['descriptions'][3]['default'] ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize"><?php echo esc_html( $content['titles'][4]['default'] ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20","right":"0","bottom":"0","left":"0"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);margin-right:0;margin-bottom:0;margin-left:0;font-size:12px"><?php echo esc_html( $content['descriptions'][4]['default'] ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:cover {"url":"<?php echo esc_url( $image2 ); ?>","dimRatio":0,"focalPoint":{"x":0.33,"y":0.06},"style":{"color":{}}} -->
			<div class="wp-block-cover">
				<img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section. 2 out of 2.', 'woo-gutenberg-products-block' ); ?>" src="<?php echo esc_url( $image2 ); ?>" style="object-position:33% 6%" data-object-fit="cover" data-object-position="33% 6%"/>
				<div class="wp-block-cover__inner-container">
				</div>
			</div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
