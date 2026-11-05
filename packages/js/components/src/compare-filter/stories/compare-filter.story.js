/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CompareFilter } from '../';

const query = {};
const compareFilter = {
	param: 'product',
	getLabels() {
		return Promise.resolve( [] );
	},
	labels: {
		helpText: 'Select at least two products to compare',
		title: 'Compare Products',
		update: 'Compare',
	},
	searchProps: {
		type: 'products',
		placeholder: 'Search for products to compare',
	},
};

export const Basic = ( {
	path = new URL( document.location ).searchParams.get( 'path' ),
} ) => <CompareFilter path={ path } query={ query } { ...compareFilter } />;

export default {
	title: 'Components/CompareFilter',
	component: CompareFilter,
};
