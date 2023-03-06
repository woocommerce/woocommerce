/**
 * External dependencies
 */

import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Pagination from '../';

export default {
	title: 'WooCommerce Admin/components/Pagination',
	component: Pagination,
	args: {
		total: 500,
		showPagePicker: true,
		showPerPagePicker: true,
		showPageArrowsLabel: true,
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
