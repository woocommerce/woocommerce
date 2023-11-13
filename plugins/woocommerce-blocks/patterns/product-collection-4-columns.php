<?php
/**
 * Title: Product Collection 4 Columns
 * Slug: woocommerce-blocks/product-collection-4-columns
 * Categories: WooCommerce
 */
use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$products_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"0px","bottom":"80px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:0px;margin-bottom:80px;padding-top:0;padding-right:var(--wp--preset--spacing--30);padding-bottom:0;padding-left:var(--wp--preset--spacing--30)">
	<!-- wp:heading {"level":3,"align":"wide"} -->
	<h3 class="wp-block-heading alignwide"><?php echo esc_html( $products_title ); ?></h3>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-collection {"query":{"perPage":4,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":4},"align":"wide"} -->
	<div class="wp-block-woocommerce-product-collection alignwide">
		<!-- wp:woocommerce/product-template -->
		<!-- wp:woocommerce/product-image {"aspectRatio":"3/5","imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

		<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

		<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->

		<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true} /-->
		<!-- /wp:woocommerce/product-template -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
