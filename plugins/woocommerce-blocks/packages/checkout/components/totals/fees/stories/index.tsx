/**
 * External dependencies
 */
import { Story, Meta } from '@storybook/react';
import {
	currenciesAPIShape as currencies,
	currencyControl,
} from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import Fees, { TotalsFeesProps } from '..';

export default {
	title: 'WooCommerce Blocks/Checkout Blocks/totals/Fees',
	component: Fees,
	argTypes: {
		currency: currencyControl,
	},
	args: {
		cartFees: [
			{
				id: 'my-id',
				name: 'Storybook fee',
				totals: {
					...currencies.USD,
					total: '1000',
					total_tax: '200',
				},
			},
		],
	},
} as Meta< TotalsFeesProps >;

const Template: Story< TotalsFeesProps > = ( args ) => <Fees { ...args } />;

export const Default = Template.bind( {} );
Default.args = {};

// @todo Revise Storybook entries for `Checkout Blocks/totals` components
