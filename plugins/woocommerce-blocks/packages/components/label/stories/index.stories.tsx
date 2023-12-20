/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import Label, { LabelProps } from '..';

export default {
	title: 'External Components/Label',
	component: Label,
	argTypes: {
		label: {
			control: {
				type: 'text',
			},
			description: 'The label to be displayed.',
		},
		screenReaderLabel: {
			control: {
				type: 'text',
			},
			description: 'The label to be read by screen readers.',
		},
		wrapperElement: {
			control: {
				type: 'text',
			},
			description:
				'HTML element to wrap around the label component (e.g. "span").',
		},
		wrapperProps: {
			control: {
				type: 'object',
			},
			description: 'The props to be passed to the wrapper element.',
		},
	},
} as Meta< LabelProps >;

const Template: StoryFn = ( args ) => <Label { ...args } />;

export const Default = Template.bind( {} );
Default.args = {
	label: 'I am a label',
	screenReaderLabel: 'I am a screen reader label',
	wrapperElement: 'span',
	wrapperProps: {},
};
