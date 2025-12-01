<?php
/**
 * Title: Centered content with image below
 * Slug: woocommerce-blocks/centered-content-with-image-below
 * Categories: WooCommerce, Intro
 */

declare(strict_types=1);

$header  = __( 'Find your shade', 'woocommerce' );
$content = __( 'Explore our exclusive collection of sunglasses, crafted to elevate your look and safeguard your eyes. Find your perfect pair and see the world through a new lens.', 'woocommerce' );
$button  = __( 'Shop now', 'woocommerce' );
$image_0 = plugins_url( 'assets/images/pattern-placeholders/girls-in-the-hills.jpg', WC_PLUGIN_FILE );

?>

<!-- wp:group {"metadata":{"name":"Intro"},"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"className":"alignfull","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"metadata":{"name":"Content"},"layout":{"type":"constrained","contentSize":"580px"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","align":"wide","fontSize":"xx-large"} -->
<h2 class="wp-block-heading alignwide has-text-align-center has-xx-large-font-size"><?php echo esc_html( $header ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html( $content ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} --><div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html( $button ); ?></a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"id":13691,"sizeSlug":"full","linkDestination":"none","align":"wide"} -->
<figure class="wp-block-image alignwide size-full"><img src="<?php echo esc_url( $image_0 ); ?>" alt="" class="wp-image-13691" /></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
