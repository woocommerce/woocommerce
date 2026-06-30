/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { store as coreStore } from '@wordpress/core-data';
import { select } from '@wordpress/data';
import type { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { normalizeVariation } from '../variation-view/normalization';
import {
	getProductWithUpdatedVariation,
	getProductVariationUpdatePath,
	isProductVariation,
} from './utils';

type ProductVariationEntityRecord = ProductEntityRecord & {
	parent_id: number;
};

type ProductVariationSaveData = Omit<
	Partial< ProductEntityRecord >,
	'images'
> & {
	image?:
		| NonNullable< ProductVariation[ 'image' ] >
		| Record< string, never >;
};

type ProductSaveResult = PromiseSettledResult<
	ProductEntityRecord | ProductVariation
>;

type EditProductRecord = (
	kind: 'root',
	name: 'product',
	recordId: number,
	edits: Partial< ProductEntityRecord >,
	options?: { undoIgnore?: boolean }
) => void;

type SaveEditedProductRecord = (
	kind: 'root',
	name: 'product',
	recordId: number,
	options: { throwOnError: true }
) => Promise< ProductEntityRecord >;

function getEditedProduct( productId: number ) {
	const product = select( coreStore ).getEditedEntityRecord(
		'root',
		'product',
		productId
	) as ProductEntityRecord | false | undefined;

	return product !== false ? product : undefined;
}

function getVariationImageSaveData(
	image: ProductEntityRecord[ 'images' ][ number ] | undefined
) {
	if ( ! image ) {
		return {};
	}

	const { thumbnail, ...variationImage } = image;

	return variationImage;
}

function getVariationSaveData(
	variation: ProductEntityRecord
): ProductVariationSaveData {
	const { images, cost_of_goods_sold: costOfGoodsSold, ...data } = variation;
	const hasNullCostOfGoodsSoldValue = costOfGoodsSold?.values?.some(
		( value ) => value.defined_value === null
	);

	return {
		...data,
		...( ! hasNullCostOfGoodsSoldValue && costOfGoodsSold !== undefined
			? { cost_of_goods_sold: costOfGoodsSold }
			: {} ),
		image: getVariationImageSaveData( images?.[ 0 ] ),
	};
}

async function saveVariation(
	product: ProductVariationEntityRecord,
	editEntityRecord: EditProductRecord
) {
	const parentProduct = getEditedProduct( product.parent_id );
	const editedVariation =
		parentProduct?._embedded?.variations?.find(
			( variation ) => variation.id === product.id
		) ?? product;
	const savedVariation = await apiFetch< ProductVariation >( {
		path: getProductVariationUpdatePath( product ),
		method: 'PUT',
		data: getVariationSaveData( editedVariation ),
	} );

	if ( parentProduct ) {
		const updatedParentProduct = getProductWithUpdatedVariation(
			parentProduct,
			normalizeVariation(
				savedVariation
			) as unknown as ProductEntityRecord
		);

		editEntityRecord(
			'root',
			'product',
			parentProduct.id,
			{
				_embedded: updatedParentProduct._embedded,
			},
			{
				undoIgnore: true,
			}
		);
	}

	return savedVariation;
}

async function saveVariationsSequentially(
	selectedVariations: ProductVariationEntityRecord[],
	editEntityRecord: EditProductRecord
) {
	const variationResults: ProductSaveResult[] = [];

	for ( const product of selectedVariations ) {
		try {
			// Save variations one at a time because saveVariation merges each
			// saved variation into the parent product's current embedded
			// variations. Concurrent saves can merge against stale parent
			// snapshots and overwrite another variation's just-saved update.
			variationResults.push( {
				status: 'fulfilled',
				value: await saveVariation( product, editEntityRecord ),
			} );
		} catch ( error ) {
			variationResults.push( {
				status: 'rejected',
				reason: error,
			} );
		}
	}

	return variationResults;
}

function getSelectedProductSaveResults(
	selectedProducts: ProductEntityRecord[],
	selectedVariations: ProductVariationEntityRecord[],
	variationResults: ProductSaveResult[],
	productSaveIds: number[],
	productSaveResults: ProductSaveResult[]
) {
	const missingSaveResult: PromiseRejectedResult = {
		status: 'rejected',
		reason: new Error( 'Product save result is missing.' ),
	};
	const productSaveResultsById = new Map(
		productSaveIds.map( ( productId, index ) => [
			productId,
			productSaveResults[ index ] ?? missingSaveResult,
		] )
	);
	const variationResultsById = new Map(
		selectedVariations.map( ( product, index ) => [
			product.id,
			variationResults[ index ] ?? missingSaveResult,
		] )
	);

	return selectedProducts.map( ( product ) => {
		if ( ! isProductVariation( product ) ) {
			return (
				productSaveResultsById.get( product.id ) ?? missingSaveResult
			);
		}

		const variationResult = variationResultsById.get( product.id );

		if ( variationResult?.status === 'rejected' ) {
			return variationResult;
		}

		return (
			productSaveResultsById.get( product.parent_id ) ?? missingSaveResult
		);
	} );
}

export async function saveSelectedProducts( {
	selectedProducts,
	editEntityRecord,
	saveEditedEntityRecord,
}: {
	selectedProducts: ProductEntityRecord[];
	editEntityRecord: EditProductRecord;
	saveEditedEntityRecord: SaveEditedProductRecord;
} ) {
	const selectedVariations = selectedProducts.filter( isProductVariation );
	const productIdsToSave = new Set(
		selectedProducts
			.filter( ( product ) => ! isProductVariation( product ) )
			.map( ( product ) => product.id )
	);
	const variationResults = await saveVariationsSequentially(
		selectedVariations,
		editEntityRecord
	);

	variationResults.forEach( ( result, index ) => {
		if ( result.status === 'fulfilled' ) {
			productIdsToSave.add( selectedVariations[ index ].parent_id );
		}
	} );

	const productSaveIds = Array.from( productIdsToSave );
	const productSaveResults = await Promise.allSettled(
		productSaveIds.map( ( productId ) =>
			saveEditedEntityRecord( 'root', 'product', productId, {
				throwOnError: true,
			} )
		)
	);

	return getSelectedProductSaveResults(
		selectedProducts,
		selectedVariations,
		variationResults,
		productSaveIds,
		productSaveResults
	);
}
