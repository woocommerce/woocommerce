<?php
/**
 * Title: Centered Header Menu with Search
 * Slug: woocommerce-blocks/header-centered-menu-with-search
 * Categories: WooCommerce
 * Block Types: core/template-part/header
 */
?>

<!-- wp:group {"className":"wc-blocks-header-pattern","align":"full","layout":{"type":"constrained"}} -->
<div class="wc-blocks-header-pattern wp-block-group alignfull">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"2%","bottom":"16px","left":"2%","top":"16px"},"margin":{"top":"0px","bottom":"0px"}}},"className":"sticky-header","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignfull sticky-header" style="margin-top:0px;margin-bottom:0px;padding-top:16px;padding-right:2%;padding-bottom:16px;padding-left:2%">
		<!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"shouldSyncIcon":false} /-->
			<!-- wp:site-title /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group">
			<!-- wp:search {"label":"<?php esc_html_e( 'Search', 'woo-gutenberg-products-block' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search', 'woo-gutenberg-products-block' ); ?>","buttonText":"<?php esc_html_e( 'Search', 'woo-gutenberg-products-block' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"},"width":100,"widthUnit":"%"} /-->
			<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconClass":"wc-block-customer-account__account-icon","style":{"spacing":{"margin":{"left":"10px"}}}} /-->
			<!-- wp:woocommerce/mini-cart /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"align":"full","className":"is-style-wide"} -->
	<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide" />
	<!-- /wp:separator -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:navigation {"style":{"typography":{"fontStyle":"normal","fontWeight":"400"}}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"align":"full","className":"is-style-wide"} -->
	<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide" />
	<!-- /wp:separator -->
</div>
<!-- /wp:group -->
