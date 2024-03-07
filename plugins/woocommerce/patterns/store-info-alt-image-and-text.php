<?php
/**
 * Title: Alternating Image and Text
 * Slug: woocommerce-blocks/alt-image-and-text
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;

$image1 = PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/tea-leaf-meal-food-herb-produce.jpg' );
$image2 = PatternsHelper::get_image_url( $images, 1, 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg' );

$first_title  = $content['titles'][0]['default'] ?? '';
$second_title = $content['titles'][1]['default'] ?? '';
$third_title  = $content['titles'][2]['default'] ?? '';
$fourth_title = $content['titles'][3]['default'] ?? '';
$fifth_title  = $content['titles'][4]['default'] ?? '';

$first_description  = $content['descriptions'][0]['default'] ?? '';
$second_description = $content['descriptions'][1]['default'] ?? '';
$list1              = $content['descriptions'][2]['default'] ?? '';
$list2              = $content['descriptions'][3]['default'] ?? '';
$list3              = $content['descriptions'][4]['default'] ?? '';
$list4              = $content['descriptions'][5]['default'] ?? '';

$button = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"wide"} -->
<div class="wp-block-group alignwide">
	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
			<figure class="wp-block-image size-full">
				<img src="<?php echo esc_url( $image1 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used in the left column.', 'woocommerce' ); ?>" />
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:paragraph {"placeholder":"Content…","style":{"typography":{"textTransform":"uppercase"}}} -->
			<p style="text-transform:uppercase"><?php echo esc_html( $first_title ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0"><?php echo esc_html( $second_title ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $first_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:list -->
			<ul>
				<!-- wp:list-item -->
				<li><?php echo esc_html( $list1 ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php echo esc_html( $list2 ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php echo esc_html( $list3 ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php echo esc_html( $list4 ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%">
			<!-- wp:paragraph {"placeholder":"Content…","style":{"typography":{"textTransform":"uppercase"}}} -->
			<p style="text-transform:uppercase"><?php echo esc_html( $third_title ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0"><?php echo esc_html( $fourth_title ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $second_description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"blockGap":"0"}},"fontSize":"small"} -->
			<div class="wp-block-buttons has-custom-font-size has-small-font-size">
				<!-- wp:button {"className":"is-style-fill"} -->
				<div class="wp-block-button is-style-fill">
					<a class="wp-block-button__link wp-element-button"><?php echo esc_html( $button ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"none", "height":"auto","aspectRatio":"1","scale":"cover", "className":"is-resized"} -->
			<figure class="wp-block-image size-full is-resized">
				<img style="aspect-ratio:1;object-fit:cover" src="<?php echo esc_url( $image2 ); ?>" alt="<?php esc_attr_e( 'Placeholder image used in the right column.', 'woocommerce' ); ?>" />
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
