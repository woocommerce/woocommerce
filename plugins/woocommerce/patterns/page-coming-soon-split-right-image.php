<?php
/**
 * Title: Coming Soon Split Right Image
 * Slug: woocommerce/page-coming-soon-split-right-image
 * Categories: WooCommerce
 * Template Types: coming-soon
 * Inserter: false
 */

use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

$fonts                 = ComingSoonTemplate::get_font_families();
$heading_font_family   = $fonts['heading'];
$body_font_family      = $fonts['body'];
$paragraph_font_family = isset( $fonts['paragraph'] ) ? $fonts['paragraph'] : null;

$left_image  = plugins_url( 'assets/images/pattern-placeholders/wheel-leaf-bicycle-bike-vehicle-spoke.jpg', WC_PLUGIN_FILE );
$right_image = plugins_url( 'assets/images/pattern-placeholders/orange-wall-with-bicycle.jpg', WC_PLUGIN_FILE );

?>
<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-split-right-image","className":"woocommerce-coming-soon-split-right-image","style":{"color":{"background":"#f9f9f9","text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}}} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-split-right-image has-text-color has-background has-link-color" style="color:#000000;background-color:#f9f9f9">
	<!-- wp:group {"style":{"dimensions":{"minHeight":"100vh"},"spacing":{"padding":{"top":"0px","bottom":"0px"},"blockGap":"0px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group" style="min-height:100vh;padding-top:0px;padding-bottom:0px"><!-- wp:group {"style":{"dimensions":{"minHeight":"117px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
		<div class="wp-block-group" style="min-height:117px"><!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"18px","letterSpacing":"2.7px","fontStyle":"normal","fontWeight":"400"}},"fontFamily":"<?php echo esc_attr( $body_font_family ); ?>"} /--></div>
		<!-- /wp:group -->

		<!-- wp:columns {"className":"woocommerce-split-right-image-content","style":{"spacing":{"blockGap":{"top":"0px","left":"0px"},"margin":{"top":"0px","bottom":"0px"}},"layout":{"selfStretch":"fill","flexSize":null}}} -->
		<div class="wp-block-columns woocommerce-split-right-image-content" style="margin-top:0px;margin-bottom:0px"><!-- wp:column {"verticalAlignment":"center","layout":{"type":"default"}} -->
			<div class="wp-block-column is-vertically-aligned-center"><!-- wp:group {"layout":{"type":"constrained","contentSize":"259px"}} -->
				<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"14px","letterSpacing":"2.1px","fontStyle":"normal","fontWeight":"700","textTransform":"uppercase"}},"fontFamily":"<?php echo esc_attr( $body_font_family ); ?>"} -->
					<h1 class="wp-block-heading has-text-align-center has-<?php echo esc_attr( $body_font_family ); ?>-font-family" style="font-size:14px;font-style:normal;font-weight:700;letter-spacing:2.1px;text-transform:uppercase"><?php echo esc_html_x( 'opening soon', 'Used in the heading of the coming soon page', 'woocommerce' ); ?></h1>
					<!-- /wp:heading -->
					<!-- wp:group {"style":{"spacing":{"margin":{"top":"32px","bottom":"32px"}}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group" style="margin-top:32px;margin-bottom:32px">
						<!-- wp:image {"id":33,"width":"259px","height":"285px","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
						<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( $left_image ); ?>" alt="" class="wp-image-33" style="object-fit:cover;width:259px;height:285px"/></figure>
						<!-- /wp:image --></div>
					<!-- /wp:group -->

					<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","lineHeight":"1.6","letterSpacing":"-0.13px","fontStyle":"normal","fontWeight":"400"},"color":{"text":"#2f2f2f"},"elements":{"link":{"color":{"text":"#2f2f2f"}}},"spacing":{"margin":{"top":"0px","bottom":"0px"}}}} -->
					<p class="has-text-align-center has-text-color has-link-color<?php echo $paragraph_font_family ? ' has-' . esc_attr( $paragraph_font_family ) . '-font-family' : ''; ?>" style="color:#2f2f2f;margin-top:0px;margin-bottom:0px;font-size:13px;font-style:normal;font-weight:400;letter-spacing:-0.13px;line-height:1.6"><?php echo esc_html_x( 'Dedicated to providing top-quality bikes, accessories, and expert advice for riders of all experience levels. Stay tuned.', 'Used in the paragraph of the coming soon page', 'woocommerce' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"0px"},"margin":{"top":"24px","bottom":"32px"},"blockGap":"0"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"constrained","justifyContent":"center"}} -->
					<div class="wp-block-group" style="margin-top:24px;margin-bottom:32px;padding-top:0px;padding-bottom:0px"><!-- wp:template-part {"slug":"coming-soon-social-links","theme":"woocommerce/woocommerce","tagName":"div","align":"center","className":"is-size-fit-content"} /--></div>
					<!-- /wp:group --></div>
				<!-- /wp:group --></div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"stretch","layout":{"type":"default"}} -->
			<div class="wp-block-column is-vertically-aligned-stretch"><!-- wp:cover {"url":"<?php echo esc_url( $right_image ); ?>","id":34,"dimRatio":0,"customOverlayColor":"#cd7550","isUserOverlayColor":false,"focalPoint":{"x":0.5,"y":0.8},"minHeight":100,"minHeightUnit":"%","isDark":false,"className":"woocommerce-split-right-image-cover","style":{"elements":{"link":{"color":{"text":"inherit"}}},"color":{"text":"inherit"},"spacing":{"padding":{"top":"126px","bottom":"126px","left":"76px","right":"75px"}}},"layout":{"type":"default"}} -->
				<div class="wp-block-cover has-text-color has-link-color is-light woocommerce-split-right-image-cover" style="padding-top:126px;padding-right:75px;padding-bottom:126px;padding-left:76px;min-height:100%;color:inherit"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:#cd7550"></span><img class="wp-block-cover__image-background wp-image-34" alt="" src="<?php echo esc_url( $right_image ); ?>" style="object-position:50% 80%" data-object-fit="cover" data-object-position="50% 80%"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","style":{"typography":{"fontSize":"75px","fontStyle":"normal","fontWeight":"400","lineHeight":"1.3"},"color":{"text":"#ffffff80"},"elements":{"link":{"color":{"text":"#ffffff80"}}}},"fontFamily":"<?php echo esc_attr( $heading_font_family ); ?>"} -->
					<p class="has-text-align-center has-text-color has-link-color has-<?php echo esc_attr( $heading_font_family ); ?>-font-family" style="color:#ffffff80;font-size:75px;font-style:normal;font-weight:400;line-height:1.3"><em><?php echo esc_html_x( 'Where cycling dreams take flight.', 'Used in the heading of the coming soon page', 'woocommerce' ); ?></em></p>
					<!-- /wp:paragraph --></div></div>
				<!-- /wp:cover --></div>
			<!-- /wp:column --></div>
		<!-- /wp:columns --></div>
	<!-- /wp:group -->
</div>
<!-- /wp:woocommerce/coming-soon -->
