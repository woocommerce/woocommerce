/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';
import { useArgs } from '@storybook/preview-api';

/**
 * Internal dependencies
 */
import { RadioControlAccordion, RadioControlAccordionProps } from '..';

export default {
	title: 'External Components/RadioControlAccordion',
	component: RadioControlAccordion,
	argTypes: {
		className: {
			control: 'text',
			description: 'Additional class name to give to the radio control.',
		},
		instanceId: {
			control: 'number',
			description:
				'If you have multiple accordions on the page, this unique number keeps track of each one.',
		},
		id: {
			control: 'string',
			description: 'Unique ID for the accordion',
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
	},
} as Meta< RadioControlAccordionProps >;

const Template: StoryFn< RadioControlAccordionProps > = ( args ) => {
	const [ { selected }, updateArgs ] = useArgs();
	return (
		<RadioControlAccordion
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
			name: 'apple',
			content: <p>🍏 More apples</p>,
			label: 'Apple',
			value: 'apple',
		},
		{
			name: 'banana',
			content: <p>🍌 More Bananas</p>,
			label: 'Banana',
			value: 'banana',
		},
		{
			name: 'orange',
			content: <p>🍊 More Oranges</p>,
			label: 'Orange',
			value: 'orange',
		},
		{
			name: 'pear',
			content: <p>🍐 More Pears</p>,
			label: 'Pear',
			value: 'pear',
		},
		{
			name: 'pineapple',
			content: <p>🍍 More Pineapples</p>,
			label: 'Pineapple',
			value: 'pineapple',
		},
		{
			name: 'strawberry',
			content: <p>🍓 More Strawberries</p>,
			label: 'Strawberry',
			value: 'strawberry',
		},
		{
			name: 'watermelon',
			content: <p>🍉 More Watermelon</p>,
			label: 'Watermelon',
			value: 'watermelon',
		},
	],
};
