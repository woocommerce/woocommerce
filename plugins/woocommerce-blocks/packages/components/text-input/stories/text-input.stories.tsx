/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { action } from '@storybook/addon-actions';
import { useArgs } from '@storybook/client-api';

/**
 * Internal dependencies
 */
import TextInput, { type TextInputProps } from '../text-input';
import '../style.scss';
import '../../validation-input-error/style.scss';

export default {
	title: 'External Components/TextInput',
	component: TextInput,
	parameters: {
		actions: {
			handles: [ 'blur', 'change' ],
		},
	},
	argTypes: {
		ariaDescribedBy: {
			type: 'string',
			control: 'text',
			description:
				'The aria-describedby attribute to set on the input element',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		ariaLabel: {
			type: 'string',
			control: 'text',
			description: 'The aria-label attribute to set on the input element',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		autoComplete: {
			type: 'string',
			control: 'text',
			description:
				'The [autocomplete property](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete) to pass to the underlying HTMl input.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		feedback: {
			control: 'null',
			description:
				'Element shown after the input. Used by `ValidatedTextInput` to show the error message.',
			table: {
				type: {
					summary: 'JSX.Element',
				},
			},
		},
		id: {
			type: 'string',
			control: 'text',
			description: 'ID for the element.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		label: {
			type: 'string',
			control: 'text',
			description: 'Label for the input.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		onBlur: {
			type: 'function',
			table: {
				type: {
					summary: 'function',
				},
			},
			description: 'Function to run when the input is blurred.',
		},
		onChange: {
			type: 'function',
			table: {
				type: {
					summary: 'function',
				},
			},
			description: "Function to run when the input's value changes.",
		},
		screenReaderLabel: {
			type: 'string',
			table: {
				type: {
					summary: 'string',
				},
			},
			description:
				'The label to be read by screen readers, if this prop is undefined, the `label` prop will be used instead',
		},
		title: {
			type: 'string',
			control: 'text',
			description: 'Title of the input.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		type: {
			type: 'string',
			control: 'text',
			description: 'The type attribute to set on the input element.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		value: {
			type: 'string',
			control: 'text',
			description: 'The value of the element.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
	},
} as Meta< TextInputProps >;

const Template: StoryFn< TextInputProps > = ( args ) => {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const [ _, updateArgs ] = useArgs();
	const onChange = ( value: string ) => {
		action( 'change' )( value || '' );
		updateArgs( { value } );
	};

	return <TextInput { ...args } onChange={ onChange } />;
};

export const Default = Template.bind( {} );
Default.args = {
	id: 'unique-id',
	label: 'Enter your value',
	value: 'Lorem ipsum',
	feedback: (
		<div className="wc-block-components-validation-error">
			<p>
				This is a feedback element, usually it would be used as an error
				message.
			</p>
		</div>
	),
};
