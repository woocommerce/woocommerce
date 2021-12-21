/**
 * External dependencies
 */
import { Story, Meta } from '@storybook/react';
import { currencyControl } from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import Item, { TotalsItemProps } from '..';

export default {
	title: 'WooCommerce Blocks/Checkout Blocks/totals/Item',
	component: Item,
	argTypes: {
		currency: currencyControl,
		description: { control: { type: 'text' } },
	},
	args: {
		description: (
			<span>
				This item is <strong>so interesting</strong>
			</span>
		),
		label: 'Intersting item',
		value: 2000,
	},
} as Meta< TotalsItemProps >;

const Template: Story< TotalsItemProps > = ( args ) => <Item { ...args } />;

export const Default = Template.bind( {} );
Default.args = {};

// @todo Revise Storybook entries for `Checkout Blocks/totals` components
