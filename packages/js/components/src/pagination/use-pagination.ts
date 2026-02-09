/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

export type usePaginationProps = {
	totalCount: number;
	defaultPerPage?: number;
	onPageChange?: ( page: number ) => void;
	onPerPageChange?: ( page: number ) => void;
};

export function usePagination( {
	totalCount,
	defaultPerPage = 25,
	onPageChange,
	onPerPageChange,
}: usePaginationProps ) {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ perPage, setPerPage ] = useState( defaultPerPage );

	const pageCount = Math.ceil( totalCount / perPage );
	const start = perPage * ( currentPage - 1 ) + 1;
	const end = Math.min( perPage * currentPage, totalCount );

	return {
		start,
		end,
		currentPage,
		perPage,
		pageCount,
		setCurrentPage: ( newPage: number ) => {
			setCurrentPage( newPage );
			if ( onPageChange ) {
				onPageChange( newPage );
			}
		},
		setPerPageChange: ( newPerPage: number ) => {
			setCurrentPage( 1 );
			setPerPage( newPerPage );
			if ( onPerPageChange ) {
				onPerPageChange( newPerPage );
			}
		},
	};
}
