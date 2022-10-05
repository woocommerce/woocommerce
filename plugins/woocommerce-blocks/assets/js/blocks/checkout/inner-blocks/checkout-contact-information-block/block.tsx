/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ValidatedTextInput } from '@woocommerce/base-components/text-input';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import { getSetting } from '@woocommerce/settings';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */

const Block = ( {
	allowCreateAccount,
}: {
	allowCreateAccount: boolean;
} ): JSX.Element => {
	const { customerId, shouldCreateAccount } = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).getCheckoutState()
	);

	const { __internalSetShouldCreateAccount } =
		useDispatch( CHECKOUT_STORE_KEY );
	const { billingAddress, setEmail } = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const onChangeEmail = ( value: string ) => {
		setEmail( value );
		dispatchCheckoutEvent( 'set-email-address' );
	};

	const createAccountUI = ! customerId &&
		allowCreateAccount &&
		getSetting( 'checkoutAllowsGuest', false ) &&
		getSetting( 'checkoutAllowsSignup', false ) && (
			<CheckboxControl
				className="wc-block-checkout__create-account"
				label={ __(
					'Create an account?',
					'woo-gutenberg-products-block'
				) }
				checked={ shouldCreateAccount }
				onChange={ ( value ) =>
					__internalSetShouldCreateAccount( value )
				}
			/>
		);

	return (
		<>
			<ValidatedTextInput
				id="email"
				type="email"
				label={ __( 'Email address', 'woo-gutenberg-products-block' ) }
				value={ billingAddress.email }
				autoComplete="email"
				onChange={ onChangeEmail }
				required={ true }
			/>
			{ createAccountUI }
		</>
	);
};

export default Block;
