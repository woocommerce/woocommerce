<?php
/**
 * Title: Reviews: heading on left, two columns of reviews on right
 * Slug: purple/reviews-heading-left
 * Categories: testimonials, purple
 * Keywords: reviews, testimonials, ratings, stars, columns
 * Description: A reviews section with the heading on the left and two columns containing four customer reviews on the right.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Reviews: heading on left, two columns of reviews on right","categories":["testimonials","purple"],"patternName":"purple/reviews-heading-left"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"metadata":{"name":"Items"},"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"style":{"layout":{"selfStretch":"fixed","flexSize":"100%"}}} -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Reviews', 'purple' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:jetpack/rating-star {"rating":5,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'I couldn\'t be happier with my purchase. The quality is outstanding, and the attention to detail is impressive. I\'ll definitely be back for more!', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Aya Nakamura', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:jetpack/rating-star {"rating":4,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'Amazing quality! It\'s rare to find something that checks all the boxes, but this did. I\'ll be recommending it to everyone I know!', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Lila Patel', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
<div class="wp-block-column"><!-- wp:group {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:jetpack/rating-star {"rating":4,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'From start to finish, the process was seamless. It\'s better than I imagined, and the customer support was incredibly responsive and friendly.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Mateo García', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:jetpack/rating-star {"rating":5,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'I couldn\'t be happier with my purchase. The quality is outstanding, and the attention to detail is impressive. I\'ll definitely be back for more!', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Aya Nakamura', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
