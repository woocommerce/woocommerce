/**
 * External dependencies
 */
import { Card, TablePlaceholder } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { headers } from './index';

export const Basic = () => (
	<Card className="woocommerce-analytics__card">
		<TablePlaceholder caption="Revenue Last Week" headers={ headers } />
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/TablePlaceholder',
	component: TablePlaceholder,
};
