<?php
/**
 * Title: About section with alternating columns
 * Slug: woostarter/about-alternating-columns
 * Categories: text, about, media
 * Keywords: about, text
 * Description: A section with alternating columns of text and an image.
 */
?>
<!-- wp:group {"metadata":{"categories":["intro"],"name":"About section with alternating columns","patternName":"woostarter/about-alternating-columns"},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0">

<!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"40%","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-bottom:var(--wp--preset--spacing--40);flex-basis:40%">

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-theme-2-color has-text-color has-link-color has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Wool', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Wool is a natural fiber harvested from sheep. It’s known for its durability, breathability, and excellent insulation properties. Wool fibers are thicker and more robust than cashmere, making them ideal for outerwear, suits, and knitwear designed for long-term wear and colder climates.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)">
	
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Strong and durable', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Temperature-regulating', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Absorbs moisture', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">
	
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero-image.webp","alt":"","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"style":{"dimensions":{"aspectRatio":"16/9"}}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero-image.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">
	
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero-image.webp","alt":"","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"style":{"dimensions":{"aspectRatio":"16/9"}}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero-image.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

</div>
<!-- /wp:column -->
 
<!-- wp:column {"verticalAlignment":"center","width":"40%","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-bottom:var(--wp--preset--spacing--40);flex-basis:40%">

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-theme-2-color has-text-color has-link-color has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Cashmere', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Cashmere is a natural fiber obtained from the undercoat of cashmere goats, primarily found in regions like Mongolia, Nepal, and northern China. Renowned for its exceptional softness, warmth, and lightweight feel, cashmere is significantly finer than sheep’s wool, which gives it a smooth, almost silky texture against the skin.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)">
	
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Exceptionally soft', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Warm and lightweight', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="Check icon" style="width:24px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Elegant and luxurious', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

</div>
<!-- /wp:group -->

