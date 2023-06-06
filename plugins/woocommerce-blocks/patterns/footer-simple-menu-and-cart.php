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
				<!-- wp:group {"style":{"border":{"top":{"width":"1px","style":"solid"},"right":{"width":"1px","style":"solid"},"bottom":{"width":"1px","style":"solid"},"left":{"width":"1px","style":"solid"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group" style="border-top-style:solid;border-top-width:1px;border-right-style:solid;border-right-width:1px;border-bottom-style:solid;border-bottom-width:1px;border-left-style:solid;border-left-width:1px">
					<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search our store","width":250,"widthUnit":"px","buttonText":"Search","buttonUseIcon":true,"query":{"post_type":"product"},"style":{"border":{"top":{"width":"0px","style":"none"},"right":{"width":"0px","style":"none"},"bottom":{"width":"0px","style":"none"},"left":{"width":"0px","style":"none"}}},"backgroundColor":"background","textColor":"foreground"} /-->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
				<div class="wp-block-group">
					<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"35px"}}} /-->
				</div>
				<!-- /wp:group -->
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

	<!-- wp:separator {"className":"is-style-wide"} -->
	<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
	<!-- /wp:separator -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"5px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"700"}}} /-->

		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"}}} -->
		<p class="has-text-align-center" style="font-size:12px">
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
