/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	PRODUCT_FIELDS,
	PRODUCT_FIELDS_KEYS,
	PRODUCTS_DATA,
	ProductsView,
} from './utilites/storybook';

export default {
	title: 'Product App/Products View',
	component: ProductsView,
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
const Template = ( args: unknown ) => <ProductsView { ...args } />;

export const Default = Template.bind( {} );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
Default.args = {
	productsData: PRODUCTS_DATA,
	fields: PRODUCT_FIELDS,
	view: {
		type: 'list',
		fields: PRODUCT_FIELDS_KEYS.filter(
			( field ) =>
				field !== 'downloads' &&
				field !== 'categories' &&
				field !== 'images'
		),
	},
	defaultLayouts: {
		list: {
			type: 'list',
		},
	},
	paginationInfo: {
		totalPages: 1,
		totalItems: 10,
	},
	onChangeView: () => {},
};
