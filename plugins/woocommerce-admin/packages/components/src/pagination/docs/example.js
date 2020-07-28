/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { Pagination } from '@woocommerce/components';

export default withState( {
	page: 2,
	perPage: 50,
} )( ( { page, perPage, setState } ) => (
	<Pagination
		page={ page }
		perPage={ perPage }
		total={ 500 }
		onPageChange={ ( newPage ) => setState( { page: newPage } ) }
		onPerPageChange={ ( newPerPage ) =>
			setState( { perPage: newPerPage } )
		}
	/>
) );
