/**
 * External dependencies
 */
import { select, resolveSelect } from '@wordpress/data';
import type { Product } from '@woocommerce/data';

export default async function getRelatedProducts( productId: number ) {
	const { getEntityRecord } = select( 'core' );
	const product = getEntityRecord( 'postType', 'product', productId );
	if ( ! product ) {
		return;
	}

	const relatedProductIds = product?.related_ids;
	if ( ! relatedProductIds ) {
		return;
	}

	const { getEntityRecords } = resolveSelect( 'core' );
	return ( await getEntityRecords( 'postType', 'product', {
		include: relatedProductIds,
	} ) ) as Product[];
}
