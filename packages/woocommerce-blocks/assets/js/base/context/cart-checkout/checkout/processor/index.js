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
import {
	useEffect,
	useRef,
	useCallback,
	useState,
	useMemo,
} from '@wordpress/element';
import { useStoreCart, useStoreNotices } from '@woocommerce/base-hooks';

/**
 * @typedef {import('@woocommerce/type-defs/payments').PaymentDataItem} PaymentDataItem
 */

/**
 * Utility function for preparing payment data for the request.
 *
 * @param {Object}  paymentData          Arbitrary payment data provided by the payment method.
 * @param {boolean} shouldSave           Whether to save the payment method info to user account.
 * @param {Object}  activePaymentMethod  The current active payment method.
 *
 * @return {PaymentDataItem[]} Returns the payment data as an array of
 *                             PaymentDataItem objects.
 */
const preparePaymentData = ( paymentData, shouldSave, activePaymentMethod ) => {
	const apiData = Object.keys( paymentData ).map( ( property ) => {
		const value = paymentData[ property ];
		return { key: property, value };
	}, [] );
	const savePaymentMethodKey = `wc-${ activePaymentMethod }-new-payment-method`;
	apiData.push( {
		key: savePaymentMethodKey,
		value: shouldSave,
	} );
	return apiData;
};

/**
 * CheckoutProcessor component.
 *
 * @todo Needs to consume all contexts.
 *
 * Subscribes to checkout context and triggers processing via the API.
 */
const CheckoutProcessor = () => {
	const {
		hasError: checkoutHasError,
		onCheckoutBeforeProcessing,
		dispatchActions,
		redirectUrl,
		isProcessing: checkoutIsProcessing,
		isBeforeProcessing: checkoutIsBeforeProcessing,
		isComplete: checkoutIsComplete,
	} = useCheckoutContext();
	const { hasValidationErrors } = useValidationContext();
	const { shippingAddress, shippingErrorStatus } = useShippingDataContext();
	const { billingData } = useBillingDataContext();
	const { cartNeedsPayment, receiveCart } = useStoreCart();
	const {
		activePaymentMethod,
		currentStatus: currentPaymentStatus,
		paymentMethodData,
		expressPaymentMethods,
		paymentMethods,
		shouldSavePayment,
	} = usePaymentMethodDataContext();
	const { addErrorNotice, removeNotice, setIsSuppressed } = useStoreNotices();
	const currentBillingData = useRef( billingData );
	const currentShippingAddress = useRef( shippingAddress );
	const currentRedirectUrl = useRef( redirectUrl );
	const [ isProcessingOrder, setIsProcessingOrder ] = useState( false );
	const expressPaymentMethodActive = Object.keys(
		expressPaymentMethods
	).includes( activePaymentMethod );

	const paymentMethodId = useMemo( () => {
		const merged = { ...expressPaymentMethods, ...paymentMethods };
		return merged?.[ activePaymentMethod ]?.paymentMethodId;
	}, [ activePaymentMethod, expressPaymentMethods, paymentMethods ] );

	const checkoutWillHaveError =
		( hasValidationErrors && ! expressPaymentMethodActive ) ||
		currentPaymentStatus.hasError ||
		shippingErrorStatus.hasError;

	// If express payment method is active, let's suppress notices
	useEffect( () => {
		setIsSuppressed( expressPaymentMethodActive );
	}, [ expressPaymentMethodActive, setIsSuppressed ] );

	useEffect( () => {
		if (
			checkoutWillHaveError !== checkoutHasError &&
			( checkoutIsProcessing || checkoutIsBeforeProcessing ) &&
			! expressPaymentMethodActive
		) {
			dispatchActions.setHasError( checkoutWillHaveError );
		}
	}, [
		checkoutWillHaveError,
		checkoutHasError,
		checkoutIsProcessing,
		checkoutIsBeforeProcessing,
		expressPaymentMethodActive,
		dispatchActions,
	] );

	const paidAndWithoutErrors =
		! checkoutHasError &&
		! checkoutWillHaveError &&
		( currentPaymentStatus.isSuccessful || ! cartNeedsPayment ) &&
		checkoutIsProcessing;

	useEffect( () => {
		currentBillingData.current = billingData;
		currentShippingAddress.current = shippingAddress;
		currentRedirectUrl.current = redirectUrl;
	}, [ billingData, shippingAddress, redirectUrl ] );

	const checkValidation = useCallback( () => {
		if ( hasValidationErrors ) {
			return {
				errorMessage: __(
					'Some input fields are invalid.',
					'woocommerce'
				),
			};
		}
		if ( currentPaymentStatus.hasError ) {
			return {
				errorMessage: __(
					'There was a problem with your payment option.',
					'woocommerce'
				),
			};
		}
		if ( shippingErrorStatus.hasError ) {
			return {
				errorMessage: __(
					'There was a problem with your shipping option.',
					'woocommerce'
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
			unsubscribeProcessing = onCheckoutBeforeProcessing(
				checkValidation,
				0
			);
		}
		return () => {
			if ( ! expressPaymentMethodActive ) {
				unsubscribeProcessing();
			}
		};
	}, [
		onCheckoutBeforeProcessing,
		checkValidation,
		expressPaymentMethodActive,
	] );

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
				payment_method: paymentMethodId,
				payment_data: preparePaymentData(
					paymentMethodData,
					shouldSavePayment,
					activePaymentMethod
				),
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
									'woocommerce'
								),
								{
									id: 'checkout',
								}
							);
						}
						dispatchActions.setHasError();
					}
					dispatchActions.setAfterProcessing( response );
					setIsProcessingOrder( false );
				} );
			} )
			.catch( ( error ) => {
				error.json().then( function( response ) {
					// If updated cart state was returned, also update that.
					if ( response.data?.cart ) {
						receiveCart( response.data.cart );
					}
					dispatchActions.setHasError();
					dispatchActions.setAfterProcessing( response );
					setIsProcessingOrder( false );
				} );
			} );
	}, [
		addErrorNotice,
		removeNotice,
		paymentMethodId,
		activePaymentMethod,
		paymentMethodData,
		shouldSavePayment,
		cartNeedsPayment,
		receiveCart,
		dispatchActions,
	] );
	// redirect when checkout is complete and there is a redirect url.
	useEffect( () => {
		if ( currentRedirectUrl.current ) {
			window.location.href = currentRedirectUrl.current;
		}
	}, [ checkoutIsComplete ] );

	// process order if conditions are good.
	useEffect( () => {
		if ( paidAndWithoutErrors && ! isProcessingOrder ) {
			processOrder();
		}
	}, [ processOrder, paidAndWithoutErrors, isProcessingOrder ] );

	return null;
};

export default CheckoutProcessor;
