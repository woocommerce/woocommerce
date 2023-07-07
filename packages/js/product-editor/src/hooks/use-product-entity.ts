/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

export function useProductEntity() {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const product: Product = useSelect( ( select ) =>
		select( 'core' ).getEntityRecord( 'postType', 'product', productId )
	);

	return product;
}
