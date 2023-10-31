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
	<!-- wp:columns {"verticalAlignment":"center","isStackedOnMobile":false,"align":"full","style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"40px","right":"40px"}}}} -->
	<div class="wp-block-columns alignfull are-vertically-aligned-center is-not-stacked-on-mobile" style="padding-top:24px;padding-right:40px;padding-bottom:24px;padding-left:40px">
		<!-- wp:column {"verticalAlignment":"center","width":"70%","layout":{"type":"default"}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:70%">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
			<div class="wp-block-group"><!-- wp:site-logo {"shouldSyncIcon":false} /-->
				<!-- wp:site-title {"style":{"layout":{"selfStretch":"fixed","flexSize":"200px"},"typography":{"fontStyle":"normal","fontWeight":"700"}}} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":""} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
			<div class="wp-block-group">
				<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconClass":"wc-block-customer-account__account-icon"} /-->
				<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:separator {"align":"full","style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"className":"is-style-wide"} -->
	<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide"
		style="margin-top:0px;margin-bottom:0px" />
	<!-- /wp:separator -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:navigation {"style":{"typography":{"fontStyle":"normal","fontWeight":"400"},"spacing":{"blockGap":"30px"}}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"align":"full","className":"is-style-wide"} -->
	<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide" />
	<!-- /wp:separator -->
</div>
<!-- /wp:group -->
