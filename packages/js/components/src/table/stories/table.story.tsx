/**
 * External dependencies
 */
import { Card } from '@wordpress/components';
import { Table } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { rows, headers } from './index';

export const Basic = () => (
	<Card size="none">
		<Table
			caption="Revenue last week"
			rows={ rows }
			headers={ headers }
			rowKey={ ( row ) => row[ 0 ].value }
		/>
	</Card>
);

export const NoDataCustomMessage = () => {
	return (
		<Card size="none">
			<Table
				caption="Revenue last week"
				rows={ [] }
				headers={ headers }
				rowKey={ ( row ) => row[ 0 ].value }
				emptyMessage="Custom empty message"
			/>
		</Card>
	);
};

export default {
	title: 'Components/Table',
	component: Table,
};
