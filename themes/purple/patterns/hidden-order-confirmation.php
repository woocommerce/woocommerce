<?php
/**
 * Title: WooCommerce order confirmation
 * Slug: purple/hidden-order-confirmation
 * Inserter: no
 */
?>

<!-- wp:group {"tagName":"main","metadata":{"patternName":"purple/hidden-order-confirmation","name":"WooCommerce order confirmation"},"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50"}}},"layout":{"inherit":true,"type":"constrained","contentSize":"1060px"}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/order-confirmation-status /-->

<!-- wp:woocommerce/order-confirmation-summary {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:woocommerce/order-confirmation-totals-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Order details', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-totals {"borderColor":"theme-6","textColor":"theme-2","lock":{"remove":true},"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"border":{"width":"1px"}}} /-->
<!-- /wp:woocommerce/order-confirmation-totals-wrapper -->

<!-- wp:woocommerce/order-confirmation-downloads-wrapper {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Downloads', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-downloads {"borderColor":"theme-6","lock":{"remove":true},"style":{"border":{"width":"1px"},"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|50"}}}} /-->
<!-- /wp:woocommerce/order-confirmation-downloads-wrapper -->

<!-- wp:columns {"align":"wide","className":"wc-block-order-confirmation-address-wrapper","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide wc-block-order-confirmation-address-wrapper" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:woocommerce/order-confirmation-shipping-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Shipping address', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-shipping-address {"lock":{"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"border":{"width":"1px","color":"rgba(17, 17, 17, 0.2)"}}} /-->
<!-- /wp:woocommerce/order-confirmation-shipping-wrapper --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:woocommerce/order-confirmation-billing-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Billing address', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-billing-address {"lock":{"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"border":{"width":"1px","color":"rgba(17, 17, 17, 0.2)"}}} /-->
<!-- /wp:woocommerce/order-confirmation-billing-wrapper --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:woocommerce/order-confirmation-additional-fields-wrapper {"align":"wide","style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Additional information', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-additional-fields /-->
<!-- /wp:woocommerce/order-confirmation-additional-fields-wrapper -->

<!-- wp:woocommerce/order-confirmation-additional-information /--></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","theme":"purple"} /-->
