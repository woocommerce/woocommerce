/**
 * External dependencies
 */
import {
	useEffect,
	useCallback,
	forwardRef,
	useImperativeHandle,
	useRef,
	useId,
} from '@wordpress/element';
import clsx from 'clsx';
import { isObject } from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import type { InputHTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import CheckboxControl from './index';
import './style.scss';
import { ValidationInputError } from '../validation-input-error';
import { getValidityMessageForInput } from '../../checkout/utils';

export interface ValidatedCheckboxControlProps
	extends Omit< InputHTMLAttributes< HTMLInputElement >, 'onChange' > {
	// Unique instance ID. id will be used instead if provided.
	instanceId?: string;
	// id to use for the error message. If not provided, an id will be generated.
	errorId?: string;
	// Callback to run on change which is passed the updated value.
	onChange: ( newValue: boolean ) => void;
	// Optional label for the field.
	label?: string;
	// If true, validation errors will be shown.
	showError?: boolean;
	// Error message to display alongside the field regardless of validation.
	errorMessage?: string;
	// Custom validation function that is run on change. Use setCustomValidity to set an error message.
	customValidation?:
		| ( ( inputObject: HTMLInputElement ) => boolean )
		| undefined;
	// Custom validation message to display when validity is false. Given the input element. Expected to use inputObject.validity.
	customValidityMessage?: ( validity: ValidityState ) => string;
	// Whether validation should run on mount.
	validateOnMount?: boolean;
}

export type ValidatedCheckboxControlHandle = {
	focus?: () => void;
	revalidate: () => void;
};

/**
 * A checkbox which validates the input is checked.
 */
const ValidatedCheckboxControl = forwardRef<
	ValidatedCheckboxControlHandle,
	ValidatedCheckboxControlProps
>(
	(
		{
			className,
			id,
			'aria-describedby': ariaDescribedBy,
			errorId,
			onChange,
			showError = true,
			errorMessage: passedErrorMessage = '',
			checked = false,
			customValidation = () => true,
			customValidityMessage,
			label,
			validateOnMount = true,
			instanceId: preferredInstanceId = '',
			disabled = false,
			...rest
		},
		forwardedRef
	) => {
		// Ref for the input element.
		const inputRef = useRef< HTMLInputElement >( null );

		const genId = useId();
		const instanceId = preferredInstanceId || genId;
		const textInputId = id || `textinput-${ instanceId }`;
		const errorIdString = errorId || textInputId;

		const { setValidationErrors, clearValidationError } =
			useDispatch( validationStore );

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

				if (
					inputObject.checkValidity() &&
					customValidationRef.current( inputObject )
				) {
					clearValidationError( errorIdString );
					return;
				}

				setValidationErrors( {
					[ errorIdString ]: {
						message: getValidityMessageForInput(
							label,
							inputObject,
							customValidityMessage
						),
						hidden: errorsHidden,
					},
				} );
			},
			[
				clearValidationError,
				errorIdString,
				setValidationErrors,
				label,
				customValidityMessage,
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
						validateInput( false );
					},
				};
			},
			[ validateInput ]
		);

		/**
		 * Validation on mount.
		 */
		useEffect( () => {
			if ( validateOnMount ) {
				validateInput( true );
			}
		}, [ validateOnMount, validateInput ] );

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
			<CheckboxControl
				className={ clsx(
					'wc-block-components-validated-checkbox-control',
					className,
					{
						'has-error': hasError,
					}
				) }
				aria-invalid={ hasError === true }
				id={ textInputId }
				aria-errormessage={
					// we're using the internal `aria-errormessage` attribute, calculated from the data store.
					// If a consumer wants to overwrite the attribute, they can pass a prop.
					showError && hasError && validationErrorId
						? validationErrorId
						: undefined
				}
				ref={ inputRef }
				onChange={ useCallback(
					( newValue ) => {
						validateInput( false );
						// Push the changes up to the parent component.
						onChange( newValue );
					},
					[ onChange, validateInput ]
				) }
				aria-describedby={ ariaDescribedBy }
				checked={ checked }
				title="" // This prevents the same error being shown on hover.
				label={ label }
				disabled={ disabled }
				{ ...rest }
			>
				<ValidationInputError propertyName={ errorIdString } />
			</CheckboxControl>
		);
	}
);

export default ValidatedCheckboxControl;
