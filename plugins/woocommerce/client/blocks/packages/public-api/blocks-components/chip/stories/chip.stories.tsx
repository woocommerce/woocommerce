/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';

/**
 * Internal dependencies
 */
import Chip, { ChipProps } from '../chip';
const availableElements = [ 'li', 'div', 'span' ];
const availableRadii = [ 'none', 'small', 'medium', 'large' ];

export default {
	title: 'External Components/Chip',
	component: Chip,
	argTypes: {
		element: {
			control: 'radio',
			options: availableElements,
		},
		className: {
			control: 'text',
		},
		radius: {
			control: 'radio',
			options: availableRadii,
		},
	},
} as Meta< ChipProps >;

const Template: StoryFn< ChipProps > = ( args ) => <Chip { ...args } />;

export const Default: StoryFn< ChipProps > = Template.bind( {} );
Default.args = {
	element: 'li',
	text: 'Take me to the casino!',
	screenReaderText: "I'm a chip, me",
};
