/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';
import { useArgs } from '@storybook/preview-api';

/**
 * Internal dependencies
 */
import RadioControl from '..';
import { RadioControlProps } from '../types';

export default {
	title: 'External Components/RadioControl',
	component: RadioControl,
	argTypes: {
		className: {
			control: 'text',
			description: 'Additional class name to give to the radio control.',
		},
		id: {
			control: 'text',
			description: 'Unique ID for the radio control.',
		},
		selected: {
			control: 'text',
			description: 'The selected value.',
		},
		onChange: {
			control: 'function',
			description:
				'Function called when the value changes. This is passed to each option and executed when that option is selected.',
		},
		options: {
			control: 'array',
			description: 'Options for the radio control.',
		},
		disabled: {
			control: 'boolean',
			description: 'Whether the radio control is disabled or not.',
		},
	},
} as Meta< RadioControlProps >;

const Template: StoryFn< RadioControlProps > = ( args ) => {
	const [ { selected }, updateArgs ] = useArgs();
	return (
		<RadioControl
			{ ...args }
			selected={ selected }
			onChange={ ( value ) => {
				updateArgs( { selected: value } );
			} }
		/>
	);
};

export const Default = Template.bind( {} );
Default.args = {
	options: [
		{
			label: 'apple',
			value: '🍏 Apple',
		},
		{
			label: 'banana',
			value: '🍌 Banana',
		},
		{
			label: 'orange',
			value: '🍊 Orange',
		},
		{
			label: 'pear',
			value: '🍐 Pear',
		},
		{
			label: 'pineapple',
			value: '🍍 Pineapple',
		},
		{
			label: 'strawberry',
			value: '🍓 Strawberry',
		},
		{
			label: 'watermelon',
			value: '🍉 Watermelon',
		},
	],
};
