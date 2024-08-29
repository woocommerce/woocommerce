<?php
/**
 * Title: Footer with Simple Menu
 * Slug: woocommerce-blocks/footer-simple-menu
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}}},"className":"wc-blocks-footer-pattern"} -->
<div class="wc-blocks-pattern-footer-simple-menu wp-block-group alignfull wc-blocks-footer-pattern" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignfull" style="padding-right:0;padding-left:0">
		<!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:search {"style":{"border":{"radius":"0px"}},"label":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonText":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"},"width":100,"widthUnit":"%"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"24px"}}} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"align":"full","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"className":"is-style-wide"} -->
	<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"/>
	<!-- /wp:separator -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"5px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:site-title /-->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">
			<?php
				/* translators: 1: WordPress link, 2: WooCommerce link */
				echo sprintf( esc_html__( 'Powered by %1$s with %2$s', 'woocommerce' ), '<a href="https://wordpress.org" target="_blank" rel="noreferrer nofollow">WordPress</a>', '<a href="https://woocommerce.com" target="_blank" rel="noreferrer nofollow">WooCommerce</a>' );
			?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
