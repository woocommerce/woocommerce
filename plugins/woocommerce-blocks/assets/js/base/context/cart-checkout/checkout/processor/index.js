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
 * CheckoutProcessor component. @todo Needs to consume all contexts.
 *
 * Subscribes to checkout context and triggers processing via the API.
 */
const CheckoutProcessor = () => {
	const {
		hasError,
		onCheckoutProcessing,
		dispatchActions,
	} = useCheckoutContext();
	const { hasValidationErrors } = useValidationContext();
	const { shippingAddress } = useShippingDataContext();
	const { billingData } = useBillingDataContext();
	const {
		activePaymentMethod,
		currentStatus: currentPaymentStatus,
		errorMessage,
	} = usePaymentMethodDataContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const currentBillingData = useRef( billingData );
	const currentShippingAddress = useRef( shippingAddress );

	const withErrors = hasValidationErrors();
	const paidAndWithoutErrors =
		! hasError && ! withErrors && currentPaymentStatus.isSuccessful;

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

	/**
	 * Process an order via the /wc/store/checkout endpoint.
	 *
	 * @return {boolean} True if everything was successful.
	 */
	useEffect( () => {
		if ( ! paidAndWithoutErrors ) {
			return;
		}

		triggerFetch( {
			path: '/wc/store/checkout',
			method: 'POST',
			data: {
				payment_method: activePaymentMethod,
				// @todo Hook this up to payment method data.
				payment_data: [],
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
					}

					dispatchActions.setRedirectUrl(
						response.payment_result.redirect_url
					);
				} );
			} )
			.catch( ( error ) => {
				addErrorNotice( error.message, {
					id: 'checkout',
				} );
			} );
	}, [
		addErrorNotice,
		activePaymentMethod,
		currentBillingData,
		currentShippingAddress,
		paidAndWithoutErrors,
	] );

	useEffect( () => {
		const unsubscribeProcessing = onCheckoutProcessing( checkValidation );
		return () => {
			unsubscribeProcessing();
		};
	}, [ onCheckoutProcessing, checkValidation ] );

	return null;
};

export default CheckoutProcessor;
