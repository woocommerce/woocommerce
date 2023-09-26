/**
 * External dependencies
 */
import { TableSummary } from '@woocommerce/components';
import { Card, CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { summary } from './index';

export const Basic = () => (
	<Card>
		<CardFooter justify="center">
			<TableSummary data={ summary } />
		</CardFooter>
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/TableSummary',
	component: TableSummary,
};
