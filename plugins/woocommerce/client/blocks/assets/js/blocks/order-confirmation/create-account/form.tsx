/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { PRIVACY_URL, TERMS_URL } from '@woocommerce/block-settings';
import { Spinner } from '@woocommerce/blocks-components';

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

const Form = ( {
	attributes: blockAttributes,
	isEditor,
}: {
	attributes?: { customerEmail?: string; nonceToken?: string };
	isEditor: boolean;
} ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const customerEmail =
		blockAttributes?.customerEmail ||
		( isEditor ? 'customer@email.com' : '' );
	const nonceToken = blockAttributes?.nonceToken || '';

	if ( ! customerEmail ) {
		return null;
	}

	return (
		<form
			className={ 'wc-block-order-confirmation-create-account-form' }
			id="create-account"
			method="POST"
			action="#create-account"
			onSubmit={ () => {
				setIsLoading( true );
			} }
		>
			<Button
				className={ clsx(
					'wc-block-order-confirmation-create-account-button',
					{
						'wc-block-order-confirmation-create-account-button--loading':
							isLoading,
					}
				) }
				type="submit"
				disabled={ isLoading }
			>
				{ !! isLoading && <Spinner /> }
				{ __( 'Create account', 'woocommerce' ) }
			</Button>
			<input type="hidden" name="email" value={ customerEmail } />
			<input type="hidden" name="create-account" value="1" />
			<input type="hidden" name="_wpnonce" value={ nonceToken } />
			<div className="wc-block-order-confirmation-create-account-description">
				<p>
					{ __(
						'We’ll email you a link to set your password.',
						'woocommerce'
					) }{ ' ' }
					{ createInterpolateElement(
						__(
							'By creating an account you agree to our <terms/> and <privacy/>.',
							'woocommerce'
						),
						{
							terms: termsPageLink,
							privacy: privacyPageLink,
						}
					) }
				</p>
			</div>
		</form>
	);
};

export default Form;
