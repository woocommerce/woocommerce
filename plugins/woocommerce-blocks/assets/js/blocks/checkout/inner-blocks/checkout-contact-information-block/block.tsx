/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useCheckoutAddress,
	useStoreEvents,
	noticeContexts,
} from '@woocommerce/base-context';
import { ContactFormValues, getSetting } from '@woocommerce/settings';
import {
	StoreNoticesContainer,
	CheckboxControl,
	ValidatedTextInput,
} from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { CONTACT_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';

const Block = (): JSX.Element => {
	const {
		additionalFields,
		customerId,
		customerPassword,
		shouldCreateAccount,
	} = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			additionalFields: store.getAdditionalFields(),
			customerId: store.getCustomerId(),
			customerPassword: store.getCustomerPassword(),
			shouldCreateAccount: store.getShouldCreateAccount(),
		};
	} );

	const {
		__internalSetShouldCreateAccount,
		setAdditionalFields,
		__internalSetCustomerPassword,
	} = useDispatch( CHECKOUT_STORE_KEY );
	const { billingAddress, setEmail } = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const onChangeEmail = ( value: string ) => {
		setEmail( value );
		dispatchCheckoutEvent( 'set-email-address' );
	};

	const createAccountVisible =
		! customerId &&
		getSetting( 'checkoutAllowsGuest', false ) &&
		getSetting( 'checkoutAllowsSignup', false );

	const generatePassword = getSetting( 'generatePassword', false );

	const onChangeForm = ( newAddress: ContactFormValues ) => {
		const { email, ...additionalValues } = newAddress;
		onChangeEmail( email );
		setAdditionalFields( additionalValues );
	};

	const contactFormValues = {
		email: billingAddress.email,
		...additionalFields,
	};

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.CONTACT_INFORMATION }
			/>
			<Form< ContactFormValues >
				id="contact"
				addressType="contact"
				onChange={ onChangeForm }
				values={ contactFormValues }
				fields={ CONTACT_FORM_KEYS }
			>
				{ createAccountVisible && ! customerId && (
					<p className="guest-checkout-notice">
						{ __(
							'You are currently checking out as a guest.',
							'woocommerce'
						) }
					</p>
				) }
				{ createAccountVisible && (
					<>
						<CheckboxControl
							className="wc-block-checkout__create-account"
							label={ sprintf(
								/* translators: Store name */
								__(
									'Create an account with %s',
									'woocommerce'
								),
								getSetting( 'siteTitle', '' )
							) }
							checked={ shouldCreateAccount }
							onChange={ ( value ) => {
								__internalSetShouldCreateAccount( value );
								__internalSetCustomerPassword( '' );
							} }
						/>
						{ shouldCreateAccount && ! generatePassword && (
							<ValidatedTextInput
								type="password"
								label={ __(
									'Create a password',
									'woocommerce'
								) }
								className={ `wc-block-components-address-form__password` }
								value={ customerPassword || '' }
								onChange={ ( value: string ) =>
									__internalSetCustomerPassword( value )
								}
							/>
						) }
					</>
				) }
			</Form>
		</>
	);
};

export default Block;
