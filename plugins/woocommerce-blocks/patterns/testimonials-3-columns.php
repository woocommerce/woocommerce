<?php
/**
 * Title: Testimonials 3 Columns
 * Slug: woocommerce-blocks/testimonials-3-columns
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/testimonials-3-columns' );
?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
	<!-- wp:heading {"level":3,"align":"wide"} -->
	<h3 class="wp-block-heading alignwide"><?php echo esc_html( $content['titles'][3]['default'] ); ?></h3>
	<!-- /wp:heading -->

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
</div>
<!-- /wp:group -->
