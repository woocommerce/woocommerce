/**
 * External dependencies
 */
import {
	cloneElement,
	useState,
	createElement,
	useCallback,
	useEffect,
} from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { ChangeEvent, PropsWithChildren } from 'react';

/**
 * Internal dependencies
 */
import { FormContext } from './form-context';

type FormProps< Values > = {
	/**
	 * Object of all initial errors to store in state.
	 */
	errors: Record< keyof Values, string >;
	/**
	 * Object key:value pair list of all initial field values.
	 */
	initialValues: Values;
	/**
	 * This prop helps determine whether or not a field has received focus
	 */
	touched: Record< keyof Values, boolean >;
	/**
	 * Function to call when a form is submitted with valid fields.
	 *
	 * @deprecated
	 */
	onSubmitCallback?: ( values: Values ) => void;
	/**
	 * Function to call when a form is submitted with valid fields.
	 */
	onSubmit?: ( values: Values ) => void;
	/**
	 * Function to call when a value changes in the form.
	 *
	 * @deprecated
	 */
	onChangeCallback?: () => void;
	/**
	 * Function to call when a value changes in the form.
	 */
	onChange?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		value: { name: string; value: any },
		values: Values,
		hasErrors: boolean
	) => void;
	/**
	 * A function that is passed a list of all values and
	 * should return an `errors` object with error response.
	 */
	validate?: ( values: Values ) => Record< string, string >;
};

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function Form< Values extends Record< string, any > >(
	props: PropsWithChildren< FormProps< Values > >
): React.ReactElement | null {
	const [ values, setValues ] = useState< Values >( props.initialValues );
	const [ errors, setErrors ] = useState< Record< string, string > >( {} );
	const [ touched, setTouched ] = useState< {
		[ P in keyof Values ]?: boolean;
	} >( {} );

	const validate = useCallback(
		( newValues: Values, onValidate = () => {} ) => {
			const newErrors = props.validate ? props.validate( newValues ) : {};
			setErrors( newErrors );
			onValidate();
		},
		[ props.validate ]
	);

	useEffect( () => {
		validate( values );
	}, [] );

	const isValidForm = async () => {
		await validate( values );
		return ! Object.keys( errors ).length;
	};

	const setValue = useCallback(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( name: string, value: any ) => {
			setValues( { ...values, [ name ]: value } );
			validate( { ...values, [ name ]: value }, () => {
				const { onChange, onChangeCallback } = props;

				// Note that onChange is a no-op by default so this will never be null
				const callback = onChangeCallback || onChange;

				if ( onChangeCallback ) {
					deprecated( 'onChangeCallback', {
						version: '9.0.0',
						alternative: 'onChange',
						plugin: '@woocommerce/components',
					} );
				}
				// onChange keeps track of validity, so needs to
				// happen after setting the error state.
				if ( callback ) {
					callback(
						{ name, value },
						values,
						! Object.keys( errors || {} ).length
					);
				}
			} );
		},
		[ values, validate, props.onChange, props.onChangeCallback ]
	);

	const handleChange = useCallback(
		( name: string, value: ChangeEvent< HTMLInputElement > ) => {
			// Handle native events.
			if ( value.target ) {
				if ( value.target.type === 'checkbox' ) {
					setValue( name, ! values[ name ] );
				} else {
					setValue( name, value.target.value );
				}
			} else {
				setValue( name, value );
			}
		},
		[ setValue ]
	);

	const handleBlur = useCallback(
		( name: string ) => {
			setTouched( {
				...touched,
				[ name ]: true,
			} );
		},
		[ touched ]
	);

	const handleSubmit = async () => {
		const { onSubmitCallback, onSubmit } = props;
		const touchedFields: { [ P in keyof Values ]?: boolean } = {};
		Object.keys( values ).map(
			( name: keyof Values ) => ( touchedFields[ name ] = true )
		);
		setTouched( touchedFields );

		if ( await isValidForm() ) {
			// Note that onSubmit is a no-op by default so this will never be null
			const callback = onSubmitCallback || onSubmit;

			if ( onSubmitCallback ) {
				deprecated( 'onSubmitCallback', {
					version: '9.0.0',
					alternative: 'onSubmit',
					plugin: '@woocommerce/components',
				} );
			}

			if ( callback ) {
				callback( values );
			}
		}
	};

	function getInputProps< Value = string >(
		name: string
	): {
		value: Value;
		checked: boolean;
		selected: Value;
		onChange: ( value: ChangeEvent< HTMLInputElement > ) => void;
		onBlur: () => void;
		className: string | undefined;
		help: string | null;
	} {
		return {
			value: values[ name ],
			checked: Boolean( values[ name ] ),
			selected: values[ name ],
			onChange: ( value: ChangeEvent< HTMLInputElement > ) =>
				handleChange( name, value ),
			onBlur: () => handleBlur( name ),
			className:
				touched[ name ] && errors[ name ] ? 'has-error' : undefined,
			help: touched[ name ] ? errors[ name ] : null,
		};
	}

	const getStateAndHelpers = () => {
		return {
			values,
			errors,
			touched,
			setTouched,
			setValue,
			handleSubmit,
			getInputProps,
			isValidForm: ! Object.keys( errors ).length,
		};
	};

	if ( props.children && typeof props.children === 'function' ) {
		const element = props.children( getStateAndHelpers() );
		return (
			<FormContext.Provider
				value={ {
					values,
					errors,
					touched,
					setTouched,
					setValue,
					handleSubmit,
					getInputProps,
					isValidForm: ! Object.keys( errors ).length,
				} }
			>
				{ cloneElement( element ) }
			</FormContext.Provider>
		);
	}
	return (
		<FormContext.Provider
			value={ {
				values,
				errors,
				touched,
				setTouched,
				setValue,
				handleSubmit,
				getInputProps,
				isValidForm: ! Object.keys( errors ).length,
			} }
		>
			{ props.children }
		</FormContext.Provider>
	);
}

Form.defaultProps = {
	errors: {},
	initialValues: {},
	onSubmitCallback: null,
	onSubmit: () => {},
	onChangeCallback: null,
	onChange: () => {},
	touched: {},
	validate: () => {},
};

export { Form };
