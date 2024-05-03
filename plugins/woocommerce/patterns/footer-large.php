<?php
/**
 * Title: Large Footer
 * Slug: woocommerce-blocks/footer-large
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"className":"wc-blocks-footer-pattern","align":"full","style":{"spacing":{"padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"},"blockGap":"40px"}}} -->
<div class="wc-blocks-pattern-footer-large wc-blocks-footer-pattern wp-block-group alignfull" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"32px","padding":{"right":"0px","left":"0px"}}}} -->
	<div class="wp-block-columns alignwide" style="padding-right:0px;padding-left:0px">
		<!-- wp:column {"width":"60%","style":{"spacing":{"padding":{"right":"50px"}}}} -->
		<div class="wp-block-column" style="padding-right:50px;flex-basis:60%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group"><!-- wp:site-logo /-->
				<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"none"},"spacing":{"margin":{"top":"40px"}}}} -->
				<h5 class="wp-block-heading" style="margin-top:40px;text-transform:none"><?php esc_html_e( 'Join the community', 'woocommerce' ); ?></h5>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"40px"}}}} -->
				<p style="margin-bottom:40px"><?php esc_html_e( 'Learn about new products and discounts', 'woocommerce' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:social-links {"size":"has-small-icon-size","style":{"spacing":{"blockGap":{"left":"16px"}}},"className":"is-style-logos-only"} -->
				<ul class="wp-block-social-links has-small-icon-size is-style-logos-only">
					<!-- wp:social-link {"url":"https://www.facebook.com","service":"facebook"} /-->
					<!-- wp:social-link {"url":"https://www.x.com","service":"x"} /-->
					<!-- wp:social-link {"url":"https://www.instagram.com","service":"instagram"} /-->
					<!-- wp:social-link {"url":"https://www.twitch.com","service":"twitch"} /-->
				</ul>
				<!-- /wp:social-links -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"20%","style":{"spacing":{"padding":{"top":"0px"}}}} -->
		<div class="wp-block-column" style="padding-top:0px;flex-basis:20%">
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"20%","style":{"spacing":{"blockGap":"16px"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%">
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"20%","style":{"spacing":{"blockGap":"16px"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%">
			<!-- wp:navigation {"overlayMenu":"never","layout":{"overlayMenu":"never","type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"10px"}}} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group alignfull" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
		<!-- wp:site-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}}} /-->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">
			<?php
			echo sprintf(
			/* translators: Footer powered by text. %1$s being WordPress, %2$s being WooCommerce */
				esc_html__(
					'Powered by %1$s with %2$s',
					'woocommerce'
				),
				'<a href="https://wordpress.org" target="_blank" rel="noreferrer nofollow">WordPress</a>',
				'<a href="https://woocommerce.com" target="_blank" rel="noreferrer nofollow">WooCommerce</a>'
			);
			?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
