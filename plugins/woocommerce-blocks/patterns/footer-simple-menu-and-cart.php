<?php
/**
 * Title: Footer with Simple Menu and Cart
 * Slug: woocommerce-blocks/footer-simple-menu-and-cart
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"align":"full"} -->
<div class="wp-block-group alignfull">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"verticalAlignment":"center","width":""} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"style":{"spacing":{"blockGap":"32px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-group">
				<!-- wp:search {"label":"<?php esc_html_e( 'Search', 'woo-gutenberg-products-block' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search our store', 'woo-gutenberg-products-block' ); ?>","buttonText":"<?php esc_html_e( 'Search our store', 'woo-gutenberg-products-block' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"}} /-->
				<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"35px"}}} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"140px","style":{"spacing":{"blockGap":"16px"}}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:140px">
			<!-- wp:woocommerce/mini-cart /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:separator {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"className":"is-style-wide"} -->
	<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"/>
	<!-- /wp:separator -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"5px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:site-title {"textAlign":"center"} /-->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">
			<?php
				/* translators: 1: WordPress link, 2: WooCommerce link */
				echo wp_kses( sprintf( __( 'Powered by %1$s with %2$s', 'woo-gutenberg-products-block' ), '<a href="https://wordpress.org">WordPress</a>', '<a href="https://woocommerce.com">WooCommerce</a>' ), array() );
			?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
