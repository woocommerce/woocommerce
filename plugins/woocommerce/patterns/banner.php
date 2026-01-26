<?php
/**
 * Title: Banner
 * Slug: woocommerce-blocks/banner
 * Categories: WooCommerce, featured-selling
 */


$banner_title       = __( 'Up to 60% off', 'woocommerce' );
$banner_button      = __( 'Shop vinyl records', 'woocommerce' );
$first_description  = __( 'Holiday Sale', 'woocommerce' );
$second_description = __( 'Get your favorite vinyl at record-breaking prices.', 'woocommerce' );
?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))">
	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
			<p style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:18px"><strong><?php echo esc_html( $first_description ); ?></strong> </p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"48px"}}} -->
			<h3 class="wp-block-heading" style="font-size:48px"><?php echo esc_html( $banner_title ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
			<p style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:24px"><?php echo esc_html( $second_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"blockGap":"0","margin":{"top":"20px","bottom":"0"}}}} -->
			<div class="wp-block-buttons" style="margin-top:20px;margin-bottom:0">
				<!-- wp:button {"style":{"typography":{"fontSize":"16px"},"border":{"width":"0px","style":"none"}},"className":"is-style-fill"} -->
				<div class="wp-block-button has-custom-font-size is-style-fill" style="font-size:16px">
					<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" style="border-style:none;border-width:0px;">
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
				<img src="<?php echo esc_url( plugins_url( 'assets/images/pattern-placeholders/music-needle-turntable-black-and-white-white-photography.jpg', WC_PLUGIN_FILE ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a banner.', 'woocommerce' ); ?>" class="wp-image-1" />
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
