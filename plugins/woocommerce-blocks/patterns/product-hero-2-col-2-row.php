<?php
/**
 * Title: Product Hero 2 Column 2 Row
 * Slug: woocommerce-blocks/product-hero-2-col-2-row
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/man-person-winter-photography-guy-statue.jpg' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'images/pattern-placeholders/pattern-fashion-clothing-outerwear-wool-scarf.png' );

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

$button = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group alignfull">
	<!-- wp:media-text {"align":"full","mediaType":"image","mediaId":1,"mediaLink":"<?php echo esc_url( $image1 ); ?>","mediaWidth":40} -->
	<div class="wp-block-media-text alignfull is-stacked-on-mobile" style="grid-template-columns:40% auto">
		<figure class="wp-block-media-text__media">
			<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woo-gutenberg-products-block' ); ?>" class="wp-image-1 size-full" />
		</figure>
		<div class="wp-block-media-text__content">
			<!-- wp:group {"layout":{"contentSize":"760px","type":"constrained"}} -->
			<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"top"} -->
				<div class="wp-block-columns are-vertically-aligned-top">
					<!-- wp:column {"verticalAlignment":"top","style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}}}} -->
					<div class="wp-block-column is-vertically-aligned-top" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
						<!-- wp:heading {"style":{"typography":{"fontSize":"58px","fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"0px"}}}} -->
						<h2 id="the-eden-jacket" style="margin-top:0px;font-size:58px;font-style:normal;font-weight:600"><?php echo esc_html( $first_title ); ?></h2>
						<!-- /wp:heading -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"top"} -->
					<div class="wp-block-column is-vertically-aligned-top">
						<!-- wp:group {"style":{"spacing":{"padding":{"top":"10px","right":"10px","bottom":"10px","left":"10px"},"blockGap":"30px"}}} -->
						<div class="wp-block-group" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">
							<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
							<p style="font-size:16px"><?php echo esc_html( $first_description ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons -->
							<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
								<div class="wp-block-button is-style-outline">
									<a class="wp-block-button__link wp-element-button"><?php echo esc_html( $button ); ?></a>
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
			</div>
			<!-- /wp:group -->
		</div>
	</div>
	<!-- /wp:media-text -->

	<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaId":1,"mediaLink":"<?php echo esc_url( $image2 ); ?>","mediaType":"image","mediaWidth":60} -->
	<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile" style="grid-template-columns:auto 60%">
		<div class="wp-block-media-text__content">
			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
				<div class="wp-block-column" style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
					<!-- wp:heading {"level":4,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1","fontSize":"22px"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
					<h4 id="100-silk" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:22px;font-style:normal;font-weight:600;line-height:1"><?php echo esc_html( $second_title ); ?></h4>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
					<p style="font-size:16px"><?php echo esc_html( $second_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
				<div class="wp-block-column" style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
					<!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1","fontSize":"22px"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
					<h5 id="100-silk" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:22px;font-style:normal;font-weight:600;line-height:1"><?php echo esc_html( $third_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
					<p style="font-size:16px"><?php echo esc_html( $third_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->

			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
				<div class="wp-block-column" style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
					<!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1","fontSize":"22px"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
					<h5 id="100-silk" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:22px;font-style:normal;font-weight:600;line-height:1"><?php echo esc_html( $fourth_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
					<p style="font-size:16px"><?php echo esc_html( $fourth_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
				<div class="wp-block-column" style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
					<!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1","fontSize":"22px"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
					<h5 id="100-silk" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:22px;font-style:normal;font-weight:600;line-height:1"><?php echo esc_html( $fifth_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
					<p style="font-size:16px"><?php echo esc_html( $fifth_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<figure class="wp-block-media-text__media">
			<img src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woo-gutenberg-products-block' ); ?>" class="wp-image-1 size-full" />
		</figure>
	</div>
	<!-- /wp:media-text -->
</div>
<!-- /wp:group -->
