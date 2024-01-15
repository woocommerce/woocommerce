/**
 * External dependencies
 */
import { select, resolveSelect } from '@wordpress/data';
import type { Product } from '@woocommerce/data';

type getRelatedProductsOptions = {
	// If true, return random products if no related products are found.
	fallbackToRandomProducts?: boolean;
};

const POSTS_NUMBER_TO_RANDOMIZE = 30;

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

		// Pick five random post IDs
		relatedProductIds = lastPostIds
			?.sort( () => Math.random() - 0.5 )
			.slice( 0, 5 );
	}

	return ( await resolveSelect( 'core' ).getEntityRecords(
		'postType',
		'product',
		{
			include: relatedProductIds,
		}
	) ) as Product[];
}
