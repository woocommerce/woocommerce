/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import { ProductPostType } from './types';

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
					isDeleted: false,
					isLoading: false,
				};
			}

			const {
				getEntityRecord,
				getEntityRecords,
				hasFinishedResolution,
				getLastEntitySaveError,
			} = selectFunc( coreDataStore );

			if ( itemType === BLOCK_NAMES.featuredProduct ) {
				const productArgs: [ string, string, number ] = [
					'postType',
					'product',
					itemId,
				];
				const product: ProductPostType | undefined = getEntityRecord(
					...productArgs
				);

				const saveError = getLastEntitySaveError( ...productArgs );
				const isResolved = hasFinishedResolution(
					'getEntityRecord',
					productArgs
				);

				if ( saveError && isResolved ) {
					return {
						status: 'deleted',
						isDeleted: true,
						isLoading: false,
					};
				}

				return {
					status:
						product?.status ?? ( isResolved ? 'deleted' : null ),
					isDeleted: ! product,
					isLoading: ! isResolved,
				};
			}

			if ( itemType === BLOCK_NAMES.featuredCategory ) {
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
				isDeleted: null,
				isLoading: false,
			};
		},
		[ itemId, itemType ]
	);
};
