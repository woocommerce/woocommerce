<?php
/**
 * Title: Hero Product 3 Split
 * Slug: woocommerce-blocks/hero-product-3-split
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/hero-product-3-split' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/hero-product-3-split' );
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column {"width":"66.66%"} -->
	<div class="wp-block-column" style="flex-basis:66.66%">
		<!-- wp:media-text {"mediaPosition":"right","mediaId":1,"mediaLink":"<?php echo esc_url( plugins_url( 'images/pattern-placeholders/hand-guitar-finger-tshirt-clothing-rack.png', dirname( __FILE__ ) ) ); ?>","mediaType":"image","style":{"color":{"background":"#000000","text":"#ffffff"}}} -->
		<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile has-text-color has-background" style="color:#ffffff;background-color:#000000">
			<div class="wp-block-media-text__content">
				<!-- wp:group {"style":{"spacing":{"margin":{"top":"20px","bottom":"20px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group" style="margin-top:20px;margin-bottom:20px;">
					<!-- wp:heading -->
					<h2 class="wp-block-heading"><?php echo esc_html( $content['titles'][0]['default'] ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $content['descriptions'][0]['default'] ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"textAlign":"left","style":{"color":{"background":"#ffffff","text":"#000000"}}} -->
						<div class="wp-block-button has-custom-font-size">
							<a class="wp-block-button__link has-text-color has-background has-text-align-left wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" style="color:#000000;background-color:#ffffff"><?php esc_attr_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a>
						</div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:group -->
			</div>
			<figure class="wp-block-media-text__media">
				<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/hand-guitar-finger-tshirt-clothing-rack.png' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woo-gutenberg-products-block' ); ?>" class="wp-image-1 size-full" />
			</figure>
		</div>
		<!-- /wp:media-text -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center","width":"33.33%"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][1]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][1]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][2]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][2]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][3]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][3]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][4]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][4]['default'] ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
