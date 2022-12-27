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
	const { updateProduct } = useDispatch( PRODUCTS_STORE_NAME );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async ( product: Partial< Product > ) => {
			setIsGenerating( true );
			return updateProduct< Promise< Product > >( product.id, product )
				.then( () => {
					return _generateProductVariations( {
						product_id: product.id,
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
