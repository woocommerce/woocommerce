<?php
/**
 * Title: Heading with three columns of content with link
 * Slug: woocommerce-blocks/heading-with-three-columns-of-content-with-link
 * Categories: WooCommerce, Services
 */
declare(strict_types=1);

$header        = __( 'Our services', 'woocommerce' );
$product_title = __( 'Create anything', 'woocommerce' );
$description   = __( 'Navigating life\'s intricate fabric, choices unfold paths to the extraordinary, demanding creativity, curiosity, and courage for a truly fulfilling journey.', 'woocommerce' );
$button_link   = __( 'Get started', 'woocommerce' );
?>



<!-- wp:group {"metadata":{"name":"Services"},"align":"full","className":"alignfull","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"left","align":"full","style":{"typography":{"fontStyle":"normal"}}} -->
<h2 class="wp-block-heading alignfull has-text-align-left" style="font-style:normal;"><?php echo esc_html( $header ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-service-link"} -->
<p class="is-service-link"><a href="#"><?php echo esc_html( $button_link ); ?></a> →</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-service-link"} -->
<p class="is-service-link"><a href="#"><?php echo esc_html( $button_link ); ?></a> →</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-service-link"} -->
<p class="is-service-link"><a href="#"><?php echo esc_html( $button_link ); ?></a> →</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
