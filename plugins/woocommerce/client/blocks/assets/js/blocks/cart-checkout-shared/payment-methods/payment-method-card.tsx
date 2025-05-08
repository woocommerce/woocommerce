/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEditorContext } from '@woocommerce/base-context';
import { CheckboxControl } from '@woocommerce/blocks-components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	checkoutStore as checkoutStoreDescriptor,
	paymentStore,
} from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import PaymentMethodErrorBoundary from './payment-method-error-boundary';

/**
 * Component used to render the contents of a payment method card.
 *
 * @param {Object}  props                Incoming props for the component.
 * @param {boolean} props.showSaveOption Whether that payment method allows saving
 *                                       the data for future purchases.
 * @param {Object}  props.children       Content of the payment method card.
 *
 * @return {*} The rendered component.
 */
interface PaymentMethodCardProps {
	showSaveOption: boolean;
	children: React.ReactNode;
}
const PaymentMethodCard = ( {
	children,
	showSaveOption,
}: PaymentMethodCardProps ) => {
	const { isEditor } = useEditorContext();
	const { shouldSavePaymentMethod, customerId, shouldCreateAccount } =
		useSelect( ( select ) => {
			const paymentMethodStore = select( paymentStore );
			const checkoutStore = select( checkoutStoreDescriptor );
			return {
				shouldSavePaymentMethod:
					paymentMethodStore.getShouldSavePaymentMethod(),
				customerId: checkoutStore.getCustomerId(),
				shouldCreateAccount: checkoutStore.getShouldCreateAccount(),
			};
		}, [] );

	const { __internalSetShouldSavePaymentMethod } =
		useDispatch( paymentStore );

	const allowGuestCheckout = getSetting( 'checkoutAllowsGuest', false );

	// Work out if the customer can save the payment method.
	const canSavePaymentMethod =
		// They're already logged in.
		customerId > 0 ||
		// They're not logged in, but they're creating an account.
		shouldCreateAccount ||
		// They're not logged in, but they must create an account.
		! allowGuestCheckout;

	useEffect( () => {
		if ( ! canSavePaymentMethod && shouldSavePaymentMethod ) {
			__internalSetShouldSavePaymentMethod( false );
		}
	}, [
		canSavePaymentMethod,
		shouldSavePaymentMethod,
		__internalSetShouldSavePaymentMethod,
	] );

	return (
		<PaymentMethodErrorBoundary isEditor={ isEditor }>
			{ children }
			{ canSavePaymentMethod && showSaveOption && (
				<CheckboxControl
					className="wc-block-components-payment-methods__save-card-info"
					label={ __(
						'Save payment information to my account for future purchases.',
						'woocommerce'
					) }
					checked={ shouldSavePaymentMethod }
					onChange={ () =>
						__internalSetShouldSavePaymentMethod(
							! shouldSavePaymentMethod
						)
					}
				/>
			) }
		</PaymentMethodErrorBoundary>
	);
};

export default PaymentMethodCard;
