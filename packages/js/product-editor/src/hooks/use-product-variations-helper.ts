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
	try {
		const { attributes } = await resolveSelect(
			'core'
		).getEntityRecord< Product >( 'postType', 'product', productId );
		const alreadyHasVariableAttribute = attributes.some(
			( attr ) => attr.variation
		);
		if ( ! alreadyHasVariableAttribute ) {
			return {};
		}
		const products = await resolveSelect(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		).getProductVariations< ProductVariation[] >( {
			product_id: productId,
			per_page: 1,
			has_price: true,
		} );
		if ( products && products.length > 0 && products[ 0 ].regular_price ) {
			return {
				regular_price: products[ 0 ].regular_price,
				stock_quantity: products[ 0 ].stock_quantity ?? undefined,
				stock_status: products[ 0 ].stock_status,
				manage_stock: products[ 0 ].manage_stock,
				low_stock_amount: products[ 0 ].low_stock_amount ?? undefined,
			};
		}
		return {};
	} catch {
		return {};
	}
}

export function useProductVariationsHelper() {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const {
		generateProductVariations: _generateProductVariations,
		invalidateResolutionForStore,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
	const { invalidateResolution: coreInvalidateResolution } =
		useDispatch( 'core' );

	const [ isGenerating, setIsGenerating ] = useState( false );

	const generateProductVariations = useCallback(
		async (
			attributes: EnhancedProductAttribute[],
			defaultAttributes?: ProductDefaultAttribute[]
		) => {
			setIsGenerating( true );

			const { status: lastStatus, variations } = await resolveSelect(
				'core'
			).getEditedEntityRecord< Product >(
				'postType',
				'product',
				productId
			);
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
					if ( variations && variations.length > 0 ) {
						for ( const variationId of variations ) {
							coreInvalidateResolution( 'getEntityRecord', [
								'postType',
								'product_variation',
								variationId,
							] );
						}
					}
					coreInvalidateResolution( 'getEntityRecord', [
						'postType',
						'product',
						productId,
					] );
					return invalidateResolutionForStore();
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
