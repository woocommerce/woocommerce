/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react-webpack5';

/**
 * Internal dependencies
 */
import ProductName, { ProductNameProps } from '..';

export default {
	title: 'Base Components/ProductName',
	component: ProductName,
	args: {
		name: 'Test product',
		permalink: '#',
	},
	disabledTagName: 'span',
} as Meta< ProductNameProps >;

const Template: StoryFn< ProductNameProps > = ( args ) => (
	<ProductName { ...args } />
);

export const Default = Template.bind( {} );
Default.args = {
	disabled: false,
};

export const DisabledProduct = Template.bind( {} );
DisabledProduct.args = {
	disabled: true,
};
