<?php
/**
 * Title: Featured Products 2 Columns
 * Slug: woocommerce-blocks/featured-products-2-cols
 * Categories: WooCommerce
 */

$first_title       = $content['titles'][0]['default'] ?? '';
$first_description = $content['descriptions'][0]['default'] ?? '';
$first_button      = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column {"width":"66.66%"} -->
	<div class="wp-block-column" style="flex-basis:66.66%">
		<!-- wp:query {"query":{"perPage":"4","pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","onbackorder"]},"displayLayout":{"type":"flex","columns":2},"namespace":"woocommerce/product-query"} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
			<!-- wp:woocommerce/product-image {"imageSizing":"single","isDescendentOfQueryLoop":true,"style":{"spacing":{"margin":{"bottom":"24px","top":"0"}}}} /-->

			<!-- wp:columns {"verticalAlignment":"bottom"} -->
			<div class="wp-block-columns are-vertically-aligned-bottom">
				<!-- wp:column {"verticalAlignment":"bottom"} -->
				<div class="wp-block-column is-vertically-aligned-bottom">
					<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /--></div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"bottom"} -->
				<div class="wp-block-column is-vertically-aligned-bottom">
					<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"right","fontSize":"small","style":{"spacing":{"margin":{"bottom":"0rem","top":"0"}}}} /--></div>
				<!-- /wp:column --></div>
			<!-- /wp:columns -->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"33.33%"} -->
	<div class="wp-block-column" style="flex-basis:33.33%">
		<!-- wp:heading {"level":4} -->
		<h4 class="wp-block-heading"><strong><?php echo esc_html( $first_title ); ?></strong></h4>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $first_description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"width":50} -->
			<div class="wp-block-button has-custom-width wp-block-button__width-50">
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link wp-element-button"><?php echo esc_html( $first_button ); ?></a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
