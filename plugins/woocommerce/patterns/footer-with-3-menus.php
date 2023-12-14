<?php
/**
 * Title: Footer with menus
 * Slug: woocommerce-blocks/footer-with-3-menus
 * Categories: WooCommerce
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"40px","padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"}}},"className":"wc-blocks-footer-pattern"} -->
<div class="wp-block-group alignfull wc-blocks-footer-pattern" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px">
	<!-- wp:columns {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"className":"are-vertically-aligned-top"} -->
	<div class="wp-block-columns are-vertically-aligned-top" style="padding-right:0;padding-left:0">
		<!-- wp:column {"verticalAlignment":"top","width":"60%"} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:60%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"32px"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} -->
			<div class="wp-block-group">
				<!-- wp:site-logo /-->

				<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}},"layout":{"selfStretch":"fill","flexSize":null}}} -->
				<div class="wp-block-columns">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"10px"}}} /-->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"10%"} -->
		<div class="wp-block-column" style="flex-basis:10%"></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"stretch","width":"30%","style":{"spacing":{"blockGap":"60px"}},"layout":{"type":"default"}} -->
		<div class="wp-block-column is-vertically-aligned-stretch" style="flex-basis:30%">
			<!-- wp:search {"style":{"border":{"radius":"0px"}}, "label":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonText":"<?php esc_html_e( 'Search', 'woocommerce' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"},"width":100,"widthUnit":"%"} /-->
			<!-- wp:group {"style":{"spacing":{"blockGap":"0","padding":{"right":"0","left":"0"},"margin":{"top":"56px","bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"right"}} -->
			<div class="wp-block-group" style="margin-top:56px;margin-bottom:0;padding-right:0;padding-left:0">
				<!-- wp:site-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}}} /-->

				<!-- wp:paragraph {"align":"right"} --><p class="has-text-align-right">
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
				</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
