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
} from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { CONTACT_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';

const guestCheckoutNoticeId = 'wc-guest-checkout-notice';

const CreateAccountUI = (): React.ReactElement | null => {
	const { shouldCreateAccount } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			shouldCreateAccount: store.getShouldCreateAccount(),
		};
	} );
	const { __internalSetShouldCreateAccount } = useDispatch( checkoutStore );

	if ( ! getSetting( 'checkoutAllowsGuest', false ) ) {
		return null;
	}

	return (
		<>
			<p
				id={ guestCheckoutNoticeId }
				className="wc-block-checkout__guest-checkout-notice"
			>
				{ __(
					'You are currently checking out as a guest.',
					'woocommerce'
				) }
			</p>
			{ getSetting( 'checkoutAllowsSignup', false ) && (
				<CheckboxControl
					className="wc-block-checkout__create-account"
					label={ sprintf(
						/* translators: Store name */
						__( 'Create an account with %s', 'woocommerce' ),
						getSetting( 'siteTitle', '' )
					) }
					checked={ shouldCreateAccount }
					onChange={ ( value ) => {
						void __internalSetShouldCreateAccount( value );
					} }
				/>
			) }
		</>
	);
};

const Block = (): JSX.Element => {
	const { additionalFields, customerId } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			additionalFields: store.getAdditionalFields(),
			customerId: store.getCustomerId(),
		};
	} );

	const { setAdditionalFields } = useDispatch( checkoutStore );
	const { billingAddress, setEmail } = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const onChangeEmail = ( value: string ) => {
		setEmail( value );
		dispatchCheckoutEvent( 'set-email-address' );
	};
	const onChangeForm = ( newAddress: ContactFormValues ) => {
		const { email, ...additionalValues } = newAddress;
		onChangeEmail( email );
		void setAdditionalFields( additionalValues );
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
				ariaDescribedBy={ guestCheckoutNoticeId }
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
