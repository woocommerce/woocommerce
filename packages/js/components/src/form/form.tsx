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
import { ChangeEvent, PropsWithChildren } from 'react';

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
		isValid: boolean
	) => void;
	/**
	 * Function to call when one or more values change in the form.
	 */
	onChanges?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		changedValues: { name: string; value: any }[],
		values: Values,
		isValid: boolean
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
		onChanges = () => {},
		initialValues = {} as Values,
		...props
	}: PropsWithChildren< FormProps< Values > >,
	ref: React.Ref< FormRef< Values > >
): React.ReactElement | null {
	const [ values, setValuesInternal ] = useState< Values >( initialValues );
	const [ errors, setErrors ] = useState< {
		[ P in keyof Values ]?: string;
	} >( props.errors || {} );
	const [ changedFields, setChangedFields ] = useState< {
		[ P in keyof Values ]?: boolean;
	} >( {} );
	const [ touched, setTouched ] = useState< {
		[ P in keyof Values ]?: boolean;
	} >( props.touched || {} );

	const validate: (
		newValues: Values,
		onValidate?: ( newErrors: {
			[ P in keyof Values ]?: string;
		} ) => void
	) => void = useCallback(
		( newValues: Values, onValidate = () => {} ) => {
			const newErrors = props.validate ? props.validate( newValues ) : {};
			setErrors( newErrors || {} );
			onValidate( newErrors );
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
		setValuesInternal( newInitialValues || {} );
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

	const setValues = useCallback(
		( valuesToSet: Values ) => {
			const newValues = { ...values, ...valuesToSet };
			setValuesInternal( newValues );

			const changedFieldsToSet: { [ P in keyof Values ]?: boolean } = {};

			for ( const key in valuesToSet ) {
				if (
					initialValues[ key ] !== valuesToSet[ key ] &&
					! changedFields[ key ]
				) {
					changedFieldsToSet[ key ] = true;
				} else if (
					initialValues[ key ] === valuesToSet[ key ] &&
					changedFields[ key ]
				) {
					changedFieldsToSet[ key ] = false;
				}
			}

			setChangedFields( { ...changedFields, ...changedFieldsToSet } );

			validate( newValues, ( newErrors ) => {
				const { onChangeCallback } = props;

				// Note that onChange is a no-op by default so this will never be null
				const singleValueChangeCallback = onChangeCallback || onChange;

				if ( onChangeCallback ) {
					deprecated( 'onChangeCallback', {
						version: '9.0.0',
						alternative: 'onChange',
						plugin: '@woocommerce/components',
					} );
				}

				if ( ! singleValueChangeCallback && ! onChanges ) {
					return;
				}

				// onChange and onChanges keep track of validity, so needs to
				// happen after setting the error state.

				const isValid = ! Object.keys( newErrors || {} ).length;
				const nameValuePairs = [];
				for ( const key in valuesToSet ) {
					const nameValuePair = {
						name: key,
						value: valuesToSet[ key ],
					};

					nameValuePairs.push( nameValuePair );

					if ( singleValueChangeCallback ) {
						singleValueChangeCallback(
							nameValuePair,
							newValues,
							isValid
						);
					}
				}

				if ( onChanges ) {
					onChanges( nameValuePairs, newValues, isValid );
				}
			} );
		},
		[ values, validate, onChange, props.onChangeCallback ]
	);

	const setValue = useCallback(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( name: string, value: any ) => {
			const valuesToSet: Values = {} as Values;
			valuesToSet[ name as keyof Values ] = value;
			setValues( valuesToSet );
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
			setValues,
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
					setValues,
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
				setValues,
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
