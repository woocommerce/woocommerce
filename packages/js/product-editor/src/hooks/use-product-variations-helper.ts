/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useCallback, useState } from '@wordpress/element';
import { EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME } from '@woocommerce/data';

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
	const {
		generateProductVariations: _generateProductVariations,
		invalidateResolutionForStoreSelector,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async ( attributes: EnhancedProductAttribute[] ) => {
			setIsGenerating( true );

			const hasVariableAttribute = attributes.some(
				( attr ) => attr.variation
			);

			return _generateProductVariations< {
				count: number;
				deleted_count: number;
			} >(
				{
					product_id: productId,
				},
				{
					type: hasVariableAttribute ? 'variable' : 'simple',
					attributes,
				},
				{
					delete: true,
				}
			)
				.then( () => {
					invalidateResolutionForStoreSelector(
						'getProductVariations'
					);
					return invalidateResolutionForStoreSelector(
						'getProductVariationsTotalCount'
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
