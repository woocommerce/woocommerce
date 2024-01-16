/**
 * External dependencies
 */
import { select, resolveSelect, dispatch } from '@wordpress/data';
import type { Product } from '@woocommerce/data';

type getRelatedProductsOptions = {
	// If true, return random products if no related products are found.
	fallbackToRandomProducts?: boolean;
};

const POSTS_NUMBER_TO_RANDOMIZE = 30;
const POSTS_NUMBER_TO_PICK = 5;

/**
 * Return related products for a given product ID.
 * If fallbackToRandomProducts is true,
 * return random products if no related products are found.
 *
 * @param {number}                    productId - Product ID.
 * @param {getRelatedProductsOptions} options   - Options.
 * @return {Promise<Product[] | undefined>} Related products.
 */
export default async function getRelatedProducts(
	productId: number,
	options: getRelatedProductsOptions = {}
): Promise< Product[] | undefined > {
	const { getEntityRecord } = select( 'core' );
	const product = getEntityRecord( 'postType', 'product', productId );
	if ( ! product ) {
		return;
	}

	let relatedProductIds = product?.related_ids?.sort(); // sort to ensure cache key is consistent
	if ( ! relatedProductIds?.length ) {
		if ( ! options?.fallbackToRandomProducts ) {
			return;
		}

		// Pick the last `POSTS_NUMBER_TO_RANDOMIZE` products
		const relatedProducts = ( await resolveSelect(
			'core'
		).getEntityRecords( 'postType', 'product', {
			_fields: [ 'id' ],
			per_page: POSTS_NUMBER_TO_RANDOMIZE,
		} ) ) as Product[];

		if ( ! relatedProducts?.length ) {
			return;
		}

		relatedProductIds = relatedProducts.map( ( post ) => post.id );

		// Pick POSTS_NUMBER_TO_PICK random post IDs
		relatedProductIds = relatedProductIds
			.sort( () => Math.random() - 0.5 )
			.slice( 0, POSTS_NUMBER_TO_PICK );
	}

	return ( await resolveSelect( 'core' ).getEntityRecords(
		'postType',
		'product',
		{
			include: relatedProductIds,
		}
	) ) as Product[];
}

/**
 * Invalidate the cache for related products.
 * This is used when a product is updated, to ensure the related products
 * are updated in the cache.
 * By convention, this function and getRelatedProducts() sort the related product IDs
 * to ensure the cache key is consistent.
 *
 * @param {number} productId - The ID of the product to invalidate related products for.
 * @return {Promise} A promise that resolves when the cache has been invalidated.
 */
export async function invalidateRelatedProductsResolution(
	productId: number
): Promise< void > {
	const {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore No types for this exist yet.
		// eslint-disable-next-line @woocommerce/dependency-group
		invalidateResolution,
	} = dispatch( 'core' );

	const relatedProducts = await getRelatedProducts( productId );
	const productsIds =
		relatedProducts?.map( ( product ) => product.id )?.sort() || []; // sort to ensure cache key is consistent

	return invalidateResolution( 'getEntityRecords', [
		'postType',
		'product',
		{ include: productsIds },
	] );
}
