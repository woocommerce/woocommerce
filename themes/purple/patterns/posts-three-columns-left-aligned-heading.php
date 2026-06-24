<?php
/**
 * Title: List of posts with left aligned heading
 * Slug: purple/posts-three-columns-left-aligned-heading
 * Categories: purple
 * Keywords: posts, blog, query, columns, grid
 * Description: A list of posts, 3 columns, with featured image, post date and title.
 * Block Types: core/query
 * Viewport width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"List of posts with left aligned heading","categories":["purple"],"patternName":"purple/posts-three-columns-left-aligned-heading"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"align":"wide","style":{"spacing":{"margin":{"bottom":"0","top":"0"}},"typography":{"textAlign":"left"}}} -->
<h2 class="wp-block-heading has-text-align-left alignwide" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'More from the journal', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
<p style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'The latest news on sustainability, knitwear and clothing care guides.', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:query {"queryId":0,"query":{"perPage":10,"pages":0,"offset":"0","postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"align":"full","style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:post-date {"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}},"style":{"typography":{"lineHeight":"1.71"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"textColor":"theme-2"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"0"}}},"fontSize":"x-large"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
