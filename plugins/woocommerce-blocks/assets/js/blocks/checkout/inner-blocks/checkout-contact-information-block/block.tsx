/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { CONTACT_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';

const Block = (): JSX.Element => {
	const { customerId, shouldCreateAccount, additionalFields } = useSelect(
		( select ) => {
			const store = select( CHECKOUT_STORE_KEY );
			return {
				customerId: store.getCustomerId(),
				shouldCreateAccount: store.getShouldCreateAccount(),
				additionalFields: store.getAdditionalFields(),
			};
		}
	);

	const { __internalSetShouldCreateAccount, setAdditionalFields } =
		useDispatch( CHECKOUT_STORE_KEY );
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

	const createAccountUI = createAccountVisible && (
		<CheckboxControl
			className="wc-block-checkout__create-account"
			label={ __( 'Create an account?', 'woocommerce' ) }
			checked={ shouldCreateAccount }
			onChange={ ( value ) => __internalSetShouldCreateAccount( value ) }
		/>
	);

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
				{ createAccountUI }
			</Form>
		</>
	);
};

export default Block;
