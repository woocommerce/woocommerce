/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';
import {
	useEffect,
	useRef,
	useCallback,
	useState,
	useMemo,
} from '@wordpress/element';
import {
	emptyHiddenAddressFields,
	formatStoreApiErrorMessage,
} from '@woocommerce/base-utils';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	CHECKOUT_STORE_KEY,
	PAYMENT_METHOD_DATA_STORE_KEY,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { preparePaymentData, processCheckoutResponseHeaders } from './utils';
import { useCheckoutEventsContext } from './checkout-events';
import { useShippingDataContext } from './shipping';
import { useCustomerDataContext } from './customer';
import { useStoreCart } from '../../hooks/cart/use-store-cart';

/**
 * CheckoutProcessor component.
 *
 * Subscribes to checkout context and triggers processing via the API.
 */
const CheckoutProcessor = () => {
	const { onCheckoutValidationBeforeProcessing } = useCheckoutEventsContext();

	const {
		hasError: checkoutHasError,
		redirectUrl,
		isProcessing: checkoutIsProcessing,
		isBeforeProcessing: checkoutIsBeforeProcessing,
		isComplete: checkoutIsComplete,
		orderNotes,
		shouldCreateAccount,
		extensionData,
	} = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			...store.getCheckoutState(),
			isProcessing: store.isProcessing(),
			isBeforeProcessing: store.isBeforeProcessing(),
			isComplete: store.isComplete(),
		};
	} );

	const { setHasError, processCheckoutResponse } =
		useDispatch( CHECKOUT_STORE_KEY );

	const hasValidationErrors = useSelect(
		( select ) => select( VALIDATION_STORE_KEY ).hasValidationErrors
	);
	const { shippingErrorStatus } = useShippingDataContext();
	const { billingAddress, shippingAddress } = useCustomerDataContext();
	const { cartNeedsPayment, cartNeedsShipping, receiveCart } = useStoreCart();
	const { createErrorNotice, removeNotice } = useDispatch( 'core/notices' );

	const {
		activePaymentMethod,
		paymentMethodData,
		isExpressPaymentMethodActive,
		currentPaymentStatus,
		shouldSavePayment,
	} = useSelect( ( select ) => {
		const store = select( PAYMENT_METHOD_DATA_STORE_KEY );

		return {
			activePaymentMethod: store.getActivePaymentMethod(),
			paymentMethodData: store.getPaymentMethodData(),
			isExpressPaymentMethodActive: store.isExpressPaymentMethodActive(),
			currentPaymentStatus: store.getCurrentStatus(),
			shouldSavePayment: store.shouldSavePaymentMethod(),
		};
	}, [] );

	const paymentMethods = getPaymentMethods();
	const expressPaymentMethods = getExpressPaymentMethods();
	const currentBillingAddress = useRef( billingAddress );
	const currentShippingAddress = useRef( shippingAddress );
	const currentRedirectUrl = useRef( redirectUrl );
	const [ isProcessingOrder, setIsProcessingOrder ] = useState( false );

	const paymentMethodId = useMemo( () => {
		const merged = {
			...expressPaymentMethods,
			...paymentMethods,
		};
		return merged?.[ activePaymentMethod ]?.paymentMethodId;
	}, [ activePaymentMethod, expressPaymentMethods, paymentMethods ] );

	const checkoutWillHaveError =
		( hasValidationErrors() && ! isExpressPaymentMethodActive ) ||
		currentPaymentStatus.hasError ||
		shippingErrorStatus.hasError;

	const paidAndWithoutErrors =
		! checkoutHasError &&
		! checkoutWillHaveError &&
		( currentPaymentStatus.isSuccessful || ! cartNeedsPayment ) &&
		checkoutIsProcessing;

	// Determine if checkout has an error.
	useEffect( () => {
		if (
			checkoutWillHaveError !== checkoutHasError &&
			( checkoutIsProcessing || checkoutIsBeforeProcessing ) &&
			! isExpressPaymentMethodActive
		) {
			setHasError( checkoutWillHaveError );
		}
	}, [
		checkoutWillHaveError,
		checkoutHasError,
		checkoutIsProcessing,
		checkoutIsBeforeProcessing,
		isExpressPaymentMethodActive,
		setHasError,
	] );

	// Keep the billing, shipping and redirectUrl current
	useEffect( () => {
		currentBillingAddress.current = billingAddress;
		currentShippingAddress.current = shippingAddress;
		currentRedirectUrl.current = redirectUrl;
	}, [ billingAddress, shippingAddress, redirectUrl ] );

	const checkValidation = useCallback( () => {
		if ( hasValidationErrors() ) {
			return false;
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

	// Validate the checkout using the CHECKOUT_VALIDATION_BEFORE_PROCESSING event
	useEffect( () => {
		let unsubscribeProcessing;
		if ( ! isExpressPaymentMethodActive ) {
			unsubscribeProcessing = onCheckoutValidationBeforeProcessing(
				checkValidation,
				0
			);
		}
		return () => {
			if ( ! isExpressPaymentMethodActive ) {
				unsubscribeProcessing();
			}
		};
	}, [
		onCheckoutValidationBeforeProcessing,
		checkValidation,
		isExpressPaymentMethodActive,
	] );

	// Redirect when checkout is complete and there is a redirect url.
	useEffect( () => {
		if ( currentRedirectUrl.current ) {
			window.location.href = currentRedirectUrl.current;
		}
	}, [ checkoutIsComplete ] );

	// POST to the Store API and process and display any errors, or set order complete
	const processOrder = useCallback( async () => {
		if ( isProcessingOrder ) {
			return;
		}
		setIsProcessingOrder( true );
		removeNotice( 'checkout' );

		const paymentData = cartNeedsPayment
			? {
					payment_method: paymentMethodId,
					payment_data: preparePaymentData(
						paymentMethodData,
						shouldSavePayment,
						activePaymentMethod
					),
			  }
			: {};

		const data = {
			billing_address: emptyHiddenAddressFields(
				currentBillingAddress.current
			),
			customer_note: orderNotes,
			create_account: shouldCreateAccount,
			...paymentData,
			extensions: { ...extensionData },
		};

		if ( cartNeedsShipping ) {
			data.shipping_address = emptyHiddenAddressFields(
				currentShippingAddress.current
			);
		}

		triggerFetch( {
			path: '/wc/store/v1/checkout',
			method: 'POST',
			data,
			cache: 'no-store',
			parse: false,
		} )
			.then( ( response ) => {
				processCheckoutResponseHeaders( response.headers );
				if ( ! response.ok ) {
					throw new Error( response );
				}
				return response.json();
			} )
			.then( ( responseJson ) => {
				processCheckoutResponse( responseJson );
				setIsProcessingOrder( false );
			} )
			.catch( ( errorResponse ) => {
				try {
					if ( errorResponse?.headers ) {
						processCheckoutResponseHeaders( errorResponse.headers );
					}
					// This attempts to parse a JSON error response where the status code was 4xx/5xx.
					errorResponse.json().then( ( response ) => {
						// If updated cart state was returned, update the store.
						if ( response.data?.cart ) {
							receiveCart( response.data.cart );
						}
						createErrorNotice(
							formatStoreApiErrorMessage( response ),
							{
								id: 'checkout',
								context: 'wc/checkout',
								__unstableHTML: true,
							}
						);
						response?.additional_errors?.forEach?.(
							( additionalError ) => {
								createErrorNotice( additionalError.message, {
									id: additionalError.error_code,
									context: 'wc/checkout',
									__unstableHTML: true,
								} );
							}
						);
						processCheckoutResponse( response );
					} );
				} catch {
					createErrorNotice(
						sprintf(
							// Translators: %s Error text.
							__(
								'%s Please try placing your order again.',
								'woo-gutenberg-products-block'
							),
							errorResponse?.message ??
								__(
									'Something went wrong. Please contact us for assistance.',
									'woo-gutenberg-products-block'
								)
						),
						{
							id: 'checkout',
							context: 'wc/checkout',
							__unstableHTML: true,
						}
					);
				}
				setHasError( true );
				setIsProcessingOrder( false );
			} );
	}, [
		isProcessingOrder,
		removeNotice,
		cartNeedsPayment,
		paymentMethodId,
		paymentMethodData,
		shouldSavePayment,
		activePaymentMethod,
		orderNotes,
		shouldCreateAccount,
		extensionData,
		cartNeedsShipping,
		createErrorNotice,
		receiveCart,
		setHasError,
		processCheckoutResponse,
	] );

	// Process order if conditions are good.
	useEffect( () => {
		if ( paidAndWithoutErrors && ! isProcessingOrder ) {
			processOrder();
		}
	}, [ processOrder, paidAndWithoutErrors, isProcessingOrder ] );

	return null;
};

export default CheckoutProcessor;
