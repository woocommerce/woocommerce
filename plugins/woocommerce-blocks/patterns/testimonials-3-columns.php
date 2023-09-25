<?php
/**
 * Title: Testimonials 3 Columns
 * Slug: woocommerce-blocks/testimonials-3-columns
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/testimonials-3-columns' );
?>

<!-- wp:columns {"align":"full"} -->
<div class="wp-block-columns alignfull">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][0]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][0]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>~ Tanner P.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->

		<p><strong><?php echo esc_html( $content['titles'][1]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][1]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>~ Abigail N.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][2]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][2]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>~ Albert L.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
