<?php
/**
 * Title: Product Collection: Featured Products 5 Columns
 * Slug: woocommerce-blocks/product-collection-featured-products-5-columns
 * Categories: WooCommerce
 */

$collection_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"0px","bottom":"80px"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group alignwide" style="margin-top:0px;margin-bottom:80px;padding-top:0;padding-right:var(--wp--preset--spacing--30);padding-bottom:0;padding-left:var(--wp--preset--spacing--30)">
	<!-- wp:heading {"textAlign":"center","level":3} -->
	<h3 class="wp-block-heading has-text-align-center">
		<?php echo esc_html( $collection_title ); ?>
	</h3>
	<!-- /wp:heading -->

	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:woocommerce/product-collection {"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":5},"align":"wide"} -->
			<div class="wp-block-woocommerce-product-collection alignwide">
				<!-- wp:woocommerce/product-template -->
				<!-- wp:woocommerce/product-image {"aspectRatio":"1","imageSizing":"single","isDescendentOfQueryLoop":true} /-->

				<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

				<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"small"} /-->
				<!-- /wp:woocommerce/product-template -->
			</div>
		<!-- /wp:woocommerce/product-collection -->

		<!-- wp:buttons {"align":"wide","layout":{"type":"flex","verticalAlignment":"center","justifyContent":"center"}} -->
		<div class="wp-block-buttons alignwide">
			<!-- wp:button {"textAlign":"center"} -->
			<div class="wp-block-button">
				<a class="wp-block-button__link has-text-align-center wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'Shop All', 'woocommerce' ); ?>
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
