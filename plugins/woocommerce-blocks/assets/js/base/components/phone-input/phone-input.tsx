/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import classnames from 'classnames';
import { Label, ValidationInputError } from '@woocommerce/blocks-components';
import IntlTelInput from 'intl-tel-input/react/build/IntlTelInput.esm';
import 'intl-tel-input/build/js/utils';

/**
 * Internal dependencies
 */
import './style.scss';
import type { PhoneInputProps } from './props';

export const PhoneInput = ( {
	country,
	className,
	id,
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	autoComplete = 'off',
	value = '',
	onChange,
	required = false,
	onBlur = () => {
		/* Do nothing */
	},
	feedback,
	...rest
}: PhoneInputProps ): JSX.Element => {
	const [ isActive, setIsActive ] = useState( false );
	const [ validationError, setValidationError ] = useState( '' );
	const [ showValidationError, setShowValidationError ] = useState( false );

	return (
		<div
			className={ classnames(
				'wc-block-components-phone-input',
				className,
				{
					'is-active': isActive || value,
				}
			) }
		>
			<div className="field-wrapper">
				<IntlTelInput
					className={ classnames( className, {
						'has-error': !! validationError,
					} ) }
					initialValue={ value }
					onChangeNumber={ ( newValue: string ) => {
						setShowValidationError( false );
						onChange( newValue );
					} }
					initOptions={ {
						initialCountry: country,
						showSelectedDialCode: true,
					} }
					onFocus={ () => setIsActive( true ) }
					onBlur={ () => {
						onBlur( value );
						setIsActive( false );

						if ( ! value && ! required ) {
							setValidationError( '' );
						}

						setShowValidationError( true );
					} }
					onChangeValidity={ ( isValid: boolean ) => {
						if ( isValid ) {
							setValidationError( '' );
						}
					} }
					onChangeErrorCode={ ( errorCode: number ) => {
						if ( errorCode ) {
							// Todo handle error code.
							setValidationError(
								__(
									'Please enter a valid phone number',
									'woocommerce'
								)
							);
						}
					} }
				/>
			</div>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: id,
					className: 'field-label',
				} }
			/>
			{ !! validationError && showValidationError && (
				<ValidationInputError errorMessage={ validationError } />
			) }
		</div>
	);
};

export default PhoneInput;
