<?php
/**
 * Title: Minimal Header
 * Slug: woocommerce-blocks/header-minimal
 * Categories: WooCommerce
 * Block Types: core/template-part/header
 */
?>

<!-- wp:group {"className":"wc-blocks-header-pattern","align":"full","style":{"spacing":{"padding":{"right":"40px","bottom":"24px","left":"40px","top":"24px"},"margin":{"top":"0px","bottom":"0px"}}},"className":"sticky-header","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wc-blocks-header-pattern wp-block-group alignfull sticky-header" style="margin-top:0px;margin-bottom:0px;padding-top:24px;padding-right:40px;padding-bottom:24px;padding-left:40px">
	<!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:site-logo {"shouldSyncIcon":false,"className":"is-style-default"} /-->
		<!-- wp:site-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
	<div class="wp-block-group">
		<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true} /-->
		<!-- wp:navigation {"overlayMenu":"always","layout":{"type":"flex","justifyContent":"left"}} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
