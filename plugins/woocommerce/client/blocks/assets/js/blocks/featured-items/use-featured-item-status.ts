/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';

interface FeaturedItemProps {
	itemId: number | undefined;
	itemType: string;
}

interface FeaturedItemReturnType {
	status: string | null;
	isDeleted: boolean | null;
	isLoading: boolean;
}

export const useFeaturedItemStatus = ( {
	itemId,
	itemType,
}: FeaturedItemProps ): FeaturedItemReturnType => {
	return useSelect(
		( selectFunc ) => {
			if ( ! itemId ) {
				return {
					status: null,
					isDeleted: null,
					isLoading: false,
				};
			}

			const { getEntityRecord, getEntityRecords, hasFinishedResolution } =
				selectFunc( coreDataStore );

			if ( itemType === BLOCK_NAMES.featuredProduct ) {
				const productArgs = [ 'postType', 'product', itemId ];
				const product = getEntityRecord( ...productArgs );
				const isResolved = hasFinishedResolution(
					'getEntityRecord',
					productArgs
				);

				return {
					status: product?.status ?? 'deleted',
					isDeleted: ! product,
					isLoading: ! isResolved,
				};
			}

			if ( itemType === BLOCK_NAMES.featuredCategory ) {
				const categoryArgs = [
					'taxonomy',
					'product_cat',
					{ per_page: -1, include: [ itemId ] },
				];
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
				isDeleted: null,
				isLoading: false,
			};
		},
		[ itemId, itemType ]
	);
};
