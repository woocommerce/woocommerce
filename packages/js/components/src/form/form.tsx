/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	cloneElement,
	useState,
	createElement,
	useCallback,
	useEffect,
	useMemo,
	forwardRef,
	useImperativeHandle,
} from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { ChangeEvent, PropsWithChildren, useRef } from 'react';
import _setWith from 'lodash/setWith';
import _get from 'lodash/get';
import _clone from 'lodash/clone';
import _isEqual from 'lodash/isEqual';
import _omit from 'lodash/omit';

/**
 * Internal dependencies
 */
import { FormContext, FormErrors } from './form-context';

type FormProps< Values > = {
	/**
	 * Object of all initial errors to store in state.
	 */
	errors?: FormErrors< Values >;
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
	validate?: ( values: Values ) => FormErrors< Values >;
};

function isChangeEvent< T >(
	value: T | ChangeEvent< HTMLInputElement >
): value is ChangeEvent< HTMLInputElement > {
	return ( value as ChangeEvent< HTMLInputElement > ).target !== undefined;
}

export type FormRef< Values > = {
	resetForm: ( initialValues: Values ) => void;
};

export type InputProps< Values, Value > = {
	value: Value;
	checked: boolean;
	selected?: boolean;
	onChange: (
		value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
	) => void;
	onBlur: () => void;
	className: string | undefined;
	help: string | null | undefined;
};

export type CheckboxProps< Values, Value > = Omit<
	InputProps< Values, Value >,
	'value' | 'selected'
>;

export type SelectControlProps< Values, Value > = Omit<
	InputProps< Values, Value >,
	'value'
> & {
	value: string | undefined;
};

export type ConsumerInputProps< Values > = {
	className?: string;
	onChange?: (
		value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
	) => void;
	onBlur?: () => void;
	[ key: string ]: unknown;
	sanitize?: ( value: Values[ keyof Values ] ) => Values[ keyof Values ];
};

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent< Values extends Record< string, any > >(
	{
		children,
		onSubmit = () => {},
		onChange = () => {},
		onChanges = () => {},
		...props
	}: PropsWithChildren< FormProps< Values > >,
	ref: React.Ref< FormRef< Values > >
): React.ReactElement | null {
	const initialValues = useRef( props.initialValues ?? ( {} as Values ) );
	const [ values, setValuesInternal ] = useState< Values >(
		props.initialValues ?? ( {} as Values )
	);
	const [ errors, setErrors ] = useState< FormErrors< Values > >(
		props.errors || {}
	);
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

	const resetForm: (
		newInitialValues?: Values,
		newTouchedFields?: { [ P in keyof Values ]?: boolean | undefined },
		newErrors?: FormErrors< Values >
	) => void = ( newInitialValues, newTouchedFields = {}, newErrors = {} ) => {
		const newValues = newInitialValues ?? initialValues.current ?? {};
		initialValues.current = newValues;
		setValuesInternal( newValues );
		setTouched( newTouchedFields );
		setErrors( newErrors );
	};

	useImperativeHandle( ref, () => ( {
		resetForm,
	} ) );

	const isValidForm = async () => {
		validate( values );
		return ! Object.keys( errors ).length;
	};

	const setValues = useCallback(
		( valuesToSet: Values ) => {
			const newValues = { ...values, ...valuesToSet };
			setValuesInternal( newValues );

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
			setValues( _setWith( { ...values }, name, value, _clone ) );
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
					setValue( name, ! _get( values, name ) );
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
				return callback( values );
			}
		}
	};

	function getInputProps< Value = Values[ keyof Values ] >(
		name: string,
		inputProps: ConsumerInputProps< Values > = {}
	): InputProps< Values, Value > {
		const inputValue = _get( values, name );
		const isTouched = touched[ name ];
		const inputError = _get( errors, name );
		const {
			className: classNameProp,
			onBlur: onBlurProp,
			onChange: onChangeProp,
			sanitize,
			...additionalProps
		} = inputProps;

		return {
			value: inputValue,
			checked: Boolean( inputValue ),
			selected: inputValue,
			onChange: (
				value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
			) => {
				handleChange( name, value );
				if ( onChangeProp ) {
					onChangeProp( value );
				}
			},
			onBlur: () => {
				if ( sanitize ) {
					handleChange( name, sanitize( inputValue ) );
				}
				handleBlur( name );
				if ( onBlurProp ) {
					onBlurProp();
				}
			},
			className: classnames( classNameProp, {
				'has-error': isTouched && inputError,
			} ),
			help: isTouched ? ( inputError as string ) : null,
			...additionalProps,
		};
	}

	function getCheckboxControlProps< Value = Values[ keyof Values ] >(
		name: string,
		inputProps: ConsumerInputProps< Values > = {}
	): CheckboxProps< Values, Value > {
		return _omit( getInputProps( name, inputProps ), [
			'selected',
			'value',
		] );
	}

	function getSelectControlProps< Value = Values[ keyof Values ] >(
		name: string,
		inputProps: ConsumerInputProps< Values > = {}
	): SelectControlProps< Values, Value > {
		const selectControlProps = getInputProps( name, inputProps );
		return {
			...selectControlProps,
			value:
				selectControlProps.value === undefined
					? undefined
					: String( selectControlProps.value ),
		};
	}

	const isDirty = useMemo(
		() => ! _isEqual( initialValues.current, values ),
		[ initialValues.current, values ]
	);

	const getStateAndHelpers = () => {
		return {
			values,
			errors,
			touched,
			isDirty,
			setTouched,
			setValue,
			setValues,
			handleSubmit,
			getCheckboxControlProps,
			getInputProps,
			getSelectControlProps,
			isValidForm: ! Object.keys( errors ).length,
			resetForm,
		};
	};

	function getChildren() {
		if ( typeof children === 'function' ) {
			const element = children( getStateAndHelpers() );
			return cloneElement( element );
		}
		return children;
	}

	return (
		<FormContext.Provider value={ getStateAndHelpers() }>
			{ getChildren() }
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
