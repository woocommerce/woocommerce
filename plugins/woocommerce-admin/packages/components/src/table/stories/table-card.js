/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { headers, rows, summary } from './index';

const TableCardExample = () => {
	const [ { query }, setState ] = useState( {
		query: {
			paged: 1,
		},
	} );
	return (
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
	);
};

export const Basic = () => <TableCardExample />;

export default {
	title: 'WooCommerce Admin/components/TableCard',
	component: TableCard,
};
