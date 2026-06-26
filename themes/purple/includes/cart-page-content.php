<?php
/**
 * Default Cart page block markup for WooCommerce page creation and theme sync.
 *
 * @package purple
 */

declare( strict_types=1 );

if ( ! function_exists( 'purple_get_cart_page_content' ) ) :
	/**
	 * Returns Purple's cart block markup for the Cart page post content.
	 *
	 * @return string Block markup.
	 */
	function purple_get_cart_page_content(): string {
		$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop' );
		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account' );

		ob_start();
		?>
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
				<h2 class="wp-block-heading has-text-align-center" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><?php echo esc_html__( 'Your shopping bag is empty', 'purple' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html__( 'Return to shop', 'purple' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

				<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
				<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->

				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><?php
					echo sprintf(
						/* translators: 1: opening anchor tag, 2: closing anchor tag */
						esc_html__( 'Have an account? %1$sLog in%2$s to check out faster.', 'purple' ),
						'<a href="' . esc_url( $account_url ) . '">',
						'</a>'
					);
				?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:woocommerce/empty-cart-block -->
	</div>
	<!-- /wp:woocommerce/cart -->
</div>
<!-- /wp:group -->
		<?php
		return (string) ob_get_clean();
	}
endif;
