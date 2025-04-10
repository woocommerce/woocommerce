/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	useState,
	createElement,
	useCallback,
	useEffect,
	useMemo,
	forwardRef,
	useImperativeHandle,
} from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { ChangeEvent, useRef } from 'react';
import _setWith from 'lodash/setWith';
import _get from 'lodash/get';
import _clone from 'lodash/clone';
import _isEqual from 'lodash/isEqual';
import _omit from 'lodash/omit';

/**
 * Internal dependencies
 */
import { FormContext } from './form-context';
import {
	CheckboxProps,
	ConsumerInputProps,
	FormProps,
	FormRef,
	InputProps,
	PropsWithChildrenFunction,
	SelectControlProps,
	FormContextType,
	FormErrors,
} from './types';

function isChangeEvent< T >(
	value: T | ChangeEvent< HTMLInputElement >
): value is ChangeEvent< HTMLInputElement > {
	return ( value as ChangeEvent< HTMLInputElement > ).target !== undefined;
}

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent< Values extends Record< string, any > = any >(
	{
		children,
		onSubmit = () => {},
		onChange = () => {},
		onChanges = () => {},
		...props
	}: PropsWithChildrenFunction<
		FormProps< Values >,
		FormContextType< Values >
	>,
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
		( name: keyof Values, value: any ) => {
			setValues( _setWith( { ...values }, name, value, _clone ) );
		},
		[ values, validate, onChange, props.onChangeCallback ]
	);

	const handleChange = useCallback(
		(
			name: keyof Values,
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
		( name: keyof Values ) => {
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

	function getInputProps< P extends keyof Values >(
		name: P,
		inputProps: ConsumerInputProps< Values > = {}
	): InputProps< Values, Values[ P ] > {
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

	function getCheckboxControlProps< P extends keyof Values >(
		name: P,
		inputProps: ConsumerInputProps< Values > = {}
	): CheckboxProps< Values, Values[ P ] > {
		return _omit( getInputProps( name, inputProps ), [
			'selected',
			'value',
		] );
	}

	function getSelectControlProps< P extends keyof Values >(
		name: P,
		inputProps: ConsumerInputProps< Values > = {}
	): SelectControlProps< Values, Values[ P ] > {
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

	const getStateAndHelpers = (): FormContextType< Values > => {
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
			return children( getStateAndHelpers() );
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
	props: PropsWithChildrenFunction<
		FormProps< Values >,
		FormContextType< Values >
	> & {
		ref?: React.ForwardedRef< FormRef< Values > >;
	},
	ref: React.Ref< FormRef< Values > >
) => React.ReactElement | null;

export { Form };
