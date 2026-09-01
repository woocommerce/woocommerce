/**
 * External dependencies
 */
import clsx from 'clsx';
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
import _toPath from 'lodash/toPath';
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

// Path segments lodash refuses to write through.
const UNWRITABLE_KEYS = [ '__proto__', 'constructor', 'prototype' ];

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent< Values extends Record< string, any > = any >(
	{
		children,
		onSubmit = () => {},
		// Keep these defaults inline: setValues depends on them, so hoisting them
		// to module constants would make setValue/setValues referentially stable
		// for consumers that omit the props and change when dependent effects run.
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
	// The latest logical values, advanced synchronously on every write so
	// same-stack writes build on each other instead of on the last render.
	const pendingValuesRef = useRef( initialValues.current );
	const [ values, setValuesInternal ] = useState< Values >(
		initialValues.current
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
		pendingValuesRef.current = newValues;
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
			const newValues = { ...pendingValuesRef.current, ...valuesToSet };
			pendingValuesRef.current = newValues;
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
				// Report the keys the merge above actually took, which is the
				// own enumerable ones. A `for...in` here would also walk the
				// prototype chain and report fields the form never stored.
				// The `|| {}` leaves a nullish patch a no-op, which is what
				// the spread above and the previous `for...in` both did.
				for ( const key of Object.keys( valuesToSet || {} ) ) {
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
		[ validate, onChange, onChanges, props.onChangeCallback ]
	);

	const setValue = useCallback(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( name: keyof Values, value: any ) => {
			// lodash writes an existing literal key such as 'a.b' in place rather
			// than as a path, so only split a name the form does not already hold.
			const path = Object.prototype.hasOwnProperty.call(
				pendingValuesRef.current,
				name
			)
				? [ String( name ) ]
				: _toPath( name );

			// toPath() yields no segments for a name such as '' or null, while
			// setWith() still writes those as a literal key. Fall back to the
			// literal so the entry below reads the key the write landed on.
			const segments = path.length ? path : [ String( name ) ];

			// lodash drops a write whose path steps through one of these keys.
			// Drop it here too: otherwise the entry picked below reads an
			// inherited value and setValues adds it to the form as an own key.
			if (
				segments.some( ( segment ) =>
					UNWRITABLE_KEYS.includes( segment )
				)
			) {
				return;
			}

			const newValues = _setWith(
				{ ...pendingValuesRef.current },
				name,
				value,
				_clone
			);
			// Hand setValues only the entry this write touched so it reports one
			// change: a literal key is its own only segment, and a path reports
			// under its top-level key.
			const key = segments[ 0 ];
			setValues( { [ key ]: newValues[ key ] } as Values );
		},
		[ setValues ]
	);

	const handleChange = useCallback(
		(
			name: keyof Values,
			value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
		) => {
			// Handle native events.
			if ( isChangeEvent( value ) && value.target ) {
				if ( value.target.type === 'checkbox' ) {
					setValue( name, ! _get( pendingValuesRef.current, name ) );
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
			className: clsx( classNameProp, {
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
	Values extends Record< string, any >,
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
