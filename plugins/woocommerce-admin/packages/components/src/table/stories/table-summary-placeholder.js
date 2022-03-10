/**
 * External dependencies
 */
import { Card, CardFooter } from '@wordpress/components';
import { TableSummaryPlaceholder } from '@woocommerce/components';

export const Basic = () => (
	<Card>
		<CardFooter justify="center">
			<TableSummaryPlaceholder />
		</CardFooter>
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/TableSummaryPlaceholder',
	component: TableSummaryPlaceholder,
};
