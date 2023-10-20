<?php
/**
 * Title: Large Footer
 * Slug: woocommerce-blocks/footer-large
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"className":"wc-blocks-footer-pattern","align":"full","style":{"spacing":{"padding":{"top":"32px","right":"4%","bottom":"32px","left":"4%"},"blockGap":"40px"}}} -->
<div class="wc-blocks-footer-pattern wp-block-group alignfull" style="padding-top:32px;padding-right:4%;padding-bottom:32px;padding-left:4%">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"16px"}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"60%","style":{"spacing":{"padding":{"right":"50px"}}}} -->
		<div class="wp-block-column" style="padding-right:50px;flex-basis:60%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:site-logo {"width":78} /-->

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

			<!-- wp:social-links {"size":"has-small-icon-size","className":"is-style-logos-only"} -->
			<ul class="wp-block-social-links has-small-icon-size is-style-logos-only">
				<!-- wp:social-link {"url":"https://www.facebook.com","service":"facebook"} /-->
				<!-- wp:social-link {"url":"https://www.twitter.com","service":"twitter"} /-->
				<!-- wp:social-link {"url":"https://www.instagram.com","service":"instagram"} /-->
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
			<!-- wp:navigation {"layout":{"type":"flex","orientation":"vertical"}} -->
			<!-- /wp:navigation -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="padding-top:0;padding-right:var(--wp--preset--spacing--30);padding-bottom:0;padding-left:var(--wp--preset--spacing--30)">
		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group alignfull" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"56px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"5px","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-right:0;padding-left:0">
				<!-- wp:paragraph -->
				<p>@ <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:site-title /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph -->
			<p>
				<?php
				echo sprintf(
				/* translators: %s WooCommerce link */
					esc_html__( 'Powered by %s', 'woo-gutenberg-products-block' ),
					'<a href="https://woocommerce.com/" target="_blank" rel="noreferrer nofollow">WooCommerce</a>'
				);
				?>
			</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
