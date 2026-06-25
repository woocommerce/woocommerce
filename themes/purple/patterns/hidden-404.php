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

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<p class="has-text-align-center" style="margin-top:var(--wp--preset--spacing--30)"><?php echo esc_html_x( 'The page you\'re looking for could not be found. Maybe try a search?', '404 error message', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pattern {"slug":"purple/hidden-search"} /-->
</div>
<!-- /wp:group -->
