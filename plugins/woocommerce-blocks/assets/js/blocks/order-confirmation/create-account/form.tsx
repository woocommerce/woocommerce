/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { PRIVACY_URL, TERMS_URL } from '@woocommerce/block-settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { PasswordStrengthMeter } from '@woocommerce/base-components/cart-checkout';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

const termsPageLink = TERMS_URL ? (
	<a href={ TERMS_URL } target="_blank" rel="noreferrer">
		{ __( 'Terms and Conditions', 'woocommerce' ) }
	</a>
) : (
	<>{ __( 'Terms and Conditions', 'woocommerce' ) }</>
);

const privacyPageLink = PRIVACY_URL ? (
	<a href={ PRIVACY_URL } target="_blank" rel="noreferrer">
		{ __( 'Privacy Policy', 'woocommerce' ) }
	</a>
) : (
	<>{ __( 'Privacy Policy', 'woocommerce' ) }</>
);

const Form = ( {
	attributes: blockAttributes,
	isEditor,
}: {
	attributes: { customerEmail: string };
	isEditor: boolean;
} ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ password, setPassword ] = useState( '' );
	const [ passwordStrength, setPasswordStrength ] = useState( 0 );
	const hasValidationError = useSelect( ( select ) =>
		select( VALIDATION_STORE_KEY ).getValidationError( 'account-password' )
	);
	const customerEmail =
		blockAttributes.customerEmail ||
		( isEditor ? 'customer@email.com' : '' );

	return (
		<form
			className={ 'wc-block-order-confirmation-create-account-form' }
			method="POST"
			onSubmit={ ( event ) => {
				if ( hasValidationError ) {
					event.preventDefault();
					return;
				}
				setIsLoading( true );
			} }
		>
			<p>
				{ createInterpolateElement(
					__( 'Set a strong password for <email/>', 'woocommerce' ),
					{
						email: <strong>{ customerEmail }</strong>,
					}
				) }
			</p>
			<div>
				<ValidatedTextInput
					disabled={ isLoading }
					type="password"
					label={ __( 'Password', 'woocommerce' ) }
					className={ `wc-block-components-address-form__password` }
					value={ password }
					required={ true }
					errorId={ 'account-password' }
					customValidityMessage={ (
						validity: ValidityState
					): string | undefined => {
						if (
							validity.valueMissing ||
							validity.badInput ||
							validity.typeMismatch
						) {
							return __(
								'Please enter a valid password',
								'woocommerce'
							);
						}
					} }
					customValidation={ ( inputObject ) => {
						if ( passwordStrength < 2 ) {
							inputObject.setCustomValidity(
								__(
									'Please create a stronger password',
									'woocommerce'
								)
							);
							return false;
						}
						return true;
					} }
					onChange={ ( value: string ) => setPassword( value ) }
					feedback={
						<PasswordStrengthMeter
							password={ password }
							onChange={ ( strength: number ) =>
								setPasswordStrength( strength )
							}
						/>
					}
				/>
			</div>
			<Button
				className={
					'wc-block-order-confirmation-create-account-button'
				}
				type="submit"
				disabled={ !! hasValidationError || ! password || isLoading }
				showSpinner={ isLoading }
			>
				{ __( 'Create an account', 'woocommerce' ) }
			</Button>
			<input type="hidden" name="email" value={ customerEmail } />
			<input type="hidden" name="password" value={ password } />
			<input type="hidden" name="create-account" value="1" />
			<p className={ 'wc-block-order-confirmation-create-account-terms' }>
				{ createInterpolateElement(
					/* translators: %1$s terms page link, %2$s privacy page link. */
					__(
						'By creating an account you agree to our <terms/> and <privacy/>.',
						'woocommerce'
					),
					{ terms: termsPageLink, privacy: privacyPageLink }
				) }
			</p>
		</form>
	);
};

export default Form;
