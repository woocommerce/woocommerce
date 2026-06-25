<?php
/**
 * Title: 404
 * Slug: purple/hidden-404
 * Inserter: no
 */
?>

<!-- wp:group {"metadata":{"name":"Page not found"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|140","bottom":"var:preset|spacing|140"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--140);padding-bottom:var(--wp--preset--spacing--140)"><!-- wp:heading {"textAlign":"center","level":1,"align":"wide"} -->
<h1 class="wp-block-heading alignwide has-text-align-center" id="page-not-found"><?php echo esc_html_x( 'Page not found', 'Heading for a web page that is not found', 'purple' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html_x( 'The page you\'re looking for could not be found. Maybe try a search?', '404 error message', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_html_x( 'Search', 'Search form label.', 'purple' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr_x( 'Search...', 'Search input field placeholder text.', 'purple' ); ?>","buttonText":"<?php echo esc_attr_x( 'Search', 'Button text. Verb.', 'purple' ); ?>","buttonUseIcon":false,"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"borderColor":"theme-4"} /-->
</div>
<!-- /wp:group -->
