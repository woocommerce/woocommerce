<?php
/**
 * Title: Mini cart contents
 * Slug: purple/hidden-mini-cart-contents
 * Inserter: no
 */
?>

<!-- wp:woocommerce/mini-cart-contents {"width":"560px"} -->
<div class="wp-block-woocommerce-mini-cart-contents"><!-- wp:woocommerce/filled-mini-cart-contents-block -->
	<div class="wp-block-woocommerce-filled-mini-cart-contents-block"><!-- wp:woocommerce/mini-cart-title-block -->
		<div class="wp-block-woocommerce-mini-cart-title-block">
			<!-- wp:woocommerce/mini-cart-title-label-block {"fontSize":"x-large"} -->
			<div class="wp-block-woocommerce-mini-cart-title-label-block has-x-large-font-size"></div>
			<!-- /wp:woocommerce/mini-cart-title-label-block -->

			<!-- wp:woocommerce/mini-cart-title-items-counter-block {"fontSize":"x-large"} -->
			<div class="wp-block-woocommerce-mini-cart-title-items-counter-block has-x-large-font-size"></div>
			<!-- /wp:woocommerce/mini-cart-title-items-counter-block -->
		</div>
		<!-- /wp:woocommerce/mini-cart-title-block -->

		<!-- wp:woocommerce/mini-cart-items-block -->
		<div class="wp-block-woocommerce-mini-cart-items-block"><!-- wp:woocommerce/mini-cart-products-table-block -->
			<div class="wp-block-woocommerce-mini-cart-products-table-block"></div>
			<!-- /wp:woocommerce/mini-cart-products-table-block -->
		</div>
		<!-- /wp:woocommerce/mini-cart-items-block -->

		<!-- wp:woocommerce/mini-cart-footer-block -->
		<div class="wp-block-woocommerce-mini-cart-footer-block">
			<!-- wp:woocommerce/mini-cart-cart-button-block {"cartButtonLabel":"<?php echo esc_attr__( 'View cart', 'purple' ); ?>"} -->
			<div class="wp-block-woocommerce-mini-cart-cart-button-block"></div>
			<!-- /wp:woocommerce/mini-cart-cart-button-block -->

			<!-- wp:woocommerce/mini-cart-checkout-button-block {"checkoutButtonLabel":"<?php echo esc_attr__( 'Proceed to checkout', 'purple' ); ?>"} -->
			<div class="wp-block-woocommerce-mini-cart-checkout-button-block"></div>
			<!-- /wp:woocommerce/mini-cart-checkout-button-block -->
		</div>
		<!-- /wp:woocommerce/mini-cart-footer-block -->
	</div>
	<!-- /wp:woocommerce/filled-mini-cart-contents-block -->

	<!-- wp:woocommerce/empty-mini-cart-contents-block -->
	<div class="wp-block-woocommerce-empty-mini-cart-contents-block">
		<!-- wp:paragraph {"metadata":{"patternName":"purple/hidden-mini-cart-empty-message","name":"Empty Mini-Cart Message"},"style":{"typography":{"textAlign":"center"}}} -->
		<p class="has-text-align-center"><strong><?php esc_html_e( 'Your shopping cart is empty', 'purple' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:woocommerce/mini-cart-shopping-button-block {"startShoppingButtonLabel":"<?php echo esc_attr__( 'Return to shop', 'purple' ); ?>"} -->
		<div class="wp-block-woocommerce-mini-cart-shopping-button-block"></div>
		<!-- /wp:woocommerce/mini-cart-shopping-button-block -->
	</div>
	<!-- /wp:woocommerce/empty-mini-cart-contents-block -->
</div>
<!-- /wp:woocommerce/mini-cart-contents -->
