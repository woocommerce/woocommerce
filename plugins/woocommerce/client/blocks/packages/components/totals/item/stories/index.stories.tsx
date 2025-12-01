/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { currencies, currencyControl } from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import Item, { TotalsItemProps } from '..';

export default {
	title: 'External Components/Totals/Item',
	component: Item,
	argTypes: {
		currency: currencyControl,
		description: { control: { type: 'text' } },
	},
	args: {
		description: 'This item is so interesting',
		label: 'Interesting item',
		value: 2000,
	},
} as Meta< TotalsItemProps >;

const Template: StoryFn< TotalsItemProps > = ( args ) => <Item { ...args } />;

export const Default: StoryFn< TotalsItemProps > = Template.bind( {} );
Default.args = {
	currency: currencies.USD,
	description: 'This item is so interesting',
};
