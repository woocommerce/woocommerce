<?php
/**
 * Title: Large Header Dark
 * Slug: woocommerce-blocks/header-large-dark
 * Categories: WooCommerce
 * Block Types: core/template-part/header
 */
?>
<!-- wp:group {"className":"wc-blocks-header-pattern","align":"full","style":{"spacing":{"blockGap":"0px","padding":{"top":"1rem","right":"1rem","bottom":"1rem","left":"1rem"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"black","textColor":"white","layout":{"type":"default"}} -->
<div class="wc-blocks-header-pattern wp-block-group alignfull has-background-color has-white-color has-black-background-color has-text-color has-background has-link-color" style="padding-top:1rem;padding-right:1rem;padding-bottom:1rem;padding-left:1rem">
	<!-- wp:group {"style":{"spacing":{"blockGap":"1rem"}},"className":"has-small-font-size","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group has-small-font-size">
		<!-- wp:search {"style":{"border":{"radius":"0px"}},"label":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonText":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"},"width":100,"widthUnit":"%"} /-->
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","fontSize":"small","iconClass":"wc-block-customer-account__account-icon"} /-->
			<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true,"style":{"typography":{"fontSize":"14px"}}} /-->
		</div>
		<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"16px","padding":{"top":"1rem","left":"0px","bottom":"2rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-top:1rem;padding-bottom:2rem;padding-left:0px">
	<!-- wp:site-logo {"shouldSyncIcon":true} /-->
	<!-- wp:site-title /-->
</div>
<!-- /wp:group -->

<!-- wp:navigation {"textColor":"background","layout":{"type":"flex","justifyContent":"center"}} /-->
</div>
<!-- /wp:group -->
