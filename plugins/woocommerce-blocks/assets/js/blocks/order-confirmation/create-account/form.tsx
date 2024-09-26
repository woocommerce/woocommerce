/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import PasswordStrengthMeter from '@woocommerce/base-components/cart-checkout/password-strength-meter';
import { PRIVACY_URL, TERMS_URL } from '@woocommerce/block-settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';

const termsPageLink = TERMS_URL ? (
	<a href={ TERMS_URL } target="_blank" rel="noreferrer">
		{ __( 'Terms', 'woocommerce' ) }
	</a>
) : (
	<span>{ __( 'Terms', 'woocommerce' ) }</span>
);

const privacyPageLink = PRIVACY_URL ? (
	<a href={ PRIVACY_URL } target="_blank" rel="noreferrer">
		{ __( 'Privacy Policy', 'woocommerce' ) }
	</a>
) : (
	<span>{ __( 'Privacy Policy', 'woocommerce' ) }</span>
);

const PasswordField = ( {
	isLoading,
	password,
	setPassword,
}: {
	isLoading: boolean;
	password: string;
	setPassword: ( password: string ) => void;
} ) => {
	const [ passwordStrength, setPasswordStrength ] = useState( 0 );

	return (
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
	);
};

const Form = ( {
	attributes: blockAttributes,
	isEditor,
}: {
	attributes?: { customerEmail?: string; nonceToken?: string };
	isEditor: boolean;
} ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ password, setPassword ] = useState( '' );
	const hasValidationError = useSelect( ( select ) =>
		select( VALIDATION_STORE_KEY ).getValidationError( 'account-password' )
	);
	const customerEmail =
		blockAttributes?.customerEmail ||
		( isEditor ? 'customer@email.com' : '' );
	const nonceToken = blockAttributes?.nonceToken || '';

	// Passwords might not be required based on settings.
	const registrationGeneratePassword = getSetting(
		'registrationGeneratePassword',
		false
	);
	const needsPassword = ! registrationGeneratePassword && ! password;

	return (
		<form
			className={ 'wc-block-order-confirmation-create-account-form' }
			id="create-account"
			method="POST"
			action="#create-account"
			onSubmit={ ( event ) => {
				if ( hasValidationError ) {
					event.preventDefault();
					return;
				}
				setIsLoading( true );
			} }
		>
			{ ! registrationGeneratePassword && (
				<>
					<p>
						{ createInterpolateElement(
							__( 'Set a password for <email/>', 'woocommerce' ),
							{
								email: <strong>{ customerEmail }</strong>,
							}
						) }
					</p>
					<PasswordField
						isLoading={ isLoading }
						password={ password }
						setPassword={ setPassword }
					/>
				</>
			) }
			<Button
				className={
					'wc-block-order-confirmation-create-account-button'
				}
				type="submit"
				disabled={ !! hasValidationError || needsPassword || isLoading }
				showSpinner={ isLoading }
			>
				{ __( 'Create account', 'woocommerce' ) }
			</Button>
			<input type="hidden" name="email" value={ customerEmail } />
			<input type="hidden" name="password" value={ password } />
			<input type="hidden" name="create-account" value="1" />
			<input type="hidden" name="_wpnonce" value={ nonceToken } />
			<div className="wc-block-order-confirmation-create-account-description">
				{ registrationGeneratePassword && (
					<p>
						{ __(
							"We'll email you a link to set up an account password.",
							'woocommerce'
						) }
					</p>
				) }
				<p>
					{ createInterpolateElement(
						/* translators: %1$s terms page link, %2$s privacy page link. */
						__(
							'By creating an account you agree to our <terms/> and <privacy/>.',
							'woocommerce'
						),
						{ terms: termsPageLink, privacy: privacyPageLink }
					) }
				</p>
			</div>
		</form>
	);
};

export default Form;
