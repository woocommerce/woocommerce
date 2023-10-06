<?php
/**
 * Title: Testimonials 3 Columns
 * Slug: woocommerce-blocks/testimonials-3-columns
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/testimonials-3-columns' );

$main_header        = $content['titles'][0]['default'] ?? '';
$first_title        = $content['titles'][1]['default'] ?? '';
$second_title       = $content['titles'][2]['default'] ?? '';
$third_title        = $content['titles'][3]['default'] ?? '';
$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
$third_description  = $content['descriptions'][2]['default'] ?? '';
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
	<!-- wp:heading {"level":3,"style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<h3 class="wp-block-heading" style="padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><?php echo esc_html( $main_header ); ?></h3>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignfull" style="padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html( $first_title ); ?></strong></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $first_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>~ Tanner P.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->

			<p><strong><?php echo esc_html( $second_title ); ?></strong></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $second_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>~ Abigail N.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html( $third_title ); ?></strong></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $third_description ); ?></p>
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
