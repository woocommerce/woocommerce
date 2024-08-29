/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductForm, PRODUCTS_DATA } from '../../utilites/storybook';

export default {
	title: 'Data Field Controls/Slug',
	component: ProductForm,
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - TS doesn't like the fact that we're not passing any args to the Template
const Template = ( args: unknown ) => <ProductForm { ...args } />;

export const Default = Template.bind( {} );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - TS doesn't like the fact that we're not passing any args
Default.args = {
	productData: PRODUCTS_DATA[ 0 ],
	fields: [
		{
			label: 'Slug',
			id: 'slug',
			type: 'text',
		},
		{
			label: 'Text',
			id: 'text',
			type: 'text',
		},
	],
	form: {
		type: 'regular',
		fields: [ 'slug', 'text' ],
	},
};
