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
import { useEffect, useRef, useCallback, useState } from '@wordpress/element';
import { useStoreCart, useStoreNotices } from '@woocommerce/base-hooks';

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
		hasError: checkoutHasError,
		onCheckoutProcessing,
		onCheckoutCompleteSuccess,
		dispatchActions,
		redirectUrl,
		isProcessing: checkoutIsProcessing,
		isProcessingComplete: checkoutIsProcessingComplete,
	} = useCheckoutContext();
	const { hasValidationErrors } = useValidationContext();
	const { shippingAddress, shippingErrorStatus } = useShippingDataContext();
	const { billingData } = useBillingDataContext();
	const { cartNeedsPayment } = useStoreCart();
	const {
		activePaymentMethod,
		currentStatus: currentPaymentStatus,
		errorMessage,
		paymentMethodData,
		expressPaymentMethods,
	} = usePaymentMethodDataContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const currentBillingData = useRef( billingData );
	const currentShippingAddress = useRef( shippingAddress );
	const [ isProcessingOrder, setIsProcessingOrder ] = useState( false );
	const expressPaymentMethodActive = Object.keys(
		expressPaymentMethods
	).includes( activePaymentMethod );

	const checkoutWillHaveError =
		( hasValidationErrors && ! expressPaymentMethodActive ) ||
		currentPaymentStatus.hasError ||
		shippingErrorStatus.hasError;

	useEffect( () => {
		if (
			checkoutWillHaveError !== checkoutHasError &&
			( checkoutIsProcessing || checkoutIsProcessingComplete ) &&
			! expressPaymentMethodActive
		) {
			dispatchActions.setHasError( checkoutWillHaveError );
		}
	}, [
		checkoutWillHaveError,
		checkoutHasError,
		checkoutIsProcessing,
		checkoutIsProcessingComplete,
		expressPaymentMethodActive,
	] );

	const paidAndWithoutErrors =
		! checkoutHasError &&
		! checkoutWillHaveError &&
		( currentPaymentStatus.isSuccessful || ! cartNeedsPayment ) &&
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
		if ( hasValidationErrors ) {
			return {
				errorMessage: __(
					'Some input fields are invalid.',
					'woo-gutenberg-products-block'
				),
			};
		}
		if ( currentPaymentStatus.hasError ) {
			return {
				errorMessage: __(
					'There was a problem with your payment option.',
					'woo-gutenberg-products-block'
				),
			};
		}
		if ( shippingErrorStatus.hasError ) {
			return {
				errorMessage: __(
					'There was a problem with your shipping option.',
					'woo-gutenberg-products-block'
				),
			};
		}

		return true;
	}, [
		hasValidationErrors,
		currentPaymentStatus.hasError,
		shippingErrorStatus.hasError,
	] );

	useEffect( () => {
		let unsubscribeProcessing;
		if ( ! expressPaymentMethodActive ) {
			unsubscribeProcessing = onCheckoutProcessing( checkValidation, 0 );
		}
		return () => {
			if ( ! expressPaymentMethodActive ) {
				unsubscribeProcessing();
			}
		};
	}, [ onCheckoutProcessing, checkValidation, expressPaymentMethodActive ] );

	const processOrder = useCallback( () => {
		setIsProcessingOrder( true );
		removeNotice( 'checkout' );
		let data = {
			billing_address: currentBillingData.current,
			shipping_address: currentShippingAddress.current,
			customer_note: '',
		};
		if ( cartNeedsPayment ) {
			data = {
				...data,
				payment_method: activePaymentMethod,
				payment_data: preparePaymentData( paymentMethodData ),
			};
		}
		triggerFetch( {
			path: '/wc/store/checkout',
			method: 'POST',
			data,
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
									'Something went wrong. Please contact us to get assistance.',
									'woo-gutenberg-products-block'
								),
								{
									id: 'checkout',
								}
							);
						}
						dispatchActions.setHasError();
					} else {
						dispatchActions.setRedirectUrl(
							response.payment_result.redirect_url
						);
					}

					dispatchActions.setComplete();
					setIsProcessingOrder( false );
				} );
			} )
			.catch( ( error ) => {
				const message =
					error.message ||
					__(
						'Something went wrong. Please contact us to get assistance.',
						'woo-gutenberg-products-block'
					);
				addErrorNotice( message, {
					id: 'checkout',
				} );
				dispatchActions.setHasError();
				dispatchActions.setComplete();
				setIsProcessingOrder( false );
			} );
	}, [
		addErrorNotice,
		removeNotice,
		activePaymentMethod,
		paymentMethodData,
		cartNeedsPayment,
	] );
	// setup checkout processing event observers.
	useEffect( () => {
		const unsubscribeRedirect = onCheckoutCompleteSuccess( () => {
			window.location.href = redirectUrl;
		}, 999 );
		return () => {
			unsubscribeRedirect();
		};
	}, [ onCheckoutCompleteSuccess, redirectUrl ] );

	// process order if conditions are good.
	useEffect( () => {
		if ( paidAndWithoutErrors && ! isProcessingOrder ) {
			processOrder();
		}
	}, [ processOrder, paidAndWithoutErrors, isProcessingOrder ] );

	return null;
};

export default CheckoutProcessor;
