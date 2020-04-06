/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';
import {
	useCheckoutContext,
	useShippingDataContext,
	useBillingDataContext,
	usePaymentMethodDataContext,
	useValidationContext,
} from '@woocommerce/base-context';
import { useEffect, useRef, useCallback } from '@wordpress/element';
import { useStoreNotices } from '@woocommerce/base-hooks';

/**
 * @typedef {import('@woocommerce/type-defs/payments').PaymentDataItem} PaymentDataItem
 */

/**
 * Utility function for preparing payment data for the request.
 *
 * @param {Object} paymentData Arbitrary payment data provided by the payment
 *                             method.
 *
 * @return {PaymentDataItem[]} Returns the payment data as an array of
 *                                 PaymentDataItem objects.
 */
const preparePaymentData = ( paymentData ) => {
	return Object.keys( paymentData ).map( ( property ) => {
		const value = paymentData[ property ];
		return { key: property, value };
	}, [] );
};

/**
 * CheckoutProcessor component. @todo Needs to consume all contexts.
 *
 * Subscribes to checkout context and triggers processing via the API.
 */
const CheckoutProcessor = () => {
	const {
		hasError,
		onCheckoutProcessing,
		onCheckoutCompleteSuccess,
		dispatchActions,
		redirectUrl,
		isProcessingComplete: checkoutIsProcessingComplete,
	} = useCheckoutContext();
	const { hasValidationErrors } = useValidationContext();
	const { shippingAddress } = useShippingDataContext();
	const { billingData } = useBillingDataContext();
	const {
		activePaymentMethod,
		currentStatus: currentPaymentStatus,
		errorMessage,
		paymentMethodData,
	} = usePaymentMethodDataContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const currentBillingData = useRef( billingData );
	const currentShippingAddress = useRef( shippingAddress );

	const withErrors = hasValidationErrors();
	const paidAndWithoutErrors =
		! hasError &&
		! withErrors &&
		currentPaymentStatus.isSuccessful &&
		checkoutIsProcessingComplete;

	useEffect( () => {
		currentBillingData.current = billingData;
		currentShippingAddress.current = shippingAddress;
	}, [ billingData, shippingAddress ] );

	useEffect( () => {
		if ( errorMessage ) {
			addErrorNotice( errorMessage, {
				id: 'payment-method-error',
			} );
		} else {
			removeNotice( 'payment-method-error' );
		}
	}, [ errorMessage ] );

	const checkValidation = useCallback( () => {
		return ! withErrors;
	}, [ withErrors ] );

	const processOrder = useCallback( () => {
		triggerFetch( {
			path: '/wc/store/checkout',
			method: 'POST',
			data: {
				payment_method: activePaymentMethod,
				payment_data: preparePaymentData( paymentMethodData ),
				billing_address: currentBillingData.current,
				shipping_address: currentShippingAddress.current,
				customer_note: '',
			},
			cache: 'no-store',
			parse: false,
		} )
			.then( ( fetchResponse ) => {
				// Update nonce.
				triggerFetch.setNonce( fetchResponse.headers );

				// Handle response.
				fetchResponse.json().then( function( response ) {
					if ( ! fetchResponse.ok ) {
						// We received an error response.
						if ( response.body && response.body.message ) {
							addErrorNotice( response.body.message, {
								id: 'checkout',
							} );
						} else {
							addErrorNotice(
								__(
									'Something went wrong. Please check your payment details and try again.',
									'woo-gutenberg-products-block'
								),
								{
									id: 'checkout',
								}
							);
						}
						dispatchActions.setHasError();
					}

					dispatchActions.setRedirectUrl(
						response.payment_result.redirect_url
					);
					dispatchActions.setComplete();
				} );
			} )
			.catch( ( error ) => {
				addErrorNotice( error.message, {
					id: 'checkout',
				} );
				dispatchActions.setHasError();
				dispatchActions.setComplete();
			} );
	}, [
		addErrorNotice,
		activePaymentMethod,
		currentBillingData,
		currentShippingAddress,
		paymentMethodData,
	] );
	// setup checkout processing event observers.
	useEffect( () => {
		const unsubscribeValidation = onCheckoutProcessing(
			checkValidation,
			0
		);
		const unsubscribeRedirect = onCheckoutCompleteSuccess( () => {
			window.location.href = redirectUrl;
		}, 999 );
		return () => {
			unsubscribeValidation();
			unsubscribeRedirect();
		};
	}, [
		onCheckoutProcessing,
		onCheckoutCompleteSuccess,
		checkValidation,
		redirectUrl,
	] );

	// process order if conditions are good.
	useEffect( () => {
		if ( paidAndWithoutErrors ) {
			processOrder();
		}
	}, [ processOrder, paidAndWithoutErrors ] );

	return null;
};

export default CheckoutProcessor;
