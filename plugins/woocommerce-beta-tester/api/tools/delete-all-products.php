<?php
register_woocommerce_admin_test_helper_rest_route(
	'/tools/delete-all-products/v1',
	'tools_delete_all_products'
);

function tools_delete_all_products() {
	$query    = new \WC_Product_Query();
	$products = $query->get_products();

	foreach ( $products as $product ) {
		$product->delete( true );
	}

	return true;
}
