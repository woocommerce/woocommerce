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
	const { images, ...data } = variation;

	return {
		...data,
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

	// Update the parent's embedded variations in the store so that subsequent
	// variation saves in the same batch read the correct parent state, not a
	// stale snapshot that would overwrite the previous save's changes.
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

		// Variations are saved directly via apiFetch (not via saveEditedEntityRecord
		// on the parent), so the variation result is the authoritative outcome.
		return variationResultsById.get( product.id ) ?? missingSaveResult;
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

	// Do NOT add parent product IDs to productIdsToSave after variation saves.
	// Variations are already persisted via direct apiFetch above. Calling
	// saveEditedEntityRecord for the parent would overwrite the entity record
	// base state with a server response that lacks _embedded.variations, causing
	// the drawer to show stale data when reopened. The caller is responsible for
	// invalidating the entity records cache after this function resolves.

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
