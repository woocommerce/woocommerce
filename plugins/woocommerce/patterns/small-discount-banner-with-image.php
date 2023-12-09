<?php
/**
 * Title: Small Discount Banner with Image
 * Slug: woocommerce-blocks/small-discount-banner-with-image
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$banner_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:columns {"style":{"color":{"background":"#fcf8e1"},"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
<div class="wp-block-columns has-background" style="background-color:#fcf8e1;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
	<!-- wp:column {"width":"45%"} -->
	<div class="wp-block-column" style="flex-basis:45%">
		<!-- wp:group {"style":{"spacing":{"margin":{"top":"0"},"padding":{"left":"25px","top":"25px"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="margin-top:0;padding-top:25px;padding-left:25px">
			<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","lineHeight":"1","fontSize":"30px"}}} -->
			<p style="font-size:30px;font-style:normal;font-weight:300;line-height:1"><em><?php echo esc_html( $banner_title ); ?></em></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","lineHeight":"0","fontSize":"30px"},"color":{"text":"#74227b"},"elements":{"link":{"color":{"text":"#74227b"}}}}} -->
			<p class="has-text-color has-link-color" style="color:#74227b;font-size:30px;font-style:normal;font-weight:300;line-height:0"><em><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">from</a></em></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"color":{"text":"#74227b"},"typography":{"fontStyle":"normal","fontWeight":"900","fontSize":"52px","lineHeight":"1.2"},"elements":{"link":{"color":{"text":"#74227b"}}}}} -->
			<p class="has-text-color has-link-color" style="color:#74227b;font-size:52px;font-style:normal;font-weight:900;line-height:1.2"><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">$149</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:image {"align":"center","id":1,"sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image aligncenter size-full">
			<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/table-floor-interior-atmosphere-living-room-furniture-square-lg.png' ) ); ?>" alt="" class="wp-image-1" />
		</figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
