/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { store as coreDataStore, Page } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { formatPageToItem } from './utils';

/**
 * The useItems hook is used to get all page items.
 *
 * @param exclude - The items to exclude from the search results.
 * @return The page items and a boolean indicating if the items are loading.
 */
export const useItems = ( exclude?: string[] ) => {
	return useSelect(
		( select ) => {
			const query = {
				status: [ 'publish', 'private', 'draft' ],
				...( exclude ? { exclude } : {} ),
			};

			const args: [ 'postType', 'page', { status: string[] } ] = [
				'postType',
				'page',
				query,
			];

			const allPages =
				( select( coreDataStore ).getEntityRecords(
					...args
				) as Page[] ) || null;

			return {
				items: allPages?.map( formatPageToItem ) || [],
				isFetching: ! select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					args
				),
			};
		},
		[ exclude ]
	);
};

/**
 * The useSelectedItem hook is used to get the selected page item.
 *
 * @param value - The value of the selected page item.
 * @return The selected page item and a boolean indicating if the page is loading.
 */
export const useSelectedItem = ( value: string ) => {
	const { selectedPage, isLoading } = useSelect(
		( select ) => {
			if ( ! value ) {
				return { selectedPage: null, isLoading: false };
			}

			const { getEntityRecord, hasFinishedResolution } =
				select( coreDataStore );

			const args: [ 'postType', 'page', string ] = [
				'postType',
				'page',
				value,
			];

			return {
				selectedPage: getEntityRecord< Page >( ...args ),
				isLoading: ! hasFinishedResolution( 'getEntityRecord', args ),
			};
		},
		[ value ]
	);

	const selectedItem = useMemo(
		() => ( selectedPage ? formatPageToItem( selectedPage ) : null ),
		[ selectedPage ]
	);

	return {
		selectedItem,
		isLoading,
	};
};
