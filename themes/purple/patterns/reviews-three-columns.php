<?php
/**
 * Title: Reviews three columns
 * Slug: purple/reviews-three-columns
 * Categories: testimonials, purple
 * Keywords: reviews, testimonials, ratings, stars
 * Description: A three-column reviews section with star ratings, review text, and reviewer name.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Reviews","categories":["testimonials","purple"],"patternName":"purple/reviews-three-columns"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"align":"wide","style":{"typography":{"textAlign":"left"}}} -->
<h2 class="wp-block-heading has-text-align-left alignwide"><?php esc_html_e( 'Reviews', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:columns {"metadata":{"name":"Items"},"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"0.63rem"}}} -->
<div class="wp-block-column"><!-- wp:jetpack/rating-star {"rating":4.5,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"metadata":{"name":"Review"},"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'I couldn\'t be happier with my purchase. The quality is outstanding, and the attention to detail is impressive. I\'ll definitely be back for more!', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"Name"},"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Aya Nakamura', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"0.63rem"}}} -->
<div class="wp-block-column"><!-- wp:jetpack/rating-star {"rating":4.5,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'From start to finish, the process was seamless. It\'s better than I imagined, and the customer support was incredibly responsive and friendly.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"Name"},"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Aya Nakamura', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"metadata":{"name":"Item"},"style":{"spacing":{"blockGap":"0.63rem"}}} -->
<div class="wp-block-column"><!-- wp:jetpack/rating-star {"rating":4.5,"className":"is-style-filled"} -->
<figure class="wp-block-jetpack-rating-star is-style-filled" style="text-align:left"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></figure>
<!-- /wp:jetpack/rating-star -->

<!-- wp:paragraph {"className":"is-testimonial-review"} -->
<p class="is-testimonial-review"><?php esc_html_e( 'Amazing quality! It\'s rare to find something that checks all the boxes, but this did. I\'ll be recommending it to everyone I know!', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"Name"},"className":"is-testimonial-name"} -->
<p class="is-testimonial-name"><em><?php esc_html_e( '— Aya Nakamura', 'purple' ); ?></em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
