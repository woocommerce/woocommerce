/**
 * External dependencies
 */
import { Card, CardFooter } from '@wordpress/components';
import { TableSummaryPlaceholder } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

export const Basic = () => {
	return (
		<Card>
			{ /* @ts-expect-error: justify is missing from the latest type def. */ }
			<CardFooter justify="center">
				<TableSummaryPlaceholder />
			</CardFooter>
		</Card>
	);
};

export default {
	title: 'WooCommerce Admin/components/TableSummaryPlaceholder',
	component: TableSummaryPlaceholder,
};
