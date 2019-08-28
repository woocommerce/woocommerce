/** @format */
/**
 * Internal dependencies
 */
import { Pagination } from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

export default withState( {
	page: 2,
	perPage: 50,
} )( ( { page, perPage, setState } ) => (
	<Pagination
		page={ page }
		perPage={ perPage }
		total={ 500 }
		onPageChange={ ( newPage ) => setState( { page: newPage } ) }
		onPerPageChange={ ( newPerPage ) => setState( { perPage: newPerPage } ) }
	/>
) );
