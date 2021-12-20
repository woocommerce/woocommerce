/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useRef, useEffect, useState } from 'react';
import classnames from 'classnames';
import {
	ValidationInputError,
	useValidationContext,
} from '@woocommerce/base-context';
import { withInstanceId } from '@wordpress/compose';
import { isString } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import TextInput from './text-input';
import './style.scss';

interface ValidatedTextInputPropsWithId {
	instanceId?: string;
	id: string;
}

interface ValidatedTextInputPropsWithInstanceId {
	instanceId: string;
	id?: string;
}

type ValidatedTextInputProps = (
	| ValidatedTextInputPropsWithId
	| ValidatedTextInputPropsWithInstanceId
 ) & {
	className?: string;
	ariaDescribedBy?: string;
	errorId?: string;
	focusOnMount?: boolean;
	showError?: boolean;
	errorMessage?: string;
	onChange: ( newValue: string ) => void;
	value: string;
};

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
	...rest
}: ValidatedTextInputProps ) => {
	const [ isPristine, setIsPristine ] = useState( true );
	const inputRef = useRef< HTMLInputElement >( null );
	const {
		getValidationError,
		hideValidationError,
		setValidationErrors,
		clearValidationError,
		getValidationErrorId,
	} = useValidationContext();
	const textInputId =
		typeof id !== 'undefined' ? id : 'textinput-' + instanceId;
	const errorIdString = errorId !== undefined ? errorId : textInputId;

	const validateInput = useCallback(
		( errorsHidden = true ) => {
			const inputObject = inputRef.current || null;
			if ( ! inputObject ) {
				return;
			}
			// Trim white space before validation.
			inputObject.value = inputObject.value.trim();
			const inputIsValid = inputObject.checkValidity();
			if ( inputIsValid ) {
				clearValidationError( errorIdString );
			} else {
				setValidationErrors( {
					[ errorIdString ]: {
						message:
							inputObject.validationMessage ||
							__(
								'Invalid value.',
								'woo-gutenberg-products-block'
							),
						hidden: errorsHidden,
					},
				} );
			}
		},
		[ clearValidationError, errorIdString, setValidationErrors ]
	);

	/**
	 * Focus on mount
	 *
	 * If the input is in pristine state, focus the element.
	 */
	useEffect( () => {
		if ( isPristine && focusOnMount ) {
			inputRef.current?.focus();
		}
		setIsPristine( false );
	}, [ focusOnMount, isPristine, setIsPristine ] );

	/**
	 * Value Validation
	 *
	 * Runs validation on state change if the current element is not in focus. This is because autofilled elements do not
	 * trigger the blur() event, and so values can be validated in the background if the state changes elsewhere.
	 */
	useEffect( () => {
		if (
			inputRef.current?.ownerDocument?.activeElement !== inputRef.current
		) {
			validateInput( true );
		}
		// We need to track value even if it is not directly used so we know when it changes.
	}, [ value, validateInput ] );

	// Remove validation errors when unmounted.
	useEffect( () => {
		return () => {
			clearValidationError( errorIdString );
		};
	}, [ clearValidationError, errorIdString ] );

	// @todo - When useValidationContext is converted to TypeScript, remove this cast and use the correct type.
	const errorMessage = ( getValidationError( errorIdString ) || {} ) as {
		message?: string;
		hidden?: boolean;
	};

	if ( isString( passedErrorMessage ) && passedErrorMessage !== '' ) {
		errorMessage.message = passedErrorMessage;
	}

	const hasError = errorMessage.message && ! errorMessage.hidden;
	const describedBy =
		showError && hasError && getValidationErrorId( errorIdString )
			? getValidationErrorId( errorIdString )
			: ariaDescribedBy;

	return (
		<TextInput
			className={ classnames( className, {
				'has-error': hasError,
			} ) }
			aria-invalid={ hasError === true }
			id={ textInputId }
			onBlur={ () => {
				validateInput( false );
			} }
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
				hideValidationError( errorIdString );
				onChange( val );
			} }
			ariaDescribedBy={ describedBy }
			value={ value }
			{ ...rest }
		/>
	);
};

export default withInstanceId( ValidatedTextInput );
