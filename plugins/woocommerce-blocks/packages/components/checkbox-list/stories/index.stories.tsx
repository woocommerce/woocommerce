/**
 * External dependencies
 */
import type { Story, Meta } from '@storybook/react';
import { useArgs } from '@storybook/client-api';

/**
 * Internal dependencies
 */
import CheckboxList, { type CheckboxListProps } from '..';

export default {
	title: 'External Components/Checkbox List',
	component: CheckboxList,
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		isLoading: {
			table: {
				type: {
					summary: 'boolean',
				},
			},
			control: 'boolean',
			description: 'Is the component loading?',
		},
		isDisabled: {
			table: {
				type: {
					summary: 'boolean',
				},
			},
			type: 'boolean',
			control: 'boolean',
			description: 'Is the component disabled?',
		},
		limit: {
			type: 'number',
			table: {
				type: {
					summary: 'number',
				},
			},
			control: 'number',
			defaultValue: 2,
			description:
				'If there are more checkboxes than the limit + 5 then show a `show more` button.',
		},
		checked: {
			control: 'array',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description:
				'An array of strings containing the values of which checkboxes are checked',
		},
		options: {
			table: {
				type: {
					summary: 'array',
				},
			},
			description:
				'The list of options to show. This should be an array of objects containing a `label` and `value` property.',
			control: 'array',
		},
		onChange: {
			table: {
				type: {
					summary: 'function',
				},
			},
			action: 'toggled',
		},
	},
} as Meta< CheckboxListProps >;

const Template: Story< CheckboxListProps > = ( args ) => {
	const [ { checked, onChange: argsOnChange }, updateArgs ] = useArgs();
	const onChange = ( checkedOption: string ) => {
		argsOnChange( checkedOption );
		if ( checked?.includes( checkedOption ) ) {
			updateArgs( {
				checked: checked.filter(
					( option: string ) => option !== checkedOption
				),
			} );
			return;
		}
		checked.push( checkedOption );
		updateArgs( { ...args, checked } );
	};

	return <CheckboxList { ...args } onChange={ onChange } />;
};

export const Default = Template.bind( {} );
Default.args = {
	options: [
		{ label: '🍏 Apple', value: 'apple' },
		{ label: '🍌 Banana', value: 'banana' },
		{ label: '🍇 Grapes', value: 'grapes' },
		{ label: '🍍 Pineapple', value: 'pineapple' },
		{ label: '🍊 Orange', value: 'orange' },
		{ label: '🍉 Watermelon', value: 'watermelon' },
		{ label: '🍓 Strawberry', value: 'strawberry' },
		{ label: '🍑 Peach', value: 'peach' },
	],
	checked: [ 'apple' ],
};
