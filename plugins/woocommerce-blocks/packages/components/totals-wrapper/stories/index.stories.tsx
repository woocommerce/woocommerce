/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import TotalsWrapper, { TotalsWrapperProps } from '..';

export default {
	title: 'External Components/Totals/TotalsWrapper',
	component: TotalsWrapper,
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		children: {
			control: null,
			table: {
				type: {
					summary: 'React.Children',
				},
			},
		},
		slotWrapper: {
			control: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description:
				'True if this `TotalsWrapper` is being used to wrap a Slot/Fill',
		},
	},
} as Meta< TotalsWrapperProps >;
const Template: StoryFn< TotalsWrapperProps > = ( args ) => {
	return (
		<TotalsWrapper { ...args }>
			<div>Custom totals content</div>
		</TotalsWrapper>
	);
};

export const Default = Template.bind( {} );
Default.args = {};
