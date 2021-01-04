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
		<Table caption="Revenue Last Week" rows={ rows } headers={ headers } />
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/Table',
	component: Table,
};
