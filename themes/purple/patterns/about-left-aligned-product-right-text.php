<?php
/**
 * Title: Left-aligned product, text on the right
 * Slug: purple/left-aligned-product-right-text
 * Categories: text, about, media
 * Keywords: about, text, intro
 * Description: A section with a product image and a large sized paragraph.
 */
?>
<!-- wp:group {"metadata":{"categories":["text","about","media"],"name":"Left-aligned product, text on the right","description":"A section with a product image and a large sized paragraph."},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"0"},"dimensions":{"minHeight":""}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"},"padding":{"right":"0","left":"0","top":"0","bottom":"0"}}},"backgroundColor":"theme-5"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center has-theme-5-background-color has-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view1.webp","alt":"An illustration of of folded shirts","dimRatio":10,"isDark":false,"style":{"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e('An illustration of of folded shirts', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view1.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-10 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('New Collection', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"><?php esc_html_e('Knitted with breathable 100% wool fibers.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Shop now', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
