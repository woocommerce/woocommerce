<?php
/**
 * Title: List of posts in three columns
 * Slug: purple/posts-three-columns
 * Categories: posts, featured
 * Description: A list of posts, 3 columns, with featured image, post date and title.
 * Block Types: core/query
 */
?>
<!-- wp:group {"metadata":{"name":"List of posts in three columns"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.07rem"}}} -->
<p class="has-text-align-center" style="letter-spacing:0.07rem;text-transform:uppercase"><?php esc_html_e('Journal', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50","top":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--50)"><?php esc_html_e('Latest News', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":0,"query":{"perPage":2,"pages":0,"offset":"0","postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"align":"full","style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"spacing":{"margin":{"bottom":"0"}}}} /-->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:post-date /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"x-large"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->