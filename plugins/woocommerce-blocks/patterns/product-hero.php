<?php
/**
 * Title: Product Hero
 * Slug: woocommerce-blocks/product-hero
 * Categories: WooCommerce
 */

$query = new \WC_Product_Query(
	array(
		'limit'  => 1,
		'return' => 'ids',
		'status' => array( 'publish' ),
	)
);

$products   = $query->get_products();
$product_id = $products ? $products[0] : null;
?>

<!-- wp:woocommerce/single-product {"productId":<?php echo esc_attr( $product_id ); ?>,"align":"wide"} -->
<div class="wp-block-woocommerce-single-product alignwide">
	<!-- wp:columns {"style":{"color":{"background":"#6b7ba8"},"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"padding":{"left":"20px"}}},"textColor":"base"} -->
	<div class="wp-block-columns has-base-color has-text-color has-background has-link-color" style="background-color:#6b7ba8;padding-left:20px">
		<!-- wp:column {"width":"40%","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
		<div class="wp-block-column" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:40%">
			<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfSingleProductBlock":true,"height":"300px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"1.5em","bottom":"1.5em"}}}} -->
		<div class="wp-block-column" style="padding-top:1.5em;padding-bottom:1.5em">
			<!-- wp:post-title {"textAlign":"","isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"400"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"fontSize":"large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

			<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductBlock":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"16px","lineHeight":"0.1"}}} /-->

			<!-- wp:woocommerce/product-button {"style":{"color":{"background":"#000001","text":"#fffff1"},"spacing":{"padding":{"left":"30px","right":"30px"},"margin":{"top":"15px"}},"typography":{"fontSize":"15px"}}} -->
			<div class="wp-block-woocommerce-product-button is-loading" style="font-size:15px"></div>
			<!-- /wp:woocommerce/product-button -->

			<!-- wp:post-excerpt {"style":{"typography":{"fontSize":"14px"}},"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:woocommerce/single-product -->
