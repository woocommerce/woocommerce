<?php
/**
 * Title: Large Footer Dark
 * Slug: woocommerce-blocks/footer-large-dark
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"className":"wc-blocks-footer-pattern","align":"full","style":{"spacing":{"padding":{"top":"32px","right":"4%","bottom":"32px","left":"4%"},"blockGap":"40px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"black","textColor":"white"} -->
<div class="wc-blocks-footer-pattern wp-block-group alignfull has-background-color has-white-color has-black-background-color has-text-color has-background has-link-color" style="padding-top:32px;padding-right:4%;padding-bottom:32px;padding-left:4%">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"16px"}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"45%","style":{"spacing":{"padding":{"right":"50px"}}}} -->
		<div class="wp-block-column" style="padding-right:50px;flex-basis:45%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"textColor":"background","layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group has-background-color has-text-color">
				<!-- wp:site-logo /-->

				<!-- wp:spacer {"height":"30px"} -->
				<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->

				<!-- wp:heading {"level":5} -->
				<h5><?php esc_html_e( 'Join the community', 'woo-gutenberg-products-block' ); ?></h5>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Learn about new products and discounts!', 'woo-gutenberg-products-block' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:spacer {"height":"20px"} -->
				<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
			</div>
			<!-- /wp:group -->

			<!-- wp:social-links {"iconColor":"background","iconColorValue":"#ffffff","size":"has-small-icon-size","className":"is-style-logos-only"} -->
			<ul class="wp-block-social-links has-small-icon-size has-icon-color is-style-logos-only">
				<!-- wp:social-link {"url":"d","service":"facebook"} /-->
				<!-- wp:social-link {"url":"d","service":"twitter"} /-->
				<!-- wp:social-link {"url":"d","service":"instagram"} /-->
			</ul>
			<!-- /wp:social-links -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"20%","style":{"spacing":{"padding":{"top":"0px"}}}} -->
		<div class="wp-block-column" style="padding-top:0px;flex-basis:20%">
			<!-- wp:navigation {"layout":{"type":"flex","orientation":"vertical"}} /--></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"20%","style":{"spacing":{"blockGap":"16px"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%">
			<!-- wp:navigation {"layout":{"type":"flex","orientation":"vertical"}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"20%","style":{"spacing":{"blockGap":"16px"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%">
			<!-- wp:woocommerce/customer-account {"displayStyle":"text_only","fontSize":"small"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"10px"}},"textColor":"background","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group alignfull has-background-color has-text-color" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
		<!-- wp:group {"style":{"spacing":{"blockGap":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group has-background-color has-text-color has-link-color">
			<!-- wp:paragraph -->
			<p>@ <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:site-title /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph -->
		<p><em>
			<?php
			echo sprintf(
				/* translators: %s WooCommerce link */
				esc_html__( 'Built with %s', 'woo-gutenberg-products-block' ),
				'<a href="https://woocommerce.com/" target="_blank" rel="noreferrer nofollow">WooCommerce</a>'
			);
			?>
		</em></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
