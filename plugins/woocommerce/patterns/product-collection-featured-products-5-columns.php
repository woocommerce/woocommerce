<?php
/**
 * Title: Product Collection: Featured Products 5 Columns
 * Slug: woocommerce-blocks/product-collection-featured-products-5-columns
 * Categories: WooCommerce, featured-selling
 */

$collection_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:group {"metadata":{"name":"Product Collection: Featured Products 5 Columns"},"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))">
	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:heading {"textAlign":"center","level":3} -->
	<h3 class="wp-block-heading has-text-align-center">
		<?php echo esc_html( $collection_title ); ?>
	</h3>
	<!-- /wp:heading -->

	<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
	<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:woocommerce/product-collection {"queryId":2,"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":5},"queryContextIncludes":["collection"],"align":"wide"} -->
		<div class="wp-block-woocommerce-product-collection alignwide">
			<!-- wp:woocommerce/product-template -->
			<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true,"aspectRatio":"1"} /-->

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

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->