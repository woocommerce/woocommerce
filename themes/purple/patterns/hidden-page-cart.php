<?php
/**
 * Title: WooCommerce page cart
 * Slug: purple/hidden-page-cart
 * Inserter: no
 */
?>

<!-- wp:woocommerce/page-content-wrapper {"page":"cart"} -->
	<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained","contentSize":"1060px"}} -->
	<main class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--30)">

		<!-- wp:heading {"level":1,"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"fontSize":"xx-large"} -->
		<h1 class="wp-block-heading alignwide has-xx-large-font-size" style="margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Cart', 'purple');?></h1>
		<!-- /wp:heading -->

		<!-- wp:woocommerce/store-notices /-->

		<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
		<div class="wp-block-group alignwide">
			<!-- wp:woocommerce/cart -->
			<div class="wp-block-woocommerce-cart alignwide is-loading">
				<!-- wp:woocommerce/filled-cart-block -->
				<div class="wp-block-woocommerce-filled-cart-block">
					<!-- wp:woocommerce/cart-items-block -->
					<div class="wp-block-woocommerce-cart-items-block">
						<!-- wp:woocommerce/cart-line-items-block -->
						<div class="wp-block-woocommerce-cart-line-items-block"></div>
						<!-- /wp:woocommerce/cart-line-items-block -->
					</div>
					<!-- /wp:woocommerce/cart-items-block -->

					<!-- wp:woocommerce/cart-totals-block -->
					<div class="wp-block-woocommerce-cart-totals-block">
						<!-- wp:woocommerce/cart-order-summary-block -->
						<div class="wp-block-woocommerce-cart-order-summary-block">
							<!-- wp:woocommerce/cart-order-summary-heading-block -->
							<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
							<!-- /wp:woocommerce/cart-order-summary-heading-block -->

							<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
							<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
							<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

							<!-- wp:woocommerce/cart-order-summary-totals-block -->
							<div class="wp-block-woocommerce-cart-order-summary-totals-block">
								<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
								<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
								<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

								<!-- wp:woocommerce/cart-order-summary-fee-block -->
								<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
								<!-- /wp:woocommerce/cart-order-summary-fee-block -->

								<!-- wp:woocommerce/cart-order-summary-discount-block -->
								<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
								<!-- /wp:woocommerce/cart-order-summary-discount-block -->

								<!-- wp:woocommerce/cart-order-summary-shipping-block -->
								<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
								<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

								<!-- wp:woocommerce/cart-order-summary-taxes-block -->
								<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
								<!-- /wp:woocommerce/cart-order-summary-taxes-block -->
							</div>
							<!-- /wp:woocommerce/cart-order-summary-totals-block -->
						</div>
						<!-- /wp:woocommerce/cart-order-summary-block -->

						<!-- wp:woocommerce/cart-express-payment-block -->
						<div class="wp-block-woocommerce-cart-express-payment-block"></div>
						<!-- /wp:woocommerce/cart-express-payment-block -->

						<!-- wp:woocommerce/proceed-to-checkout-block -->
						<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
						<!-- /wp:woocommerce/proceed-to-checkout-block -->

						<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
						<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
						<!-- /wp:woocommerce/cart-accepted-payment-methods-block -->
					</div>
					<!-- /wp:woocommerce/cart-totals-block -->
				</div>
				<!-- /wp:woocommerce/filled-cart-block -->

				<!-- wp:woocommerce/empty-cart-block -->
				<div class="wp-block-woocommerce-empty-cart-block">
					<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
						<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
						<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
						<!-- /wp:spacer -->

						<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
						<h2 class="wp-block-heading has-text-align-center" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e('Your shopping bag is empty', 'purple');?></h2>
						<!-- /wp:heading -->

						<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
						<div class="wp-block-buttons"><!-- wp:button -->
							<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop"><?php esc_html_e('Return to shop', 'purple');?></a></div>
							<!-- /wp:button --></div>
						<!-- /wp:buttons -->

						<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
						<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
						<!-- /wp:spacer -->

						<!-- wp:paragraph {"align":"center"} -->
						<p class="has-text-align-center"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
				echo sprintf( esc_html__( 'Have an account? %1$sLog in%2$s to check out faster.', 'purple' ), '<a href="' . esc_url( '/my-account' ) . '">', '</a>' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

				</div>
				<!-- /wp:woocommerce/empty-cart-block -->
			</div>
			<!-- /wp:woocommerce/cart -->
		</div>
		<!-- /wp:group -->
	</main>
	<!-- /wp:group -->

	<!-- wp:pattern {"slug":"purple/woocommerce-upsell-products-collection"} /-->
<!-- /wp:woocommerce/page-content-wrapper -->
