<?php
/**
 * Title: Three columns with images and content
 * Slug: woocommerce-blocks/three-columns-with-images-and-content
 * Categories: WooCommerce, Services
 */

declare(strict_types=1);
use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$header        = __( 'Our services', 'woocommerce' );
$product_title = __( 'Create anything', 'woocommerce' );
$description   = __( 'Navigating life\'s intricate fabric, choices unfold paths to the extraordinary, demanding creativity, curiosity, and courage for a truly fulfilling journey.', 'woocommerce' );
$image_0       = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/table-wood-house-chair-floor-window.jpg' );
$image_1       = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/hand-light-architecture-wood-white-house.jpg' );
$image_2       = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/Image-table-wood-chair-stool-interior-restaurant.jpg' );

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

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"id":13699,"aspectRatio":"4/3","scale":"cover","sizeSlug":"large","linkDestination":"none","style":{"color":[]}} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_html( $image_0 ); ?>" alt="" class="wp-image-13699" style="aspect-ratio:4/3;object-fit:cover" /></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"4px"} -->
<div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"id":13707,"aspectRatio":"4/3","scale":"cover","sizeSlug":"large","linkDestination":"none","style":{"color":[]}} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_html( $image_1 ); ?>" alt="" class="wp-image-13707" style="aspect-ratio:4/3;object-fit:cover" /></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"4px"} -->
<div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"id":13699,"aspectRatio":"4/3","scale":"cover","sizeSlug":"large","linkDestination":"none","style":{"color":[]}} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_html( $image_2 ); ?>" alt="" class="wp-image-13699" style="aspect-ratio:4/3;object-fit:cover" /></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"4px"} -->
<div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"className":"is-service-name","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading is-service-name has-medium-font-size" style="font-style:normal;font-weight:700"><?php echo esc_html( $product_title ); ?></h3>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-service-description"} -->
<p class="is-service-description"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
