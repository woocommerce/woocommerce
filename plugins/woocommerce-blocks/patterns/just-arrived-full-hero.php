<?php
/**
 * Title: Just Arrived Full Hero
 * Slug: woocommerce-blocks/just-arrived-full-hero
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/just-arrived-full-hero' );
?>
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":0,"contentPosition":"center right","isDark":false,"align":"wide","style":{"spacing":{"padding":{"right":"4em"}}}} -->
<div class="wp-block-cover alignwide is-light has-custom-content-position is-position-center-right" style="padding-right:4em">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"anchor":"just-arrived"} -->
			<h2 class="wp-block-heading" id="just-arrived"><?php echo esc_html( $content['titles'][0]['default'] ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $content['descriptions'][0]['default'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button">
					<a class="wp-block-button__link wp-element-button"><?php echo esc_html( $content['buttons'][0]['default'] ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div></div>
<!-- /wp:cover -->
