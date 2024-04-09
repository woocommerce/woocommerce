/**
 * External dependencies
 */
import { select, resolveSelect, dispatch } from '@wordpress/data';
import { PRODUCTS_STORE_NAME } from '@woocommerce/data';
import type { Product } from '@woocommerce/data';

type getRelatedProductsOptions = {
	// If true, return random products if no related products are found.
	fallbackToRandomProducts?: boolean;
};

const POSTS_NUMBER_TO_RANDOMIZE = 30;
const POSTS_NUMBER_TO_PICK = 3;
const POSTS_NUMBER_TO_DISPLAY = 4;

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

	let relatedProductIds = product?.related_ids;
	if ( ! relatedProductIds?.length ) {
		if ( ! options?.fallbackToRandomProducts ) {
			return;
		}

		// Pick the last `POSTS_NUMBER_TO_RANDOMIZE` posts
		const lastPost = ( await resolveSelect( 'core' ).getEntityRecords(
			'postType',
			'product',
			{
				_fields: [ 'id' ],
				per_page: POSTS_NUMBER_TO_RANDOMIZE,
			}
		) ) as Product[];

		if ( ! lastPost?.length ) {
			return;
		}

		const lastPostIds = lastPost.map( ( post ) => post.id );

		// Pick POSTS_NUMBER_TO_PICK random post IDs
		relatedProductIds = lastPostIds
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

type getSuggestedProductsForOptions = {
	postId: number;
	postType?: 'product' | 'post' | 'page';
	forceRequest?: boolean;
	exclude?: number[];
};

/**
 * Get suggested products for a given post ID.
 *
 * @param { getSuggestedProductsForOptions } options - Options.
 * @return { Promise<Product[] | undefined> } Suggested products.
 */
export async function getSuggestedProductsFor( {
	postId,
	postType = 'product',
	forceRequest = false,
	exclude = [],
}: getSuggestedProductsForOptions ): Promise< Product[] | undefined > {
	// @ts-expect-error There are no types for this.
	const { getEditedEntityRecord } = select( 'core' );

	const data: Product = getEditedEntityRecord( 'postType', postType, postId );

	const options = {
		categories: data?.categories
			? data.categories.map( ( cat ) => cat.id )
			: [],
		tags: data?.tags ? data.tags.map( ( tag ) => tag.id ) : [],
		exclude: exclude?.length ? exclude : [ postId ],
		limit: POSTS_NUMBER_TO_DISPLAY,
	};

	if ( forceRequest ) {
		await dispatch( PRODUCTS_STORE_NAME ).invalidateResolution(
			'getSuggestedProducts',
			[ options ]
		);
	}

	return await resolveSelect( PRODUCTS_STORE_NAME ).getSuggestedProducts(
		options
	);
}
