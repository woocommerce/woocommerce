/**
 * External dependencies
 */
import { Card } from '@wordpress/components';
import { Table } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { rows, headers } from './index';

export const Basic = () => (
	<Card size={ null }>
		<Table
			caption="Revenue last week"
			rows={ rows }
			headers={ headers }
			rowKey={ ( row ) => row[ 0 ].value }
		/>
	</Card>
);

export const NoDataCustomMessage = () => (
	<Card size={ null }>
		<Table
			caption="Revenue last week"
			rows={ [] }
			headers={ headers }
			rowKey={ ( row ) => row[ 0 ].value }
			emptyMessage="Custom empty message"
		/>
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/Table',
	component: Table,
};
