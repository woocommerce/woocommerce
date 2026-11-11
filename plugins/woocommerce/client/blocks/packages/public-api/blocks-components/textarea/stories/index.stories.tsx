/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react-webpack5';
import { useArgs } from 'storybook/preview-api';
import { action } from 'storybook/actions';

/**
 * Internal dependencies
 */
import Textarea, { type TextareaProps } from '..';
import '../style.scss';

export default {
	title: 'External Components/Textarea',
	component: Textarea,
	parameters: {
		docs: {
			description: {
				component:
					'Textarea accepts standard React textarea attributes and event handlers, including onBlur, onFocus, name, required, rows, and aria-* and data-* attributes. Continue to use value and onTextChange to control the text. onTextChange receives the new string value; an optional onChange handler receives the React change event and runs after onTextChange.',
			},
		},
	},
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
		onTextChange: {
			control: false,
			description:
				'Called with the new string value when the text changes.',
		},
		onChange: {
			control: false,
			description:
				'Called with the React change event after onTextChange, if provided.',
		},
		onBlur: {
			control: false,
			description:
				'Called with the React focus event when the textarea loses focus.',
		},
		rows: {
			control: 'number',
			description: 'Number of visible text rows. Defaults to 2.',
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

export const Default: StoryFn< TextareaProps > = Template.bind( {} );

Default.args = {
	className: '',
	disabled: false,
	placeholder: 'Enter some text here',
	value: '',
};

export const NativeProps: StoryFn< TextareaProps > = Template.bind( {} );

NativeProps.args = {
	...Default.args,
	id: 'order-notes',
	name: 'order_notes',
	'aria-label': 'Order notes',
	required: true,
	maxLength: 100,
	rows: 4,
	onBlur: action( 'onBlur' ),
	onChange: action( 'onChange' ),
};
