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
	findProductInList,
	getProductWithUpdatedVariation,
	getProductVariationUpdatePath,
	isProductVariation,
} from './utils';

type ProductVariationEntityRecord = ProductEntityRecord & {
	parent_id: number;
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

function getOriginalProduct(
	products: ProductEntityRecord[],
	productId: number
) {
	return (
		findProductInList( products, productId ) ??
		( select( coreStore ).getEntityRecord(
			'root',
			'product',
			productId
		) as ProductEntityRecord | undefined )
	);
}

function restoreUnselectedVariationEdits(
	productId: number,
	products: ProductEntityRecord[],
	savedVariationIds: Set< number >,
	editEntityRecord: EditProductRecord
) {
	const editedProduct = getEditedProduct( productId );
	const editedVariations = editedProduct?._embedded?.variations ?? [];

	if ( ! editedProduct || editedVariations.length === 0 ) {
		return;
	}

	const originalProduct = getOriginalProduct( products, productId );
	const originalVariationsById = new Map(
		( originalProduct?._embedded?.variations ?? [] ).map( ( variation ) => [
			variation.id,
			variation,
		] )
	);
	const restoredVariations = editedVariations.map( ( variation ) => {
		if ( savedVariationIds.has( variation.id ) ) {
			return variation;
		}

		return originalVariationsById.get( variation.id ) ?? variation;
	} );
	const hasRestoredVariation = restoredVariations.some(
		( variation, index ) => variation !== editedVariations[ index ]
	);

	if ( ! hasRestoredVariation ) {
		return;
	}

	editEntityRecord(
		'root',
		'product',
		editedProduct.id,
		{
			_embedded: {
				...editedProduct._embedded,
				variations: restoredVariations,
			},
		},
		{
			undoIgnore: true,
		}
	);
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
		data: editedVariation,
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
	products,
	selectedProducts,
	editEntityRecord,
	saveEditedEntityRecord,
}: {
	products: ProductEntityRecord[];
	selectedProducts: ProductEntityRecord[];
	editEntityRecord: EditProductRecord;
	saveEditedEntityRecord: SaveEditedProductRecord;
} ) {
	const savedVariationIds = new Set< number >();
	const selectedVariations = selectedProducts.filter( isProductVariation );
	const productIdsToSave = new Set(
		selectedProducts
			.filter( ( product ) => ! isProductVariation( product ) )
			.map( ( product ) => product.id )
	);
	const variationResults = await Promise.allSettled(
		selectedVariations.map( ( product ) =>
			saveVariation( product, editEntityRecord )
		)
	);

	variationResults.forEach( ( result, index ) => {
		if ( result.status === 'fulfilled' ) {
			savedVariationIds.add( selectedVariations[ index ].id );
			productIdsToSave.add( selectedVariations[ index ].parent_id );
		}
	} );

	const productSaveIds = Array.from( productIdsToSave );
	const productSaveResults = await Promise.allSettled(
		productSaveIds.map( ( productId ) => {
			restoreUnselectedVariationEdits(
				productId,
				products,
				savedVariationIds,
				editEntityRecord
			);

			return saveEditedEntityRecord( 'root', 'product', productId, {
				throwOnError: true,
			} );
		} )
	);

	return getSelectedProductSaveResults(
		selectedProducts,
		selectedVariations,
		variationResults,
		productSaveIds,
		productSaveResults
	);
}
