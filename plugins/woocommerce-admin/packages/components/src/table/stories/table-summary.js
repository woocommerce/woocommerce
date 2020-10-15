/**
 * External dependencies
 */
import { TableSummary } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { summary } from './index';

export const Basic = () => <TableSummary data={ summary } />;

export default {
	title: 'WooCommerce Admin/components/TableSummary',
	component: TableSummary,
};
