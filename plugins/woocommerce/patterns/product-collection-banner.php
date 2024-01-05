<?php
/**
 * Title: Product Collection Banner
 * Slug: woocommerce-blocks/product-collection-banner
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$image = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/beach-landscape-sea-coast-nature-person.jpg' );

$first_title       = $content['titles'][0]['default'] ?? '';
$first_description = $content['descriptions'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:columns {"verticalAlignment":null,"align":"wide","style":{"color":{"background":"#ffedf5"}}} -->
	<div class="wp-block-columns alignwide has-background" style="background-color:#ffedf5">
		<!-- wp:column {"width":"58%"} -->
		<div class="wp-block-column" style="flex-basis:58%">
			<!-- wp:cover {"url":"<?php echo esc_url( $image ); ?>","dimRatio":20,"minHeight":430,"minHeightUnit":"px","isDark":false,"style":{"spacing":{"padding":{"top":"80px","right":"80px","bottom":"80px","left":"80px"}}}} -->
			<div class="wp-block-cover is-light" style="padding-top:80px;padding-right:80px;padding-bottom:80px;padding-left:80px;min-height:430px">
				<span aria-hidden="true" class="wp-block-cover__background has-background-dim-20 has-background-dim"></span>
				<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $image ); ?>" data-object-fit="cover" />
				<div class="wp-block-cover__inner-container">
					<!-- wp:paragraph {"align":"center","placeholder":"Write title…","style":{"typography":{"fontSize":"40px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#ffffff"}},"fontFamily":"inter"} -->
					<p class="has-text-align-center has-text-color has-inter-font-family" style="color:#ffffff;font-size:40px;font-style:normal;font-weight:700;line-height:1.3;text-transform:uppercase"><?php echo esc_html( $first_title ); ?></p>
					<!-- /wp:paragraph -->
				</div>
			</div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"22px","fontStyle":"normal","fontWeight":"300","lineHeight":"1.3"},"color":{"text":"#000000"}},"fontFamily":"inter"} -->
			<p class="has-text-color has-inter-font-family" style="color:#000000;font-size:22px;font-style:normal;font-weight:300;line-height:1.3"><?php echo esc_html( $first_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"style":{"color":{"background":"#ff7179","text":"#000000"},"border":{"radius":"100px","width":"0px","style":"none"}}} -->
				<div class="wp-block-button">
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="border-style:none;border-width:0px;border-radius:100px;color:#000000;background-color:#ff7179">Shop now</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
