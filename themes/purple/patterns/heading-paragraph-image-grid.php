<?php
/**
 * Title: Heading, paragraph & image grid
 * Slug: woostarter/heading-paragraph-image-grid
 * Categories: 
 */
?>
<!-- wp:group {"metadata":{"name":"Heading, paragraph \u0026 image grid"},"align":"full","className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php esc_html_e('Follow us', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('And use the hashtag #example to share your purchases.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-japanese-artisanal-bowls.webp" alt="<?php esc_attr_e('A collection of handcrafted Japanese ceramic bowls with varied textures and glazes on a dark background.', 'woostarter');?>" class="" style="aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-necklace-closeup.webp" alt="<?php esc_attr_e('A double-strand turquoise beaded necklace with silver accents.', 'woostarter');?>" class="" style="aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-lamp-closeup.webp" alt="<?php esc_attr_e('A close-up of an old metal street lamp with a greenish interior, glowing against a gray background.', 'woostarter');?>" class="" style="aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-ceramic-cup.webp" alt="<?php esc_attr_e('A glossy, hand-crafted brown ceramic cup with an organic shape.', 'woostarter');?>" class="" style="aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->