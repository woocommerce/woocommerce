<?php
/**
 * Title: Hero Product Chessboard
 * Slug: woocommerce-blocks/hero-product-chessboard
 * Categories: WooCommerce
 */
?>

<!-- wp:group {"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":{"top":"0","left":"0"}}}} -->
	<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:cover {"url":"<?php echo esc_url( plugins_url( 'images/pattern-placeholders/person-winter-blur-wood-girl-woman.png', dirname( __FILE__ ) ) ); ?>","dimRatio":0,"focalPoint":{"x":0.54,"y":0.52},"isDark":false,"style":{"color":{}}} -->
			<div class="wp-block-cover is-light">
				<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/person-winter-blur-wood-girl-woman.png', dirname( __FILE__ ) ) ); ?>" style="object-position:54% 52%" data-object-fit="cover" data-object-position="54% 52%"/>
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
				<h3 class="wp-block-heading has-text-align-left has-large-font-size" style="font-style:normal;font-weight:600">Unisex Leather Winter Jacket</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}},"typography":{"fontSize":"14px"}}} -->
				<p style="margin-bottom:var(--wp--preset--spacing--40);font-size:14px">With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.</p>
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
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize">Quality Materials</h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"var:preset|spacing|20","right":"0","bottom":"0","left":"0"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:12px">We use only the highest-quality materials in our products, ensuring that they look great and last for years to come. </p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize">Expert Craftsmanship</h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);font-size:12px">Our products are made with expert craftsmanship and attention to detail, ensuring that every stitch and seam is perfect.</p>
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
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize">Unique Design</h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);font-size:12px">From bold prints and colors to intricate details and textures, our products are a perfect combination of style and function.</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize","fontSize":"14px"}}} -->
						<h5 class="wp-block-heading" style="font-size:14px;text-transform:capitalize">Customer Satisfaction</h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"},"spacing":{"margin":{"top":"var:preset|spacing|20","right":"0","bottom":"0","left":"0"}}}} -->
						<p style="margin-top:var(--wp--preset--spacing--20);margin-right:0;margin-bottom:0;margin-left:0;font-size:12px">Our top priority is customer satisfaction, and we stand behind our products 100%. </p>
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
			<!-- wp:cover {"url":"<?php echo esc_url( plugins_url( 'images/pattern-placeholders/man-person-winter-photography-statue-coat.png', dirname( __FILE__ ) ) ); ?>","dimRatio":0,"focalPoint":{"x":0.33,"y":0.06},"style":{"color":{}}} -->
			<div class="wp-block-cover">
				<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/man-person-winter-photography-statue-coat.png', dirname( __FILE__ ) ) ); ?>" style="object-position:33% 6%" data-object-fit="cover" data-object-position="33% 6%"/>
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
