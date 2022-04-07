/**
 * External dependencies
 */
import { useMemo, cloneElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { usePaymentMethodDataContext } from '@woocommerce/base-context';
import RadioControl from '@woocommerce/base-components/radio-control';
import {
	usePaymentMethodInterface,
	usePaymentMethods,
	useStoreNotices,
	useStoreEvents,
	useEmitResponse,
} from '@woocommerce/base-context/hooks';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').CustomerPaymentMethod} CustomerPaymentMethod
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 */

/**
 * Returns the option object for a cc or echeck saved payment method token.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 * @return {string} label
 */
const getCcOrEcheckLabel = ( { method, expires } ) => {
	return sprintf(
		/* translators: %1$s is referring to the payment method brand, %2$s is referring to the last 4 digits of the payment card, %3$s is referring to the expiry date.  */
		__(
			'%1$s ending in %2$s (expires %3$s)',
			'woo-gutenberg-products-block'
		),
		method.brand,
		method.last4,
		expires
	);
};

/**
 * Returns the option object for any non specific saved payment method.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 * @return {string} label
 */
const getDefaultLabel = ( { method } ) => {
	return sprintf(
		/* translators: %s is the name of the payment method gateway. */
		__( 'Saved token for %s', 'woo-gutenberg-products-block' ),
		method.gateway
	);
};

const SavedPaymentMethodOptions = () => {
	const {
		customerPaymentMethods,
		activePaymentMethod,
		setActivePaymentMethod,
		activeSavedToken,
	} = usePaymentMethodDataContext();
	const { paymentMethods } = usePaymentMethods();
	const paymentMethodInterface = usePaymentMethodInterface();
	const { noticeContexts } = useEmitResponse();
	const { removeNotice } = useStoreNotices();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const options = useMemo( () => {
		const types = Object.keys( customerPaymentMethods );
		return types
			.flatMap( ( type ) => {
				const typeMethods = customerPaymentMethods[ type ];
				return typeMethods.map( ( paymentMethod ) => {
					const isCC = type === 'cc' || type === 'echeck';
					const paymentMethodSlug = paymentMethod.method.gateway;
					return {
						name: `wc-saved-payment-method-token-${ paymentMethodSlug }`,
						label: isCC
							? getCcOrEcheckLabel( paymentMethod )
							: getDefaultLabel( paymentMethod ),
						value: paymentMethod.tokenId.toString(),
						onChange: ( token ) => {
							const savedTokenKey = `wc-${ paymentMethodSlug }-payment-token`;
							setActivePaymentMethod( paymentMethodSlug, {
								token,
								payment_method: paymentMethodSlug,
								[ savedTokenKey ]: token.toString(),
								isSavedToken: true,
							} );
							removeNotice(
								'wc-payment-error',
								noticeContexts.PAYMENTS
							);
							dispatchCheckoutEvent(
								'set-active-payment-method',
								{
									paymentMethodSlug,
								}
							);
						},
					};
				} );
			} )
			.filter( Boolean );
	}, [
		customerPaymentMethods,
		setActivePaymentMethod,
		removeNotice,
		noticeContexts.PAYMENTS,
		dispatchCheckoutEvent,
	] );

	const savedPaymentMethodHandler =
		!! activeSavedToken &&
		paymentMethods[ activePaymentMethod ] &&
		paymentMethods[ activePaymentMethod ]?.savedTokenComponent
			? cloneElement(
					paymentMethods[ activePaymentMethod ]?.savedTokenComponent,
					{ token: activeSavedToken, ...paymentMethodInterface }
			  )
			: null;

	return options.length > 0 ? (
		<>
			<RadioControl
				id={ 'wc-payment-method-saved-tokens' }
				selected={ activeSavedToken }
				options={ options }
			/>
			{ savedPaymentMethodHandler }
		</>
	) : null;
};

export default SavedPaymentMethodOptions;
