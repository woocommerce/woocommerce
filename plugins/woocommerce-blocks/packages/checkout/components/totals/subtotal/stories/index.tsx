/**
 * External dependencies
 */
import { Story, Meta } from '@storybook/react';
import { currencyControl } from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import Subtotal, { SubtotalProps } from '..';

export default {
	title: 'WooCommerce Blocks/Checkout Blocks/totals/Subtotal',
	component: Subtotal,
	argTypes: {
		currency: currencyControl,
	},
	args: {
		values: {
			total_items: '1000',
			total_items_tax: '200',
		},
	},
} as Meta< SubtotalProps >;

const Template: Story< SubtotalProps > = ( args ) => <Subtotal { ...args } />;

export const Default = Template.bind( {} );
Default.args = {};

// @todo Revise Storybook entries for `Checkout Blocks/totals` components
