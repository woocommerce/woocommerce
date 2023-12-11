<?php
/**
 * Title: Related Products
 * Slug: woocommerce-blocks/related-products
 * Categories: WooCommerce
 * Inserter: false
 */
?>

<!-- wp:woocommerce/related-products {"align":"wide"} -->
<div class="wp-block-woocommerce-related-products alignwide">
		<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"flex","columns":5},"namespace":"woocommerce/related-products","lock":{"remove":true,"move":true}} -->
		<div class="wp-block-query">
			<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
			<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)">
				<?php
					echo esc_html__(
						'Related products',
						'woo-gutenberg-products-block'
					)
					?>
			</h2>
			<!-- /wp:heading -->

			<!-- wp:post-template {"className":"products-block-post-template","__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
			<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->

			<!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

			<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->

			<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:woocommerce/related-products -->
