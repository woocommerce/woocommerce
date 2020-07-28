/**
 * External dependencies
 */
import { number, boolean } from '@storybook/addon-knobs';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Pagination from '../';

export default {
	title: 'WooCommerce Admin/components/Pagination',
	component: Pagination,
};

export const Default = () => {
	const [ statePage, setPage ] = useState( 2 );
	const [ statePerPage, setPerPage ] = useState( 50 );

	return (
		<Pagination
			page={ statePage }
			perPage={ statePerPage }
			total={ number( 'Total', 500 ) }
			showPagePicker={ boolean( 'Page Picker', true ) }
			showPerPagePicker={ boolean( 'Per Page Picker', true ) }
			showPageArrowsLabel={ boolean( 'Page Arrows Label', true ) }
			onPageChange={ ( newPage ) => setPage( newPage ) }
			onPerPageChange={ ( newPerPage ) => setPerPage( newPerPage ) }
		/>
	);
};
