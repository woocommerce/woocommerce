<?php
/**
 * Title: Two columns highlights sections
 * Slug: purple/two-columns-highlights-sections
 * Categories: about, columns, media, featured
 * Keywords: about, columns, media, featured
 * Description: A section with 2 columns of highlights
 */
?>
<!-- wp:group {"metadata":{"name":"Two columns highlights sections "},"align":"full","className":"is-style-section-1","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-1" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'purple');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"contentPosition":"bottom left","sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"left","placeholder":"Write title…","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.1em"}}} -->
<p class="has-text-align-left" style="letter-spacing:0.1em;text-transform:uppercase"><?php esc_html_e('Our Collections', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('The Cashmere Collection', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-button-1"} -->
<div class="wp-block-button is-style-button-1"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Learn more', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'purple');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"contentPosition":"bottom left","sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"left","placeholder":"Write title…","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.1em"}}} -->
<p class="has-text-align-left" style="letter-spacing:0.1em;text-transform:uppercase"><?php esc_html_e('our Flagship Store', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Exclusive pop-ups', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-button-1"} -->
<div class="wp-block-button is-style-button-1"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Learn more', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->