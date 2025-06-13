/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { action } from '@storybook/addon-actions';
import { useArgs } from '@storybook/client-api';
import { useDispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ValidatedTextInput from '../validated-text-input';
import '../style.scss';
import '../../validation-input-error/style.scss';
import { ValidatedTextInputProps } from '../types';

export default {
	title: 'External Components/ValidatedTextInput',
	component: ValidatedTextInput,
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
		customFormatter: {
			control: 'function',
			table: {
				type: {
					summary: 'function',
				},
			},
		},
		customValidation: {
			control: 'function',
			table: {
				type: {
					summary: 'function',
				},
			},
			description:
				'A custom validation function that is run on change. Use `setCustomValidity` to set an error message.',
		},
		errorMessage: {
			type: 'string',
			control: 'text',
			description:
				'If supplied, this error will be used rather than the `message` from the validation data store.',
		},
		focusOnMount: {
			type: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'If true, the input will be focused on mount.',
		},
		help: {
			type: 'string',
			description: 'Help text to show alongside the input.',
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
		instanceId: {
			type: 'string',
			control: 'text',
			description:
				'Unique instance ID. id will be used instead if provided.',
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
		showError: {
			type: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'If true, validation errors will be shown.',
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
		validateOnMount: {
			type: 'boolean',
			control: 'boolean',
			description:
				'If the field should perform validation when mounted, as opposed to waiting for a change or blur event.',
			table: {
				type: {
					summary: 'boolean',
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
} as Meta< ValidatedTextInputProps >;

const Template: StoryFn< ValidatedTextInputProps > = ( args ) => {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const [ _, updateArgs ] = useArgs();
	const getFormattedValue = (
		val: string | number | readonly string[] | undefined
	) => {
		const stringVal = typeof val === 'string' ? val : String( val || '' );
		return args.customFormatter
			? args.customFormatter( stringVal )
			: stringVal;
	};

	const [ inputValue, setInputValue ] = useState(
		getFormattedValue( args.value )
	);
	const { setValidationErrors, showValidationError } =
		useDispatch( validationStore );

	useEffect( () => {
		setInputValue( getFormattedValue( args.value ) );
	}, [ args.value, args.customFormatter ] );

	const onChange = ( newValue: string ) => {
		const formattedValue = args.customFormatter
			? args.customFormatter( newValue )
			: newValue;

		setInputValue( formattedValue );

		action( 'change' )( newValue || '' );
		updateArgs( { value: newValue } );

		// Always show error for WithError story.
		if ( args.id === 'with-error-id' ) {
			setValidationErrors( {
				[ args.id ]: {
					message: 'This field cannot be empty',
					hidden: true,
				},
			} );
			showValidationError( args.id );
			return;
		}

		// Default: only show error if input is empty and showError is true.
		if (
			args.showError &&
			! newValue.trim() &&
			args.id !== 'with-error-id'
		) {
			setValidationErrors( {
				[ args.id || 'unique-id' ]: {
					message: 'This field cannot be empty',
					hidden: true,
				},
			} );
			showValidationError( args.id || 'unique-id' );
		}
	};

	return (
		<ValidatedTextInput
			{ ...args }
			value={ inputValue }
			onChange={ onChange }
		/>
	);
};

export const Default: StoryFn< ValidatedTextInputProps > = Template.bind( {} );
Default.args = {
	id: 'unique-id',
	label: 'Enter your value',
	value: '',
};

export const WithError: StoryFn< ValidatedTextInputProps > = Template.bind(
	{}
);
WithError.args = {
	id: 'with-error-id',
	label: 'Enter your value',
	value: '',
	errorMessage: 'This is an error message',
	showError: true,
};

export const WithCustomFormatter: StoryFn< ValidatedTextInputProps > =
	Template.bind( {} );
WithCustomFormatter.args = {
	id: 'unique-id',
	label: 'Enter your value',
	value: 'The custom formatter will turn this lowercase string to uppercase.',
	customFormatter: ( value: string ) => {
		return value.toUpperCase();
	},
};
