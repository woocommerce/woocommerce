/**
 * External dependencies
 */

import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	Pagination,
	PaginationPageArrowsWithPicker,
	usePagination,
	PaginationPageSizePicker,
} from '../';

export default {
	title: 'WooCommerce Admin/components/Pagination',
	component: Pagination,
	args: {
		total: 500,
		showPagePicker: true,
		showPerPagePicker: true,
		showPageArrowsLabel: true,
	},
	argTypes: {
		onPageChange: { action: 'onPageChange' },
		onPerPageChange: { action: 'onPerPageChange' },
	},
};

export const Default = ( args ) => {
	const [ statePage, setPage ] = useState( 2 );
	const [ statePerPage, setPerPage ] = useState( 50 );

	return (
		<Pagination
			page={ statePage }
			perPage={ statePerPage }
			onPageChange={ ( newPage ) => setPage( newPage ) }
			onPerPageChange={ ( newPerPage ) => setPerPage( newPerPage ) }
			{ ...args }
		/>
	);
};

export const CustomWithHook = ( args ) => {
	const paginationProps = usePagination( {
		totalCount: args.total,
		defaultPerPage: 25,
		onPageChange: args.onPageChange,
		onPerPageChange: args.onPerPageChange,
	} );

	return (
		<div>
			<div>
				Viewing { paginationProps.start }-{ paginationProps.end } of{ ' ' }
				{ args.total } items
			</div>
			<PaginationPageArrowsWithPicker { ...paginationProps } />
			<PaginationPageSizePicker
				{ ...paginationProps }
				total={ args.total }
				perPageOptions={ [ 5, 10, 25 ] }
				label=""
			/>
		</div>
	);
};
