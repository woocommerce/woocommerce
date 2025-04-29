<?php
/**
 * Title: Coming Soon Image Gallery
 * Slug: woocommerce/page-coming-soon-image-gallery
 * Categories: WooCommerce
 * Template Types: coming-soon
 * Inserter: false
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

$fonts               = ComingSoonTemplate::get_font_families();
$heading_font_family = $fonts['heading'];
$body_font_family    = $fonts['body'];

$featured_image_urls = array(
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-1.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-2.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-3.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-4.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-5.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-6.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-7.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-8.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-9.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-10.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-11.jpg' ),
	PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/gallery-12.jpg' ),
);
?>

<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-image-gallery","className":"woocommerce-coming-soon-image-gallery"} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-image-gallery">
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"right":"90px","left":"90px"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignwide" style="padding-right:90px;padding-left:90px">
	<!-- wp:group {"align":"wide","className":"woocommerce-coming-soon-header has-background","style":{"spacing":{"padding":{"bottom":"14px","left":"0px","right":"0px","top":"26px"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide woocommerce-coming-soon-header has-background" style="padding-top:26px;padding-right:0px;padding-bottom:14px;padding-left:0px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
			<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
				<div class="wp-block-group"><!-- wp:site-logo {"width":60} /-->

					<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
					<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"20px","letterSpacing":"0px"},"color":{"text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}},"fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
					<!-- /wp:group --></div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"woocommerce-coming-soon-social-login","style":{"spacing":{"blockGap":"48px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
				<div class="wp-block-group woocommerce-coming-soon-social-login"><!-- wp:template-part {"slug":"coming-soon-social-links","theme":"woocommerce/woocommerce","tagName":"div"} /-->

					<!-- wp:loginout {"style":{"elements":{"link":{"color":{"text":"#ffffff"}}},"color":{"background":"#000000"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"16px","right":"16px"}},"typography":{"fontSize":"14px","lineHeight":"1.2"},"border":{"radius":"6px"}}, "fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
				<!-- /wp:group --></div>
			<!-- /wp:group --></div>
		<!-- /wp:group -->

		<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
		<div class="wp-block-group alignwide">
			<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group alignwide">
				<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"48px","lineHeight":"1.3","fontStyle":"normal","fontWeight":"400"},"spacing":{"padding":{"top":"100px","bottom":"100px"}}},"fontFamily":"<?php echo esc_html( $heading_font_family ); ?>"} -->
					<h1 class="wp-block-heading has-<?php echo esc_html( $heading_font_family ); ?>-font-family" style="padding-top:100px;padding-bottom:100px;font-size:48px;font-style:normal;font-weight:400;line-height:1.3"><em><?php echo esc_html__( 'Great things are coming soon', 'woocommerce' ); ?></em></h1>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"},"tagName":"main"} -->
	<main class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">


		<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"bottom":"100px"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group alignfull" style="padding-bottom:100px">
			<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|40"},"margin":{"top":"0","bottom":"0"}}}} -->
			<div class="wp-block-columns alignwide" style="margin-top:0;margin-bottom:0">
				<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
				<div class="wp-block-column">
					<?php if ( isset( $featured_image_urls[0] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[0] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[4] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[4] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[8] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[8] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"style":{"spacing":{"blockGap":"0","padding":{"top":"0"}}}} -->
				<div class="wp-block-column" style="padding-top:0">
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->

					<?php if ( isset( $featured_image_urls[1] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[1] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[5] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[5] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[9] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[9] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
				<div class="wp-block-column">
					<?php if ( isset( $featured_image_urls[2] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[2] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[6] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[6] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[10] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[10] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
				<div class="wp-block-column">
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php if ( isset( $featured_image_urls[3] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[3] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[7] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[7] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
					<?php if ( isset( $featured_image_urls[11] ) ) : ?>
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center","style":{"border":{"radius":"14px"}}} -->
					<figure class="wp-block-image aligncenter size-full has-custom-border"><img src="<?php echo esc_url( $featured_image_urls[11] ); ?>" alt="" style="border-radius:14px"/></figure>
					<!-- /wp:image -->
					<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
					<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer">
					</div>
					<!-- /wp:spacer -->
					<?php endif ?>
				</div>
				<!-- /wp:column -->


			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->

	</main>
	<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:woocommerce/coming-soon -->
