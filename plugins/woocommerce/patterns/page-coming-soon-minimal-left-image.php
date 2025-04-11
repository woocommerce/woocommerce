<?php
/**
 * Title: Coming Soon Minimal Left Image
 * Slug: woocommerce/page-coming-soon-minimal-left-image
 * Categories: WooCommerce
 * Template Types: coming-soon
 * Inserter: false
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

$fonts                 = ComingSoonTemplate::get_font_families();
$heading_font_family   = $fonts['heading'];
$body_font_family      = $fonts['body'];
$paragraph_font_family = isset( $fonts['paragraph'] ) ? $fonts['paragraph'] : null;

$default_image = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/green-glass-jars-on-stairs.jpg' );

$site_tagline = get_bloginfo( 'description' );

// If the site tagline is empty, use a default copy. Otherwise, use the site tagline.
$store_description = ! empty( $site_tagline )
	? $site_tagline
	: sprintf(
		/* translators: %s: Site name. */
		__( '%s transforms your home with our curated collection of home decor, bringing inspiration and style to every corner.', 'woocommerce' ),
		get_bloginfo( 'name' )
	);

?>
<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-minimal-left-image","className":"woocommerce-coming-soon-default woocommerce-coming-soon-minimal-left-image","style":{"color":{"background":"#f9f9f9","text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}}} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-default woocommerce-coming-soon-minimal-left-image has-text-color has-background has-link-color" style="color:#000000;background-color:#f9f9f9"><!-- wp:cover {"customOverlayColor":"transparent","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","className":"coming-soon-is-vertically-aligned-center coming-soon-cover","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"24px","right":"24px"}},"color":{"text":"inherit"},"elements":{"link":{"color":{"text":"inherit"}}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover coming-soon-is-vertically-aligned-center coming-soon-cover has-text-color has-link-color" style="color:inherit;padding-top:0px;padding-right:24px;padding-bottom:0px;padding-left:24px;min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:transparent"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"full","style":{"dimensions":{"minHeight":"100vh"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="min-height:100vh"><!-- wp:group {"align":"wide","className":"woocommerce-coming-soon-header has-background","style":{"spacing":{"padding":{"top":"26px","bottom":"14px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide woocommerce-coming-soon-header has-background" style="padding-top:26px;padding-bottom:14px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":60} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"20px","letterSpacing":"0px"},"color":{"text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}},"fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"woocommerce-coming-soon-social-login","style":{"spacing":{"blockGap":"48px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group woocommerce-coming-soon-social-login"><!-- wp:template-part {"slug":"coming-soon-social-links","theme":"woocommerce/woocommerce","tagName":"div"} /-->

<!-- wp:loginout {"style":{"elements":{"link":{"color":{"text":"#000000"}}},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"16px","right":"16px"}},"typography":{"fontSize":"14px","lineHeight":"1.2"},"border":{"radius":"6px","color":"#000000","width":"1px"}},"fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"woocommerce-coming-soon-minimal-left-image__content","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"120px","bottom":"120px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull woocommerce-coming-soon-minimal-left-image__content" style="margin-top:120px;margin-bottom:120px;padding-top:0;padding-bottom:0"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:columns {"className":"alignfull","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":{"top":"0","left":"60px"},"margin":{"top":"0px","bottom":"0px"}},"layout":{"selfStretch":"fit","flexSize":null}}} -->
<div class="wp-block-columns alignfull" style="margin-top:0px;margin-bottom:0px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"bottom","width":"481px","className":"woocommerce-coming-soon-minimal-left-image__content-image","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-bottom woocommerce-coming-soon-minimal-left-image__content-image" style="flex-basis:481px"><!-- wp:image {"aspectRatio":"481/576","scale":"cover","style":{"border":{"radius":"16px"}}} -->
<figure class="wp-block-image has-custom-border"><img src="<?php echo esc_url( $default_image ); ?>" alt="Decorative Image" style="border-radius:16px;aspect-ratio:481/576;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"stretch","width":"453px","className":"woocommerce-coming-soon-minimal-left-image__content-text","style":{"spacing":{"blockGap":"0","padding":{"right":"0","left":"0","bottom":"0","top":"53px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-stretch woocommerce-coming-soon-minimal-left-image__content-text" style="padding-top:53px;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:453px"><!-- wp:group {"style":{"dimensions":{"minHeight":"100%"},"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","flexWrap":"nowrap","verticalAlignment":"space-between"}} -->
<div class="wp-block-group" style="min-height:100%"><!-- wp:heading {"level":1,"className":"is-style-default","style":{"typography":{"fontSize":"38px","lineHeight":"1.19"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"fontFamily":"<?php echo esc_html( $heading_font_family ); ?>"} -->
<h1 class="wp-block-heading is-style-default has-<?php echo esc_html( $heading_font_family ); ?>-font-family" style="margin-bottom:var(--wp--preset--spacing--30);font-size:38px;line-height:1.19"><?php echo esc_html__( 'Something big is brewing! Our store is in the works – Launching shortly!', 'woocommerce' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left","contentSize":"338px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"has-<?php echo esc_html( $paragraph_font_family ?? '' ); ?>-font-family","style":{"typography":{"lineHeight":"1.6","letterSpacing":"0px"}},"fontFamily":"<?php echo esc_html( $paragraph_font_family ?? '' ); ?>"} -->
<p class="has-<?php echo esc_html( $paragraph_font_family ?? '' ); ?>-font-family" style="letter-spacing:0px;line-height:1.6"><?php echo esc_html( $store_description ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:woocommerce/coming-soon -->
