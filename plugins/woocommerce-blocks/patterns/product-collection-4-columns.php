<?php
/**
 * Title: Product Collection 4 Columns
 * Slug: woocommerce-blocks/product-collection-4-columns
 * Categories: WooCommerce
 */
use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/product-collection-4-columns' );
?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
	<!-- wp:heading {"level":3,"align":"wide"} -->
	<h3 class="wp-block-heading alignwide"><?php echo esc_html( $content['titles'][0]['default'] ); ?></h3>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-collection {"query":{"perPage":4,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{},"parents":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":4},"align":"wide"} -->
	<div class="wp-block-woocommerce-product-collection alignwide">
		<!-- wp:woocommerce/product-template -->
		<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

		<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

		<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->

		<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
		<!-- /wp:woocommerce/product-template -->

		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
		<!-- wp:query-pagination-previous /-->

		<!-- wp:query-pagination-numbers /-->

		<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->

		<!-- wp:query-no-results -->
		<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
