/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { productsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';

interface UseFeaturedItemProps {
	itemId: number | undefined;
	itemType: string;
}

interface UseFeaturedItemReturnType {
	status: string | null;
	isDeleted: boolean | null;
	isLoading: boolean;
}

export const useFeaturedItemStatus = ( {
	itemId,
	itemType,
}: UseFeaturedItemProps ): UseFeaturedItemReturnType => {
	return useSelect(
		( selectFunc ) => {
			if ( ! itemId ) {
				return {
					status: null,
					isDeleted: true,
					isLoading: false,
				};
			}

			if ( itemType === BLOCK_NAMES.featuredProduct ) {
				const { getProduct, hasFinishedResolution } =
					selectFunc( productsStore );

				const product = getProduct( itemId );
				const isLoading = ! hasFinishedResolution( 'getProduct', [
					itemId,
				] );

				// An item is considered deleted if its status is 'trash' or if the
				// API request has finished and returned no product.
				const isDeleted =
					product?.status === 'trash' || ( ! isLoading && ! product );

				return {
					status: product?.status ?? null,
					isDeleted,
					isLoading,
				};
			}

			if ( itemType === BLOCK_NAMES.featuredCategory ) {
				const { getEntityRecords, hasFinishedResolution } =
					selectFunc( coreDataStore );
				const categoryArgs: [ string, string, { include: number[] } ] =
					[ 'taxonomy', 'product_cat', { include: [ itemId ] } ];
				const categories = getEntityRecords( ...categoryArgs );
				const isResolved = hasFinishedResolution(
					'getEntityRecords',
					categoryArgs
				);
				const isDeleted = ! categories?.length;

				return {
					status: isDeleted ? 'deleted' : null,
					isDeleted,
					isLoading: ! isResolved,
				};
			}

			// Default fallback (if itemType doesn't match any expected value)
			return {
				status: null,
				isDeleted: true,
				isLoading: false,
			};
		},
		[ itemId, itemType ]
	);
};
