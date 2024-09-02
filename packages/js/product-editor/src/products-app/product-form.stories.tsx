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
	ProductForm,
	PRODUCTS_DATA,
} from './utilites/storybook';

export default {
	title: 'Product App/Product Form',
	component: ProductForm,
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - improve typing.
const Template = ( args: unknown ) => <ProductForm { ...args } />;

export const Default = Template.bind( {} );

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
Default.args = {
	productData: PRODUCTS_DATA[ 0 ],
	fields: PRODUCT_FIELDS,
	form: {
		type: 'regular',
		fields: PRODUCT_FIELDS_KEYS,
	},
};
