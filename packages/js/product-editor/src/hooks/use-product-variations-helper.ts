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
	ProductVariation,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from './use-product-attributes';

async function getDefaultVariationValues(
	productId: number
): Promise< Partial< Omit< ProductVariation, 'id' > > > {
	const attributes = (
		( await resolveSelect( 'core' ).getEntityRecord(
			'postType',
			'product',
			productId
		) ) as Product
	 ).attributes;
	const alreadyHasVariableAttribute = attributes.some(
		( attr ) => attr.variation
	);
	if ( ! alreadyHasVariableAttribute ) {
		return {};
	}
	try {
		const productsWithPrice = await resolveSelect(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		).getProductVariations< Pick< ProductVariation, 'regular_price' >[] >( {
			product_id: productId,
			per_page: 1,
			has_price: true,
			_fields: [ 'regular_price' ],
		} );
		if ( productsWithPrice && productsWithPrice.length > 0 ) {
			return {
				regular_price: productsWithPrice[ 0 ].regular_price,
			};
		}
	} catch {
		return {};
	}
	return {};
}

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

			const defaultVariationValues = await getDefaultVariationValues(
				productId
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
					default_values: defaultVariationValues,
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
