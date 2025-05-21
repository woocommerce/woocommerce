/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';

/**
 * Internal dependencies
 */
import { RemovableChip, RemovableChipProps } from '../removable-chip';

const availableElements = [ 'li', 'div', 'span' ];

export default {
	title: 'External Components/RemovableChip',
	component: RemovableChip,
	argTypes: {
		element: {
			control: 'radio',
			options: availableElements,
		},
	},
} as Meta< RemovableChipProps >;

const Template: StoryFn< RemovableChipProps > = ( args ) => (
	<RemovableChip { ...args } />
);

export const Default: StoryFn< RemovableChipProps > = Template.bind( {} );
Default.args = {
	element: 'li',
	text: 'Take me to the casino',
	screenReaderText: "I'm a removable chip, me",
};
