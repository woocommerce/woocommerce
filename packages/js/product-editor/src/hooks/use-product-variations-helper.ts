/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useCallback, useState } from '@wordpress/element';
import {
	Product,
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from './use-product-attributes';

export function useProductVariationsHelper() {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const { saveEntityRecord } = useDispatch( 'core' );
	const {
		generateProductVariations: _generateProductVariations,
		invalidateResolutionForStoreSelector,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async ( attributes: EnhancedProductAttribute[] ) => {
			setIsGenerating( true );

			const updateProductAttributes = async () => {
				const hasVariableAttribute = attributes.some(
					( attr ) => attr.variation
				);
				await saveEntityRecord< Promise< Product > >(
					'postType',
					'product',
					{
						id: productId,
						type: hasVariableAttribute ? 'variable' : 'simple',
						attributes,
					}
				);
			};

			return updateProductAttributes()
				.then( () => {
					return _generateProductVariations< { count: number } >( {
						product_id: productId,
					} );
				} )
				.then( ( data ) => {
					if ( data.count > 0 ) {
						invalidateResolutionForStoreSelector(
							'getProductVariations'
						);
						return invalidateResolutionForStoreSelector(
							'getProductVariationsTotalCount'
						);
					}
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
