<?php
/**
 * Title: Product Collections Featured Collections
 * Slug: woocommerce-blocks/product-collections-featured-collections
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/technology-white-camera-photography-vintage-photographer.png' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'images/pattern-placeholders/leather-guitar-typewriter-red-gadget-sofa.png' );
$image3 = PatternsHelper::get_image_url( $images, 2, 'images/pattern-placeholders/music-technology-play-equipment-studio-gadget.png' );
$image4 = PatternsHelper::get_image_url( $images, 3, 'images/pattern-placeholders/technology-joystick-gadget-console-games-playstation.png' );

$first_title   = $content['titles'][0]['default'] ?? '';
$second_title  = $content['titles'][1]['default'] ?? '';
$first_button  = $content['buttons'][0]['default'] ?? '';
$second_button = $content['buttons'][1]['default'] ?? '';
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column {"width":"50%","style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"color":{"background":"#f3edd8"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-column has-background" style="background-color:#f3edd8;padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px;flex-basis:50%">
		<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"},"color":{"text":"#000000"}},"fontSize":"x-large"} -->
		<h2 class="wp-block-heading has-text-color has-x-large-font-size" style="color:#000000;font-style:normal;font-weight:700"><?php echo esc_html( $first_title ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"style":{"spacing":{"padding":{"left":"18px","right":"18px","top":"9px","bottom":"9px"}},"typography":{"fontSize":"16px"},"color":{"background":"#000000","text":"#ffffff"}}} -->
			<div class="wp-block-button has-custom-font-size" style="font-size:16px">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#ffffff;background-color:#000000;padding-top:9px;padding-right:18px;padding-bottom:9px;padding-left:18px"><?php echo esc_html( $first_button ); ?></a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group">
			<!-- wp:image {"width":140,"sizeSlug":"full","linkDestination":"none"} -->
			<figure class="wp-block-image size-full is-resized">
				<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a left side of a banner. 1 out of 2.', 'woo-gutenberg-products-block' ); ?>" width="140" />
			</figure>
			<!-- /wp:image -->

			<!-- wp:image {"width":140,"height":100,"sizeSlug":"full","linkDestination":"none"} -->
			<figure class="wp-block-image size-full is-resized">
				<img src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a left side of a banner. 2 out of 2.', 'woo-gutenberg-products-block' ); ?>" width="140" height="100" />
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"50%","style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"color":{"background":"#d8f2f3"}}} -->
	<div class="wp-block-column has-background" style="background-color:#d8f2f3;padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px;flex-basis:50%">
		<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"},"color":{"text":"#000000"}},"fontSize":"x-large"} -->
		<h2 class="wp-block-heading has-text-color has-x-large-font-size" style="color:#000000;font-style:normal;font-weight:700"><?php echo esc_html( $second_title ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"style":{"spacing":{"padding":{"left":"18px","right":"18px","top":"9px","bottom":"9px"}},"typography":{"fontSize":"16px"},"color":{"background":"#000000","text":"#ffffff"}}} -->
			<div class="wp-block-button has-custom-font-size" style="font-size:16px">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#ffffff;background-color:#000000;padding-top:9px;padding-right:18px;padding-bottom:9px;padding-left:18px"><?php echo esc_html( $second_button ); ?></a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group">
			<!-- wp:image {"width":140,"height":100,"sizeSlug":"full","linkDestination":"none"} -->
			<figure class="wp-block-image size-full is-resized">
				<img src="<?php echo esc_url( $image3 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a right side of a banner. 1 out of 2.', 'woo-gutenberg-products-block' ); ?>" width="140" height="100" />
			</figure>
			<!-- /wp:image -->

			<!-- wp:image {"width":140,"height":100,"sizeSlug":"full","linkDestination":"none"} -->
			<figure class="wp-block-image size-full is-resized">
				<img src="<?php echo esc_url( $image4 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a right side of a banner. 2 out of 2.', 'woo-gutenberg-products-block' ); ?>" width="140" height="100" />
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
