<?php
/**
 * Title: Hero Product 3 Split
 * Slug: woocommerce-blocks/hero-product-3-split
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$main_title   = $content['titles'][3]['default'] ?? '';
$first_title  = $content['titles'][0]['default'] ?? '';
$second_title = $content['titles'][1]['default'] ?? '';
$third_title  = $content['titles'][2]['default'] ?? '';

$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
$third_description  = $content['descriptions'][2]['default'] ?? '';
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"0"},"margin":{"top":"0px","bottom":"80px"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:0px;margin-bottom:80px">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:cover {"url":"<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg' ) ); ?>","dimRatio":0,"minHeight":800,"minHeightUnit":"px","isDark":false,"layout":{"type":"constrained"}} -->
		<div class="wp-block-cover is-light" style="min-height:800px">
			<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
			<img
				class="wp-block-cover__image-background"
				alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased.', 'woocommerce' ); ?>"
				src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg' ) ); ?>"
				data-object-fit="cover" />
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph {"align":"center","placeholder":" ","fontSize":"large"} -->
				<p class="has-text-align-center has-large-font-size"></p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:cover -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center"} -->
	<div class="wp-block-column is-vertically-aligned-center">
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"50px","right":"50px"},"blockGap":"48px","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:20px;padding-right:50px;padding-bottom:20px;padding-left:50px">
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><strong><?php echo esc_html( $main_title ); ?></strong></h3>
			<!-- /wp:heading -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"35px"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
					<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $first_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $first_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:separator {"className":"is-style-wide"} -->
				<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
				<!-- /wp:separator -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
					<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $second_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $second_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:separator {"className":"is-style-wide"} -->
				<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
				<!-- /wp:separator -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
					<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $third_title ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $third_description ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons"><!-- wp:button -->
				<div class="wp-block-button">
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'woocommerce' ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
