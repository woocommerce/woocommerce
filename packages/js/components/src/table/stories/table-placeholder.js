/**
 * External dependencies
 */
import { Card } from '@wordpress/components';
import { TablePlaceholder } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { headers } from './index';

export const Basic = () => (
	<Card size={ null }>
		<TablePlaceholder caption="Revenue last week" headers={ headers } />
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/TablePlaceholder',
	component: TablePlaceholder,
};
