/**
 * External dependencies
 */
import {
	cloneElement,
	useState,
	createElement,
	useCallback,
	useEffect,
	forwardRef,
	useImperativeHandle,
} from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { ChangeEvent, PropsWithChildren, useRef } from 'react';

/**
 * Internal dependencies
 */
import { FormContext } from './form-context';

type FormProps< Values > = {
	/**
	 * Object of all initial errors to store in state.
	 */
	errors?: { [ P in keyof Values ]?: string };
	/**
	 * Object key:value pair list of all initial field values.
	 */
	initialValues?: Values;
	/**
	 * This prop helps determine whether or not a field has received focus
	 */
	touched?: Record< keyof Values, boolean >;
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

function isChangeEvent< T >(
	value: T | ChangeEvent< HTMLInputElement >
): value is ChangeEvent< HTMLInputElement > {
	return ( value as ChangeEvent< HTMLInputElement > ).target !== undefined;
}

export type FormRef< Values > = {
	resetForm: ( initialValues: Values ) => void;
};

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent< Values extends Record< string, any > >(
	{
		onSubmit = () => {},
		onChange = () => {},
		initialValues = {} as Values,
		...props
	}: PropsWithChildren< FormProps< Values > >,
	ref: React.Ref< FormRef< Values > >
): React.ReactElement | null {
	const [ values, setValues ] = useState< Values >( initialValues );
	const [ errors, setErrors ] = useState< {
		[ P in keyof Values ]?: string;
	} >( props.errors || {} );
	const [ changedFields, setChangedFields ] = useState< {
		[ P in keyof Values ]?: boolean;
	} >( {} );
	const [ touched, setTouched ] = useState< {
		[ P in keyof Values ]?: boolean;
	} >( props.touched || {} );

	const validate = useCallback(
		( newValues: Values, onValidate = () => {} ) => {
			const newErrors = props.validate ? props.validate( newValues ) : {};
			setErrors( newErrors || {} );
			onValidate();
		},
		[ props.validate ]
	);

	useEffect( () => {
		validate( values );
	}, [] );

	const resetForm = (
		newInitialValues: Values,
		newChangedFields = {},
		newTouchedFields = {},
		newErrors = {}
	) => {
		setValues( newInitialValues || {} );
		setChangedFields( newChangedFields );
		setTouched( newTouchedFields );
		setErrors( newErrors );
	};

	useImperativeHandle( ref, () => ( {
		resetForm,
	} ) );

	const isValidForm = async () => {
		await validate( values );
		return ! Object.keys( errors ).length;
	};

	const setValue = useCallback(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( name: string, value: any ) => {
			setValues( { ...values, [ name ]: value } );
			if ( initialValues[ name ] !== value && ! changedFields[ name ] ) {
				setChangedFields( {
					...changedFields,
					[ name ]: true,
				} );
			} else if (
				initialValues[ name ] === value &&
				changedFields[ name ]
			) {
				setChangedFields( {
					...changedFields,
					[ name ]: false,
				} );
			}
			validate( { ...values, [ name ]: value }, () => {
				const { onChangeCallback } = props;

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
		[ values, validate, onChange, props.onChangeCallback ]
	);

	const handleChange = useCallback(
		(
			name: string,
			value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
		) => {
			// Handle native events.
			if ( isChangeEvent( value ) && value.target ) {
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
		const { onSubmitCallback } = props;
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

	function getInputProps< Value = Values[ keyof Values ] >(
		name: string
	): {
		value: Value;
		checked: boolean;
		selected?: boolean;
		onChange: (
			value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
		) => void;
		onBlur: () => void;
		className: string | undefined;
		help: string | null | undefined;
	} {
		return {
			value: values[ name ],
			checked: Boolean( values[ name ] ),
			selected: values[ name ],
			onChange: (
				value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
			) => handleChange( name, value ),
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

	const isDirty = Object.values( changedFields ).some( Boolean );

	if ( props.children && typeof props.children === 'function' ) {
		const element = props.children( getStateAndHelpers() );
		return (
			<FormContext.Provider
				value={ {
					values,
					errors,
					touched,
					isDirty,
					changedFields,
					setTouched,
					setValue,
					handleSubmit,
					getInputProps,
					isValidForm: ! Object.keys( errors ).length,
					resetForm,
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
				isDirty,
				changedFields,
				setTouched,
				setValue,
				handleSubmit,
				getInputProps,
				isValidForm: ! Object.keys( errors ).length,
				resetForm,
			} }
		>
			{ props.children }
		</FormContext.Provider>
	);
}

const Form = forwardRef( FormComponent ) as <
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	Values extends Record< string, any >
>(
	props: PropsWithChildren< FormProps< Values > > & {
		ref?: React.ForwardedRef< FormRef< Values > >;
	},
	ref: React.Ref< FormRef< Values > >
) => React.ReactElement | null;

export { Form };
