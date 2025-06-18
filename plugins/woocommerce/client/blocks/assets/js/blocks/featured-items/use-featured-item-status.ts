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
			const featuredItemStatus: FeaturedItemReturnType = {
				status: null,
				isDeleted: null,
				isLoading: true,
			};

			if ( ! itemId ) return { ...featuredItemStatus, isLoading: false };

			const { getEntityRecord, getEntityRecords, hasFinishedResolution } =
				selectFunc( coreDataStore );

			const productArgs = [ 'postType', 'product', itemId ];
			const categoryArgs = [
				'taxonomy',
				'product_cat',
				{ per_page: -1, include: [ itemId ] },
			];

			const activeProduct =
				itemType === BLOCK_NAMES.featuredProduct &&
				getEntityRecord( ...productArgs );

			const isCategoryDeleted =
				itemType === BLOCK_NAMES.featuredCategory &&
				! getEntityRecords( ...categoryArgs )?.length;

			if ( itemType === BLOCK_NAMES.featuredProduct ) {
				if ( ! activeProduct ) {
					featuredItemStatus.isDeleted = true;
				} else {
					featuredItemStatus.status = activeProduct?.status;
					featuredItemStatus.isDeleted = false;
					featuredItemStatus.isLoading = ! hasFinishedResolution(
						'getEntityRecord',
						productArgs
					);
				}
			}

			if ( itemType === BLOCK_NAMES.featuredCategory ) {
				if ( isCategoryDeleted ) {
					featuredItemStatus.isDeleted = true;
				} else {
					featuredItemStatus.isDeleted = false;
					featuredItemStatus.isLoading = ! hasFinishedResolution(
						'getEntityRecords',
						categoryArgs
					);
				}
			}

			return featuredItemStatus;
		},
		[ itemId ]
	);
};
