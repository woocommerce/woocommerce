/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useRef, useEffect, useState, Component } from 'react';
import classnames from 'classnames';
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';

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
	validateOnMount?: boolean;
	focusOnMount?: boolean;
	showError?: boolean;
	onChange: ( newValue: string ) => void;
	// @todo Type this properly when validation context is typed
	getValidationError: (
		errorId: string
	) => {
		message?: string;
		hidden?: boolean;
	};
	hideValidationError: ( errorId: string ) => void;
	setValidationErrors: ( errors: Record< string, unknown > ) => void;
	clearValidationError: ( errorId: string ) => void;
	getValidationErrorId: ( errorId: string ) => string;
	inputErrorComponent: typeof Component;
};

const ValidatedTextInput = ( {
	className,
	instanceId,
	id,
	ariaDescribedBy,
	errorId,
	validateOnMount = true,
	focusOnMount = false,
	onChange,
	showError = true,
	getValidationError,
	hideValidationError,
	setValidationErrors,
	clearValidationError,
	getValidationErrorId,
	inputErrorComponent: ValidationInputError,
	...rest
}: ValidatedTextInputProps ) => {
	const [ isPristine, setIsPristine ] = useState( true );
	const inputRef = useRef< HTMLInputElement >( null );
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

	useEffect( () => {
		if ( isPristine ) {
			if ( focusOnMount ) {
				inputRef.current?.focus();
			}
			setIsPristine( false );
		}
	}, [ focusOnMount, isPristine, setIsPristine ] );

	useEffect( () => {
		if ( isPristine ) {
			if ( validateOnMount ) {
				validateInput();
			}
			setIsPristine( false );
		}
	}, [ isPristine, setIsPristine, validateOnMount, validateInput ] );

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
			id={ textInputId }
			onBlur={ () => {
				validateInput( false );
			} }
			feedback={
				showError && (
					<ValidationInputError propertyName={ errorIdString } />
				)
			}
			ref={ inputRef }
			onChange={ ( val ) => {
				hideValidationError( errorIdString );
				onChange( val );
			} }
			ariaDescribedBy={ describedBy }
			{ ...rest }
		/>
	);
};

export default withInstanceId( ValidatedTextInput );
