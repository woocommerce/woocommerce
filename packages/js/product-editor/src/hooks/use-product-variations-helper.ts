/**
 * External dependencies
 */
import { resolveSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useCallback, useState } from '@wordpress/element';
import { getNewPath, getPath, navigateTo } from '@woocommerce/navigation';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
	ProductDefaultAttribute,
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
	const {
		generateProductVariations: _generateProductVariations,
		invalidateResolutionForStoreSelector,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async (
			attributes: EnhancedProductAttribute[],
			defaultAttributes?: ProductDefaultAttribute[]
		) => {
			setIsGenerating( true );

			const lastStatus = (
				( await resolveSelect( 'core' ).getEditedEntityRecord(
					'postType',
					'product',
					productId
				) ) as Product
			 ).status;
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
					default_attributes: defaultAttributes,
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
					if (
						lastStatus === 'auto-draft' &&
						getPath().endsWith( 'add-product' )
					) {
						const url = getNewPath( {}, `/product/${ productId }` );
						navigateTo( { url } );
					}
				} );
		},
		[]
	);

	return {
		generateProductVariations,
		isGenerating,
	};
}
