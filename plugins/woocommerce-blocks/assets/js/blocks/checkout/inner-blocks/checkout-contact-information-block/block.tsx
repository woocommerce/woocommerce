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

const CreateAccountUI = (): React.ReactElement => {
	const { customerPassword, shouldCreateAccount } = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			customerPassword: store.getCustomerPassword(),
			shouldCreateAccount: store.getShouldCreateAccount(),
		};
	} );
	const { __internalSetShouldCreateAccount, __internalSetCustomerPassword } =
		useDispatch( CHECKOUT_STORE_KEY );

	// Work out what fields need to be displayed for the current shopper.
	const allowGuestCheckout = getSetting( 'checkoutAllowsGuest', false );
	const allowSignup = getSetting( 'checkoutAllowsSignup', false );
	const generatePassword = getSetting( 'generatePassword', false );
	const showCreateAccountCheckbox = allowGuestCheckout && allowSignup;
	const showCreateAccountPassword = generatePassword
		? false
		: ( showCreateAccountCheckbox && shouldCreateAccount ) ||
		  ! allowGuestCheckout;

	return (
		<>
			{ allowGuestCheckout && (
				<p className="guest-checkout-notice">
					{ __(
						'You are currently checking out as a guest.',
						'woocommerce'
					) }
				</p>
			) }
			{ showCreateAccountCheckbox && (
				<CheckboxControl
					className="wc-block-checkout__create-account"
					label={ sprintf(
						/* translators: Store name */
						__( 'Create an account with %s', 'woocommerce' ),
						getSetting( 'siteTitle', '' )
					) }
					checked={ shouldCreateAccount }
					onChange={ ( value ) => {
						__internalSetShouldCreateAccount( value );
						__internalSetCustomerPassword( '' );
					} }
				/>
			) }
			{ showCreateAccountPassword && (
				<ValidatedTextInput
					type="password"
					label={ __( 'Create a password', 'woocommerce' ) }
					className={ `wc-block-components-address-form__password` }
					value={ customerPassword }
					required={ true }
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
					onChange={ ( value: string ) =>
						__internalSetCustomerPassword( value )
					}
				/>
			) }
		</>
	);
};

const Block = (): JSX.Element => {
	const { additionalFields, customerId } = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			additionalFields: store.getAdditionalFields(),
			customerId: store.getCustomerId(),
		};
	} );

	const { setAdditionalFields } = useDispatch( CHECKOUT_STORE_KEY );
	const { billingAddress, setEmail } = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const onChangeEmail = ( value: string ) => {
		setEmail( value );
		dispatchCheckoutEvent( 'set-email-address' );
	};
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
				{ ! customerId && <CreateAccountUI /> }
			</Form>
		</>
	);
};

export default Block;
