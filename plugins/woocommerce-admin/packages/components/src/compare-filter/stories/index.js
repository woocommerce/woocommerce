/**
 * External dependencies
 */
import { CompareFilter } from '@woocommerce/components';

const path = new URL( document.location ).searchParams.get( 'path' );
const query = {};
const compareFilter = {
	type: 'products',
	param: 'product',
	getLabels() {
		return Promise.resolve( [] );
	},
	labels: {
		helpText: 'Select at least two products to compare',
		placeholder: 'Search for products to compare',
		title: 'Compare Products',
		update: 'Compare',
	},
};

export const Basic = () => (
	<CompareFilter path={ path } query={ query } { ...compareFilter } />
);

export default {
	title: 'WooCommerce Admin/components/CompareFilter',
	component: CompareFilter,
};
