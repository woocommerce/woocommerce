/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	useCallback,
	forwardRef,
	useImperativeHandle,
	useRef,
} from '@wordpress/element';
import clsx from 'clsx';
import { isObject } from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { usePrevious } from '@woocommerce/base-hooks';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import TextInput from './text-input';
import './style.scss';
import { ValidationInputError } from '../validation-input-error';
import { getValidityMessageForInput } from '../../checkout/utils';
import { ValidatedTextInputProps } from './types';

export type ValidatedTextInputHandle = {
	focus?: () => void;
	revalidate: () => void;
	isFocused: () => boolean;
	setErrorMessage: ( errorMessage: string ) => void;
	inputRef: React.RefObject< HTMLInputElement >;
};

/**
 * A text based input which validates the input value.
 */
const ValidatedTextInput = forwardRef<
	ValidatedTextInputHandle,
	ValidatedTextInputProps
>(
	(
		{
			className,
			id,
			type = 'text',
			ariaDescribedBy = '',
			errorId,
			focusOnMount = false,
			onChange,
			showError = true,
			errorMessage: passedErrorMessage = '',
			value = '',
			customValidation = () => true,
			customValidityMessage,
			feedback = null,
			customFormatter = ( newValue: string ) => newValue,
			label,
			validateOnMount = true,
			instanceId: preferredInstanceId = '',
			icon = null,
			...rest
		},
		forwardedRef
	): JSX.Element => {
		// True on mount.
		const [ isPristine, setIsPristine ] = useState( true );

		// Track incoming value.
		const previousValue = usePrevious( value );

		// Ref for the input element.
		const inputRef = useRef< HTMLInputElement >( null );

		const instanceId = useInstanceId(
			ValidatedTextInput,
			'',
			preferredInstanceId
		);
		const textInputId =
			typeof id !== 'undefined' ? id : 'textinput-' + instanceId;
		const errorIdString = errorId !== undefined ? errorId : textInputId;

		const {
			setValidationErrors,
			hideValidationError,
			clearValidationError,
			showValidationError,
		} = useDispatch( validationStore );

		// Ref for validation callback.
		const customValidationRef = useRef( customValidation );

		// Update ref when validation callback changes.
		useEffect( () => {
			customValidationRef.current = customValidation;
		}, [ customValidation ] );

		const { validationError, validationErrorId } = useSelect(
			( select ) => {
				const store = select( validationStore );
				return {
					validationError: store.getValidationError( errorIdString ),
					validationErrorId:
						store.getValidationErrorId( errorIdString ),
				};
			},
			[ errorIdString ]
		);

		const validateInput = useCallback(
			( errorsHidden = true ) => {
				const inputObject = inputRef.current || null;

				if ( inputObject === null ) {
					return;
				}

				// Trim white space before validation.
				inputObject.value = inputObject.value.trim();
				inputObject.setCustomValidity( '' );

				if (
					inputObject.checkValidity() &&
					customValidationRef.current( inputObject ) &&
					errorsHidden
				) {
					clearValidationError( errorIdString );
					return;
				}

				if ( ! errorsHidden ) {
					showValidationError( errorIdString );
				}

				const validityMessage = getValidityMessageForInput(
					label,
					inputObject,
					customValidityMessage
				);

				if ( validityMessage ) {
					setValidationErrors( {
						[ errorIdString ]: {
							message: validityMessage,
							hidden: errorsHidden,
						},
					} );
				}
			},
			[
				clearValidationError,
				errorIdString,
				setValidationErrors,
				label,
				customValidityMessage,
				showValidationError,
			]
		);

		// Allows parent to trigger revalidation.
		useImperativeHandle(
			forwardedRef,
			function () {
				return {
					focus() {
						inputRef.current?.focus();
					},
					revalidate() {
						validateInput( ! value );
					},
					isFocused() {
						return (
							inputRef.current?.ownerDocument?.activeElement ===
							inputRef.current
						);
					},
					setErrorMessage( errorMessage: string ) {
						inputRef.current?.setCustomValidity( errorMessage );
					},
					inputRef,
				};
			},
			[ validateInput, value ]
		);

		/**
		 * Handle browser autofill / changes via data store.
		 *
		 * Trigger validation on incoming state change if the current element is not in focus. This is because autofilled
		 * elements do not trigger the blur() event, and so values can be validated in the background if the state changes
		 * elsewhere.
		 *
		 * Errors are immediately visible.
		 */
		useEffect( () => {
			if (
				value !== previousValue &&
				( value || previousValue ) &&
				inputRef &&
				inputRef.current !== null &&
				inputRef.current?.ownerDocument?.activeElement !==
					inputRef.current
			) {
				const formattedValue = customFormatter(
					inputRef.current.value
				);

				if ( formattedValue !== value ) {
					onChange( formattedValue );
				} else {
					validateInput( true );
				}
			}
		}, [ validateInput, customFormatter, value, previousValue, onChange ] );

		/**
		 * Validation on mount.
		 *
		 * If the input is in pristine state on mount, focus the element (if focusOnMount is enabled), and validate in the
		 * background.
		 *
		 * Errors are hidden until blur.
		 */
		useEffect( () => {
			if ( ! isPristine ) {
				return;
			}

			setIsPristine( false );

			if ( focusOnMount ) {
				inputRef.current?.focus();
			}

			// if validateOnMount is false, only validate input if focusOnMount is also false
			if ( validateOnMount || ! focusOnMount ) {
				validateInput( true );
			}
		}, [
			validateOnMount,
			focusOnMount,
			isPristine,
			setIsPristine,
			validateInput,
		] );

		// Remove validation errors when unmounted.
		useEffect( () => {
			return () => {
				clearValidationError( errorIdString );
			};
		}, [ clearValidationError, errorIdString ] );

		if ( passedErrorMessage !== '' && isObject( validationError ) ) {
			validationError.message = passedErrorMessage;
		}

		const hasError = validationError?.message && ! validationError?.hidden;

		return (
			<TextInput
				className={ clsx( className, {
					'has-error': hasError,
				} ) }
				aria-invalid={ hasError === true }
				id={ textInputId }
				aria-errormessage={
					// we're using the internal `aria-errormessage` attribute, calculated from the data store.
					// If a consumer wants to overwrite the attribute, they can pass a prop.
					showError && hasError && validationErrorId
						? validationErrorId
						: undefined
				}
				type={ type }
				feedback={
					showError && hasError ? (
						<ValidationInputError
							errorMessage={ passedErrorMessage }
							propertyName={ errorIdString }
							elementId={ errorIdString }
						/>
					) : (
						feedback
					)
				}
				ref={ inputRef }
				onChange={ ( newValue ) => {
					// Hide errors while typing.
					hideValidationError( errorIdString );

					// Validate the input value.
					validateInput( true );

					// Push the changes up to the parent component.
					const formattedValue = customFormatter( newValue );

					if ( formattedValue !== value ) {
						onChange( formattedValue );
					}
				} }
				onBlur={ () => validateInput( false ) }
				aria-describedby={ ariaDescribedBy }
				value={ value }
				title="" // This prevents the same error being shown on hover.
				label={ label }
				icon={ icon }
				{ ...rest }
			/>
		);
	}
);

export default ValidatedTextInput;
