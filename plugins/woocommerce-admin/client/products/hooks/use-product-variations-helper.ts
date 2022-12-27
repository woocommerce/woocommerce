/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import {
	Product,
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	PRODUCTS_STORE_NAME,
} from '@woocommerce/data';

export function useProductVariationsHelper() {
	const {
		generateProductVariations: _generateProductVariations,
		invalidateResolutionForStoreSelector,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
	const { createProduct, updateProduct } = useDispatch( PRODUCTS_STORE_NAME );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async ( product: Partial< Product > ) => {
			setIsGenerating( true );

			const createOrUpdateProduct = product.id
				? () =>
						updateProduct< Promise< Product > >(
							product.id,
							product
						)
				: () => {
						return createProduct< Promise< Product > >( {
							...product,
							status: 'auto-draft',
							name: product.name || 'AUTO-DRAFT',
						} );
				  };

			return createOrUpdateProduct()
				.then( ( createdOrUpdatedProduct ) => {
					return _generateProductVariations( {
						product_id: createdOrUpdatedProduct.id,
					} );
				} )
				.then( () => {
					return invalidateResolutionForStoreSelector(
						'getProductVariations'
					);
				} )
				.finally( () => {
					setIsGenerating( false );
				} );
		},
		[]
	);

	return {
		generateProductVariations,
		isGenerating,
	};
}
