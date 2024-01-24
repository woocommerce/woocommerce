/**
 * External dependencies
 */
import { select, resolveSelect } from '@wordpress/data';
import type { Product } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { GetSuggestedProductsOptions } from '../../store/data/types';
import { store as wooProductEditorDataStore } from '../../store/data/constants';

type getRelatedProductsOptions = {
	// If true, return random products if no related products are found.
	fallbackToRandomProducts?: boolean;
};

const POSTS_NUMBER_TO_RANDOMIZE = 30;
const POSTS_NUMBER_TO_PICK = 3;

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
};

type ProductTaxonomyItemProps = {
	id: number;
	name: string;
	slug: string;
};

export async function getSuggestedProductsFor( {
	postId,
	postType = 'product',
}: getSuggestedProductsForOptions ) {
	// @ts-expect-error There are no types for this.
	const { getEditedEntityRecord } = select( 'core' );

	const data: Product = getEditedEntityRecord( 'postType', postType, postId );

	const options = {
		categories: data?.categories
			? data.categories.map( ( cat: ProductTaxonomyItemProps ) => cat.id )
			: [],
		tags: data?.tags
			? data.tags.map( ( tag: ProductTaxonomyItemProps ) => tag.id )
			: [],
	};

	return await resolveSelect(
		wooProductEditorDataStore
	).getSuggestedProducts( options );
}
