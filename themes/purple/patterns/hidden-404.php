<?php
/**
 * Title: 404
 * Slug: woostarter/hidden-404
 * Inserter: no
 * 
 */
?>

<!-- wp:group {"metadata":{"name":"Page not found"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":1,"align":"wide"} -->
<h1 class="wp-block-heading alignwide has-text-align-center" id="page-not-found"><?php echo esc_html_x( 'Page not found', 'Heading for a web page that is not found', 'woostarter' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"theme-4"} -->
<p class="has-text-align-center has-theme-4-color has-text-color"><?php echo esc_html_x( 'The page you\'re looking for could not be found. Maybe try a search?', '404 error message', 'woostarter' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search...","buttonText":"Search","buttonUseIcon":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"borderColor":"theme-4"} /-->

<!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->