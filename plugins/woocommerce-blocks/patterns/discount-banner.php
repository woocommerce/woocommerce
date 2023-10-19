<?php
/**
 * Title: Discount Banner
 * Slug: woocommerce-blocks/discount-banner
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$content     = PatternsHelper::get_pattern_content( 'woocommerce-blocks/discount-banner' );
$description = $content['descriptions'][0]['default'] ?? '';
?>

<!-- wp:group {"layout":{"type":"constrained","contentSize":"470px"}} -->
<div class="wp-block-group">
	<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#254094"}},"layout":{"type":"constrained","contentSize":""}} -->
	<div class="wp-block-group has-background" style="background-color:#254094;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"45px"},"color":{"text":"#ffffff"}}} -->
		<p class="has-text-color" style="color:#ffffff;font-size:45px;font-style:normal;font-weight:500">UP TO</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"color":{"text":"#fdf251"},"typography":{"fontStyle":"normal","fontWeight":"800","fontSize":"90px","lineHeight":"0.1"}}} -->
		<p class="has-text-color" style="color:#fdf251;font-size:90px;font-style:normal;font-weight:800;line-height:0.1">40% off</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","fontSize":"35px"},"color":{"text":"#ffffff"}}} -->
		<p class="has-text-color" style="color:#ffffff;font-size:35px;font-style:normal;font-weight:300"><?php echo esc_html( $description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"style":{"color":{"background":"#ff7179","text":"#ffffff"},"border":{"radius":"40px"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"30px","right":"30px"}}}} -->
			<div class="wp-block-button">
				<a class="wp-block-button__link has-text-color has-background wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" style="border-radius:40px;color:#ffffff;background-color:#ff7179;padding-top:10px;padding-right:30px;padding-bottom:10px;padding-left:30px">
					Shop now
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
