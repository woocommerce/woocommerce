<?php
/**
 * Title: Hero Product 3 Split
 * Slug: woocommerce-blocks/hero-product-3-split
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/hero-product-3-split' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/hero-product-3-split' );

$first_title  = $content['titles'][0]['default'] ?? '';
$second_title = $content['titles'][1]['default'] ?? '';
$third_title  = $content['titles'][2]['default'] ?? '';
$fourth_title = $content['titles'][3]['default'] ?? '';
$fifth_title  = $content['titles'][4]['default'] ?? '';

$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
$third_description  = $content['descriptions'][2]['default'] ?? '';
$fourth_description = $content['descriptions'][3]['default'] ?? '';
$fifth_description  = $content['descriptions'][4]['default'] ?? '';
?>

<!-- wp:columns {"align":"full","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
<div class="wp-block-columns alignfull" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column {"width":"66.66%"} -->
	<div class="wp-block-column" style="flex-basis:66.66%">
		<!-- wp:media-text {"mediaPosition":"right","mediaId":1,"mediaLink":"<?php echo esc_url( plugins_url( 'images/pattern-placeholders/hand-guitar-finger-tshirt-clothing-rack.png', dirname( __FILE__ ) ) ); ?>","mediaType":"image"} -->
		<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile">
			<div class="wp-block-media-text__content">
				<!-- wp:group {"style":{"spacing":{"margin":{"top":"20px","bottom":"20px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group" style="margin-top:20px;margin-bottom:20px;">
					<!-- wp:heading -->
					<h2 class="wp-block-heading"><?php echo esc_html( $first_title ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $first_description ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"textAlign":"left"} -->
						<div class="wp-block-button has-custom-font-size">
							<a class="wp-block-button__link has-text-align-left wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_attr_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a>
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

	<!-- wp:column {"verticalAlignment":"center","width":"30%","style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-column is-vertically-aligned-center" style="padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30);flex-basis:30%">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $second_title ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $second_description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $third_title ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $third_description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $fourth_title ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $fourth_description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
		<!-- /wp:separator -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $fifth_title ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $fifth_description ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
