<?php
/**
 * Title: Banner
 * Slug: woocommerce-blocks/banner
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$banner_title       = $content['titles'][0]['default'] ?? '';
$banner_button      = $content['buttons'][0]['default'] ?? '';
$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
?>

<!-- wp:columns {"verticalAlignment":null,"align":"wide","backgroundColor":"contrast"} -->
<div class="wp-block-columns alignwide has-contrast-background-color has-background">
	<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
		<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}, "textColor":"base"} -->
		<p class="has-base-color has-text-color" style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:18px"><strong><?php echo esc_html( $first_description ); ?></strong> </p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}, "textColor":"base"} -->
		<p class="has-base-color has-text-color" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:48px"><strong><?php echo esc_html( $banner_title ); ?></strong> </p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}, "textColor":"base"} -->
		<p class="has-base-color has-text-color" style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:24px"><?php echo esc_html( $second_description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"style":{"spacing":{"blockGap":"0","margin":{"top":"20px","bottom":"0"}}}} -->
		<div class="wp-block-buttons" style="margin-top:20px;margin-bottom:0">
			<!-- wp:button {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#000000","background":"#ffffff"},"border":{"width":"0px","style":"none"}},"className":"is-style-fill"} -->
			<div class="wp-block-button has-custom-font-size is-style-fill" style="font-size:16px">
				<a class="wp-block-button__link has-text-color has-background wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" style="border-style:none;border-width:0px;color:#000000;background-color:#ffffff">
					<?php echo esc_html( $banner_button ); ?>
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center"} -->
	<div class="wp-block-column is-vertically-aligned-center">
		<!-- wp:image {"id":1,"sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image size-full">
			<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/music-needle-turntable-black-and-white-white-photography.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a banner.', 'woo-gutenberg-products-block' ); ?>" class="wp-image-1" />
		</figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
