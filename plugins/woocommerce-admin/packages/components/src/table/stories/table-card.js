/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { headers, rows, summary } from './index';

const TableCardExample = withState( {
	query: {
		paged: 1,
	},
} )( ( { query, setState } ) => (
	<TableCard
		title="Revenue last week"
		rows={ rows }
		headers={ headers }
		onQueryChange={ ( param ) => ( value ) =>
			setState( {
				query: {
					[ param ]: value,
				},
			} ) }
		query={ query }
		rowsPerPage={ 7 }
		totalRows={ 10 }
		summary={ summary }
	/>
) );

export const Basic = () => <TableCardExample />;

export default {
	title: 'WooCommerce Admin/components/TableCard',
	component: TableCard,
};
