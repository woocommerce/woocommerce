# woocommerce/product-taxonomy-field block

This is a block that displays a taxonomy field, allowing searching, selection, and creation of new items, to be used in a product context.

Please note that to use this block you need to have the custom taxonomy registered in the backend, attached to the products post type and added to the REST API. Here's a snippet that shows how to add an already registered taxonomy to the REST API:

```php
function YOUR_PREFIX_rest_api_prepare_custom1_to_product( $response, $post ) {
	$post_id = $post->get_id();

	if ( empty( $response->data[ 'custom1' ] ) ) {
		$terms = [];

		foreach ( wp_get_post_terms( $post_id, 'custom-taxonomy' ) as $term ) {
			$terms[] = [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			];
		}

		$response->data[ 'custom1' ] = $terms;
	}

	return $response;
}

add_filter( 'woocommerce_rest_prepare_product_object', 'YOUR_PREFIX_rest_api_prepare_custom1_to_product', 10, 2 );

function YOUR_PREFIX_rest_api_add_custom1_to_product( $product, $request, $creating = true ) {
	$product_id = $product->get_id();
	$params     = $request->get_params();
	$custom1s     = isset( $params['custom1'] ) ? $params['custom1'] : array();

	if ( ! empty( $custom1s ) ) {
		if ( $custom1s[0]['id'] ) {
			$custom1s = array_map(
				function ( $custom1 ) {
					return absint( $custom1['id'] );
				},
				$custom1s
			);
		} else {
			$custom1s = array_map( 'absint', $custom1s );
		}
		wp_set_object_terms( $product_id, $custom1s, 'custom-taxonomy' );
	}
}

add_filter( 'woocommerce_rest_insert_product_object', 'YOUR_PREFIX_rest_api_add_custom1_to_product', 10, 3 );
```
