<?php
/**
 * Title: Heading, image and list of features
 * Slug: purple/about-features-2-columns
 * Categories: purple
 * Keywords: about, text, image, media
 * Description: A section with a heading, paragraph, a list of features with icons and an image.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"categories":["purple"],"name":"Heading, image and list of features","patternName":"purple/about-features-2-columns","description":"A section with a heading, paragraph, a list of features with icons and an image."},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"40%","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-bottom:var(--wp--preset--spacing--40);flex-basis:40%"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"1.12px"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="letter-spacing:1.12px;text-transform:uppercase"><?php esc_html_e('Sustainability', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Our commitment', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Clothing is important to us, but so is our planet. That\'s why we abide by sustainable manufacturing and business practices.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="<?php esc_attr_e('Check icon', 'purple');?>" style="width:24px;height:auto"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We use regenerate materials in the manufacturing and shipping process', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="<?php esc_attr_e('Check icon', 'purple');?>" style="width:24px;height:auto"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We make an effort to leave no materials behind', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"24px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-check.webp" alt="<?php esc_attr_e('Check icon', 'purple');?>" style="width:24px;height:auto"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We make clothes that will last a long time', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e('Placeholder image', 'purple');?>","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"style":{"dimensions":{"aspectRatio":"1"}}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
