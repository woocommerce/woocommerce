/**
 * External dependencies
 */
import type { Story, Meta } from '@storybook/react';
import { useArgs } from '@storybook/client-api';

/**
 * Internal dependencies
 */
import FormattedMonetaryAmount, { type FormattedMonetaryAmountProps } from '..';

export default {
	title: 'External Components/FormattedMonetaryAmount',
	component: FormattedMonetaryAmount,
	args: {
		displayType: 'text',
		value: 1234,
	},
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		currency: {
			table: {
				type: {
					summary: 'object',
				},
			},
			control: 'object',
			description:
				'The currency object used to describe how the monetary amount should be displayed.',
		},
		displayType: {
			table: {
				type: {
					summary: 'input | text',
				},
			},
			control: 'select',
			options: [ 'input', 'text' ],
			description:
				'Whether this should be an input or just a text display.',
		},
		value: {
			control: 'number',
			table: {
				type: {
					summary: 'number',
				},
			},
			description:
				'The raw value of the currency, it will be formatted depending on the props of this component.',
		},
		isAllowed: {
			control: 'function',
			table: {
				type: {
					summary: 'function',
				},
			},
			if: { arg: 'displayType', eq: 'input' },
			description:
				'A checker function to validate the input value. If this function returns false, the onChange method will not get triggered and the input value will not change.',
		},
		allowNegative: {
			control: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'Whether negative numbers can be entered',
		},
		onValueChange: {
			table: {
				type: {
					summary: 'function',
				},
			},
			action: 'changed',
		},
		style: {
			control: 'object',
			table: {
				type: {
					summary: 'object',
				},
			},
		},
	},
} as Meta< FormattedMonetaryAmountProps >;

const Template: Story< FormattedMonetaryAmountProps > = ( args ) => {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const [ _, updateArgs ] = useArgs();
	const onValueChange = ( unit: number ) => {
		updateArgs( { value: unit } );
	};

	return (
		<FormattedMonetaryAmount { ...args } onValueChange={ onValueChange } />
	);
};

export const Default = Template.bind( {} );
Default.args = {
	currency: {
		minorUnit: 2,
		code: 'USD',
		decimalSeparator: '.',
		prefix: '$',
		suffix: '',
		symbol: '$',
		thousandSeparator: ',',
	},
};
