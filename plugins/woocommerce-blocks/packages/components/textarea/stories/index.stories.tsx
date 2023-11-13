/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';
import { useArgs } from '@storybook/preview-api';

/**
 * Internal dependencies
 */
import Textarea, { type TextareaProps } from '..';
import '../style.scss';

export default {
	title: 'Checkout Components/Textarea',
	component: Textarea,
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
			description: 'The value in the textarea.',
		},
		disabled: {
			control: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'Whether the textarea is disabled.',
		},
		placeholder: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description:
				'The placeholder text to show when no value has been entered.',
		},
	},
} as Meta< TextareaProps >;

const Template: StoryFn< TextareaProps > = ( args ) => {
	const [ { value }, updateArgs ] = useArgs();
	return (
		<Textarea
			{ ...args }
			value={ value }
			onTextChange={ ( newValue: string ) =>
				updateArgs( { value: newValue } )
			}
		/>
	);
};

export const Default = Template.bind( {} );

Default.args = {
	className: '',
	disabled: false,
	placeholder: 'Enter some text here',
	value: '',
};
