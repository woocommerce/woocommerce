/* eslint-disable @typescript-eslint/ban-types */
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { createElement } from '@wordpress/element';
import { ProductForm, PRODUCTS_DATA } from '../../utilites/storybook';

export default {
	title: 'Data Field Controls/Slug',
	component: ProductForm,
};

const Template = () => (
	<ProductForm
		productData={ PRODUCTS_DATA[ 0 ] }
		fields={ [
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
		] }
		form={ {
			type: 'regular',
		} }
	/>
);

export const Default = Template.bind( {} );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - TS doesn't like the fact that we're not passing any args
Default.args = {};
