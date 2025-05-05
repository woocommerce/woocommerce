<?php
/**
 * Title: Four Image Grid Content Left
 * Slug: woocommerce-blocks/form-image-grid-content-left
 * Categories: WooCommerce, About
 */
declare(strict_types=1);

$header  = __( 'Create anything', 'woocommerce' );
$content = __( 'Navigating life\'s intricate fabric, choices unfold paths to the extraordinary, demanding creativity, curiosity, and courage for a truly fulfilling journey.', 'woocommerce' );
$button  = __( 'Get Started', 'woocommerce' );

$image_0 = plugins_url( 'assets/images/pattern-placeholders/sun-glass-vase-green-ceramic-shelf.jpg', WC_PLUGIN_FILE );
$image_1 = plugins_url( 'assets/images/pattern-placeholders/white-vase-decoration-pattern-ceramic-lamp.jpg', WC_PLUGIN_FILE );
$image_2 = plugins_url( 'assets/images/pattern-placeholders/plant-white-leaf-flower-vase-green.jpg', WC_PLUGIN_FILE );
$image_3 = plugins_url( 'assets/images/pattern-placeholders/tree-branch-plant-wood-leaf-flower.jpg', WC_PLUGIN_FILE );

?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"className":"alignfull","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"40%","layout":{"type":"constrained","justifyContent":"left","contentSize":"400px"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html( $header ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $content ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php echo esc_html( $button ); ?></a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"8px","left":"8px"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"blockGap":"8px"}}} -->
<div class="wp-block-column"><!-- wp:image {"id":13699,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default"} -->
<figure class="wp-block-image size-full is-style-default"><img src="<?php echo esc_html( $image_0 ); ?>" alt="" class="wp-image-13699" style="aspect-ratio:1;object-fit:cover" /></figure>
<!-- /wp:image -->

<!-- wp:image {"id":13707,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default"} -->
<figure class="wp-block-image size-full is-style-default"><img src="<?php echo esc_html( $image_1 ); ?>" alt="" class="wp-image-13707" style="aspect-ratio:1;object-fit:cover" /></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"8px"}}} -->
<div class="wp-block-column"><!-- wp:image {"id":13707,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default"} -->
<figure class="wp-block-image size-full is-style-default"><img src="<?php echo esc_html( $image_2 ); ?>" alt="" class="wp-image-13707" style="aspect-ratio:1;object-fit:cover" /></figure>
<!-- /wp:image -->

<!-- wp:image {"id":13699,"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-default"} -->
<figure class="wp-block-image size-full is-style-default"><img src="<?php echo esc_html( $image_3 ); ?>" alt="" class="wp-image-13699" style="aspect-ratio:1;object-fit:cover" /></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
