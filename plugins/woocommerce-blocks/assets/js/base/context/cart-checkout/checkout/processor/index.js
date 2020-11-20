/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';
import {
	useCheckoutContext,
	useShippingDataContext,
	useCustomerDataContext,
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
 * Internal dependencies
 */
import { preparePaymentData } from './utils';

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
		orderNotes,
		shouldCreateAccount,
	} = useCheckoutContext();
	const { hasValidationErrors } = useValidationContext();
	const { shippingErrorStatus } = useShippingDataContext();
	const { billingData, shippingAddress } = useCustomerDataContext();
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
			customer_note: orderNotes,
			should_create_account: shouldCreateAccount,
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
				fetchResponse.json().then( function ( response ) {
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
					}
					dispatchActions.setAfterProcessing( response );
					setIsProcessingOrder( false );
				} );
			} )
			.catch( ( error ) => {
				error.json().then( function ( response ) {
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
		orderNotes,
		shouldCreateAccount,
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
