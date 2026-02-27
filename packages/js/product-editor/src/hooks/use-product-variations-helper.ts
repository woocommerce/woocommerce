/**
 * External dependencies
 */
import { dispatch, resolveSelect, useSelect } from '@wordpress/data';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { getNewPath, getPath, navigateTo } from '@woocommerce/navigation';
import {
	Product,
	ProductDefaultAttribute,
	ProductVariation,
	experimentalProductVariationsStore,
} from '@woocommerce/data';
import { applyFilters } from '@wordpress/hooks';
import {
	useEntityProp,
	useEntityRecord,
	store as coreStore,
} from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from './use-product-attributes';

async function getDefaultVariationValues(
	productId: number
): Promise< Partial< Omit< ProductVariation, 'id' > > > {
	try {
		const product = ( await resolveSelect( 'core' ).getEntityRecord(
			'postType',
			'product',
			productId
		) ) as Product;
		if ( ! product ) {
			return {};
		}

		const { attributes } = product;

		const alreadyHasVariableAttribute = attributes.some(
			( attr ) => attr.variation
		);
		if ( ! alreadyHasVariableAttribute ) {
			return {};
		}

		const products = await resolveSelect(
			experimentalProductVariationsStore
		).getProductVariations( {
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

	const { editedRecord: product } = useEntityRecord< Product >(
		'postType',
		'product',
		productId
	);

	const [ _isGenerating, setIsGenerating ] = useState( false );

	const { isGeneratingVariations, generateError } = useSelect(
		( select ) => {
			const {
				isGeneratingVariations: getIsGeneratingVariations,
				generateProductVariationsError,
			} = select( experimentalProductVariationsStore );

			return {
				isGeneratingVariations: getIsGeneratingVariations( {
					product_id: productId,
				} ),
				generateError: generateProductVariationsError( {
					product_id: productId,
				} ),
			};
		},
		[ productId ]
	);

	const isGenerating = useMemo(
		() => _isGenerating || Boolean( isGeneratingVariations ),
		[ _isGenerating, isGeneratingVariations ]
	);

	const generateProductVariations = useCallback( async function onGenerate(
		attributes: EnhancedProductAttribute[],
		defaultAttributes?: ProductDefaultAttribute[]
	) {
		setIsGenerating( true );

		const { status: lastStatus, variations } = ( await resolveSelect(
			'core'
		).getEditedEntityRecord(
			'postType',
			'product',
			productId
		) ) as unknown as Product;
		const hasVariableAttribute = attributes.some(
			( attr ) => attr.variation
		);

		const defaultVariationValues = await getDefaultVariationValues(
			productId
		);

		await Promise.all(
			variations.map( ( variationId: number ) =>
				dispatch( coreStore ).invalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variationId,
				] )
			)
		);
		await dispatch(
			experimentalProductVariationsStore
		).invalidateResolutionForStore();
		/**
		 * Filters the meta_data array for generated variations.
		 *
		 * @filter woocommerce.product.variations.generate.meta_data
		 * @param {Object} product Main product object.
		 * @return {Object} meta_data array for variations.
		 */
		const meta_data = applyFilters(
			'woocommerce.product.variations.generate.meta_data',
			[],
			product
		);

		return dispatch( experimentalProductVariationsStore )
			.generateProductVariations(
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
					meta_data: meta_data as Product[ 'meta_data' ],
				}
			)
			.then( async ( response ) => {
				await dispatch( coreStore ).invalidateResolution(
					'getEntityRecord',
					[ 'postType', 'product', productId ]
				);

				await resolveSelect( coreStore ).getEntityRecord(
					'postType',
					'product',
					productId
				);

				await dispatch(
					experimentalProductVariationsStore
				).invalidateResolutionForStore();

				return response;
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
	[] );

	return {
		generateProductVariations,
		isGenerating,
		generateError,
	};
}
