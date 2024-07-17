/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';
import { useArgs } from '@storybook/preview-api';

/**
 * Internal dependencies
 */
import { SortSelect, type SortSelectProps } from '..';

export default {
	title: 'External Components/SortSelect',
	component: SortSelect,
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description: 'Additional class names to add to the textarea.',
		},
		value: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description: 'The selected value.',
		},
		label: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description: 'Label for the select.',
		},
		readOnly: {
			control: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'Whether the textarea is disabled/read-only.',
		},
		onChange: {
			action: 'onChange',
			table: {
				type: {
					summary: 'function',
				},
			},
			description: 'Function to call on the change event.',
		},
		screenReaderLabel: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description: 'Hidden text to be read by a screen reader.',
		},
		options: {
			control: 'object',
			table: {
				type: {
					summary: '{ key: string; label: string }[]',
				},
			},
			description:
				'The placeholder text to show when no value has been entered.',
		},
	},
} as Meta< SortSelectProps >;

const Template: StoryFn< SortSelectProps > = ( args ) => {
	const [ { value }, updateArgs ] = useArgs();
	return (
		<SortSelect
			{ ...args }
			value={ value }
			onChange={ ( e ) => {
				updateArgs( { value: e.target.value } );
			} }
		/>
	);
};

export const Default = Template.bind( {} );
Default.args = {
	label: 'Choose one of the options',
	options: [
		{
			key: 'apple',
			label: '🍏 Apple',
		},
		{
			key: 'banana',
			label: '🍌 Banana',
		},
		{
			key: 'orange',
			label: '🍊 Orange',
		},
		{
			key: 'pear',
			label: '🍐 Pear',
		},
		{
			key: 'pineapple',
			label: '🍍 Pineapple',
		},
		{
			key: 'strawberry',
			label: '🍓 Strawberry',
		},
		{
			key: 'watermelon',
			label: '🍉 Watermelon',
		},
	],
	screenReaderLabel: 'Invisible text that is read by a screen-reader.',
	value: 'apple',
	readOnly: false,
};
