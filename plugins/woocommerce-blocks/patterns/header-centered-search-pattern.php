<?php
/**
 * Title: Header Centered Menu with Search
 * Slug: woocommerce-blocks/header-centered-menu-with-search
 * Categories: WooCommerce
 * Block Types: core/template-part/header
 */
?>

<!-- wp:group {"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"2%","bottom":"16px","left":"2%","top":"16px"},"margin":{"top":"0px","bottom":"0px"}}},"className":"sticky-header","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignfull sticky-header" style="margin-top:0px;margin-bottom:0px;padding-top:16px;padding-right:2%;padding-bottom:16px;padding-left:2%">
		<!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"shouldSyncIcon":false} /-->
			<!-- wp:site-title /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"8px"},"typography":{"fontSize":"13px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group" style="font-size:13px">
			<!-- wp:group {"style":{"border":{"top":{"width":"1px","style":"solid"},"right":{"width":"1px","style":"solid"},"bottom":{"width":"1px","style":"solid"},"left":{"width":"1px","style":"solid"}},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group has-background" style="border-top-style:solid;border-top-width:1px;border-right-style:solid;border-right-width:1px;border-bottom-style:solid;border-bottom-width:1px;border-left-style:solid;border-left-width:1px;background-color:#ffffff;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search our store","width":350,"widthUnit":"px","buttonText":"Search","buttonUseIcon":true,"query":{"post_type":"product"},"style":{"border":{"top":{"width":"0px","style":"none"},"right":{"width":"0px","style":"none"},"bottom":{"width":"0px","style":"none"},"left":{"width":"0px","style":"none"}},"color":{"background":"#ffffff","text":"#000000"}}} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconClass":"wc-block-customer-account__account-icon","style":{"spacing":{"margin":{"left":"10px"}}}} /-->

			<!-- wp:woocommerce/mini-cart {"style":{"typography":{"fontSize":"14px"}}} /-->
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
