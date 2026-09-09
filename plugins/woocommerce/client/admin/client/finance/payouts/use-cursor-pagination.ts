/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';

export type CursorPaginationState = {
	page: number;
	perPage: number;
	// cursors[ i ] is the cursor that fetches page i + 1; the first page has no cursor.
	cursors: ( string | null )[];
};

/**
 * Map DataViews' page-number pagination onto the API's opaque cursors.
 *
 * DataViews can only request pages we hold a cursor for, plus the page after the current one
 * once its cursor arrives, so `totalPages` never claims a page that cannot be fetched.
 *
 * @param state         The pagination state.
 * @param state.page    The current page number.
 * @param state.perPage The page size.
 * @param state.cursors The cursors of the known pages.
 * @param itemCount     The number of items on the current page, or null while unknown.
 * @param hasMore       Whether the API reported more items after the current page.
 */
export const getPaginationInfo = (
	{ page, perPage, cursors }: CursorPaginationState,
	itemCount: number | null,
	hasMore: boolean
): { totalItems: number; totalPages: number } => {
	if ( itemCount === null ) {
		return { totalItems: 0, totalPages: 0 };
	}

	return {
		totalItems: ( page - 1 ) * perPage + itemCount + ( hasMore ? 1 : 0 ),
		totalPages: Math.max( cursors.length, hasMore ? page + 1 : page ),
	};
};

export function useCursorPagination( initialPerPage: number ) {
	const [ state, setState ] = useState< CursorPaginationState >( {
		page: 1,
		perPage: initialPerPage,
		cursors: [ null ],
	} );

	const reset = useCallback( ( perPage?: number ) => {
		setState( ( prev ) => ( {
			page: 1,
			perPage: perPage ?? prev.perPage,
			cursors: [ null ],
		} ) );
	}, [] );

	// Store the cursor of the page after the current one, once per page.
	const registerNext = useCallback( ( nextCursor: string | null ) => {
		setState( ( prev ) => {
			if ( ! nextCursor || prev.cursors.length !== prev.page ) {
				return prev;
			}
			return { ...prev, cursors: [ ...prev.cursors, nextCursor ] };
		} );
	}, [] );

	const knownPages = state.cursors.length;
	const goToPage = useCallback(
		( page: number ): boolean => {
			if ( page < 1 || page > knownPages ) {
				return false;
			}
			setState( ( prev ) => ( { ...prev, page } ) );
			return true;
		},
		[ knownPages ]
	);

	return {
		page: state.page,
		perPage: state.perPage,
		cursor: state.cursors[ state.page - 1 ] ?? null,
		state,
		reset,
		registerNext,
		goToPage,
	};
}
