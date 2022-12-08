/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useRef,
	useEffect,
	useState,
	useCallback,
	InputHTMLAttributes,
} from 'react';
import classnames from 'classnames';
import { withInstanceId } from '@wordpress/compose';
import { isObject } from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { usePrevious } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import TextInput from './text-input';
import './style.scss';
import { ValidationInputError } from '../validation-input-error';

interface ValidatedTextInputProps
	extends Omit<
		InputHTMLAttributes< HTMLInputElement >,
		'onChange' | 'onBlur'
	> {
	id?: string;
	instanceId: string;
	className?: string | undefined;
	ariaDescribedBy?: string | undefined;
	errorId?: string;
	focusOnMount?: boolean;
	showError?: boolean;
	errorMessage?: string | undefined;
	onChange: ( newValue: string ) => void;
	label?: string | undefined;
	value: string;
	requiredMessage?: string | undefined;
	customValidation?:
		| ( ( inputObject: HTMLInputElement ) => boolean )
		| undefined;
}

const ValidatedTextInput = ( {
	className,
	instanceId,
	id,
	ariaDescribedBy,
	errorId,
	focusOnMount = false,
	onChange,
	showError = true,
	errorMessage: passedErrorMessage = '',
	value = '',
	requiredMessage,
	customValidation,
	...rest
}: ValidatedTextInputProps ): JSX.Element => {
	const [ isPristine, setIsPristine ] = useState( true );
	const inputRef = useRef< HTMLInputElement >( null );
	const previousValue = usePrevious( value );
	const textInputId =
		typeof id !== 'undefined' ? id : 'textinput-' + instanceId;
	const errorIdString = errorId !== undefined ? errorId : textInputId;

	const { setValidationErrors, hideValidationError, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );

	const { validationError, validationErrorId } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			validationError: store.getValidationError( errorIdString ),
			validationErrorId: store.getValidationErrorId( errorIdString ),
		};
	} );

	const validateInput = useCallback(
		( errorsHidden = true ) => {
			const inputObject = inputRef.current || null;

			if ( inputObject === null ) {
				return;
			}

			// Trim white space before validation.
			inputObject.value = inputObject.value.trim();
			inputObject.setCustomValidity( '' );

			const inputIsValid = customValidation
				? inputObject.checkValidity() && customValidation( inputObject )
				: inputObject.checkValidity();

			if ( inputIsValid ) {
				clearValidationError( errorIdString );
				return;
			}

			const validityState = inputObject.validity;

			if ( validityState.valueMissing && requiredMessage ) {
				inputObject.setCustomValidity( requiredMessage );
			}

			setValidationErrors( {
				[ errorIdString ]: {
					message:
						inputObject.validationMessage ||
						__( 'Invalid value.', 'woo-gutenberg-products-block' ),
					hidden: errorsHidden,
				},
			} );
		},
		[
			clearValidationError,
			customValidation,
			errorIdString,
			requiredMessage,
			setValidationErrors,
		]
	);

	/**
	 * Handle browser autofill / changes via data store.
	 *
	 * Trigger validation on state change if the current element is not in focus. This is because autofilled elements do not
	 * trigger the blur() event, and so values can be validated in the background if the state changes elsewhere.
	 *
	 * Errors are immediately visible.
	 */
	useEffect( () => {
		if (
			value !== previousValue &&
			( value || previousValue ) &&
			inputRef &&
			inputRef.current !== null &&
			inputRef.current?.ownerDocument?.activeElement !== inputRef.current
		) {
			validateInput( false );
		}
		// We need to track value even if it is not directly used so we know when it changes.
	}, [ value, previousValue, validateInput ] );

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
		if ( focusOnMount ) {
			inputRef.current?.focus();
		}
		validateInput( true );
		setIsPristine( false );
	}, [ focusOnMount, isPristine, setIsPristine, validateInput ] );

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
	const describedBy =
		showError && hasError && validationErrorId
			? validationErrorId
			: ariaDescribedBy;

	return (
		<TextInput
			className={ classnames( className, {
				'has-error': hasError,
			} ) }
			aria-invalid={ hasError === true }
			id={ textInputId }
			feedback={
				showError && (
					<ValidationInputError
						errorMessage={ passedErrorMessage }
						propertyName={ errorIdString }
					/>
				)
			}
			ref={ inputRef }
			onChange={ ( val ) => {
				// Hide errors while typing.
				hideValidationError( errorIdString );

				// Revalidate on user input so we know if the value is valid.
				validateInput( true );

				// Push the changes up to the parent component if the value is valid.
				onChange( val );
			} }
			onBlur={ () => {
				validateInput( false );
			} }
			ariaDescribedBy={ describedBy }
			value={ value }
			{ ...rest }
		/>
	);
};
export const __ValidatedTexInputWithoutId = ValidatedTextInput;
export default withInstanceId( ValidatedTextInput );
