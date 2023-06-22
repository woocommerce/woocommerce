<?php
/**
 * Title: Discount Banner
 * Slug: woocommerce-blocks/discount-banner
 * Categories: WooCommerce
 */
?>

<!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center">
	<!-- wp:column {"verticalAlignment":"center","width":"400px","style":{"color":{"background":"#254094"},"spacing":{"padding":{"top":"25px","right":"40px","bottom":"40px","left":"40px"}}}} -->
	<div class="wp-block-column is-vertically-aligned-center has-background" style="background-color:#254094;padding-top:25px;padding-right:40px;padding-bottom:40px;padding-left:40px;flex-basis:400px">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"45px"},"color":{"text":"#ffffff"}}} -->
		<p class="has-text-color" style="color:#ffffff;font-size:45px;font-style:normal;font-weight:500">UP TO</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"color":{"text":"#fdf251"},"typography":{"fontStyle":"normal","fontWeight":"800","fontSize":"90px","lineHeight":"0.1"}}} -->
		<p class="has-text-color" style="color:#fdf251;font-size:90px;font-style:normal;font-weight:800;line-height:0.1">40% off</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","fontSize":"35px"},"color":{"text":"#ffffff"}}} -->
		<p class="has-text-color" style="color:#ffffff;font-size:35px;font-style:normal;font-weight:300">Select products</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"style":{"color":{"background":"#ff7179","text":"#ffffff"},"border":{"radius":"40px"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"30px","right":"30px"}}}} -->
			<div class="wp-block-button">
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="border-radius:40px;color:#ffffff;background-color:#ff7179;padding-top:10px;padding-right:30px;padding-bottom:10px;padding-left:30px">
					Shop now
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->

