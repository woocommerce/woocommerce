/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import PaymentMethodLabel from '@woocommerce/base-components/cart-checkout/payment-method-label';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { getSetting } from '@woocommerce/settings';
import deprecated from '@wordpress/deprecated';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { type PaymentMethodInterface, responseTypes } from '@woocommerce/types';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	checkoutStore,
	paymentStore,
	cartStore,
} from '@woocommerce/block-data';
import { checkoutEvents } from '@woocommerce/blocks-checkout-events';
import { ValidationInputError } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import { useStoreCart } from '../cart/use-store-cart';
import { useStoreCartCoupons } from '../cart/use-store-cart-coupons';
import { noticeContexts } from '../../event-emit';
import { useCheckoutEventsContext } from '../../providers/cart-checkout/checkout-events';
import { usePaymentEventsContext } from '../../providers/cart-checkout/payment-events';
import { useShippingDataContext } from '../../providers/cart-checkout/shipping';
import { prepareTotalItems } from './utils';
import { useShippingData } from '../shipping/use-shipping-data';

/**
 * Returns am interface to use as payment method props.
 */
export const usePaymentMethodInterface = (): PaymentMethodInterface => {
	const {
		onCheckoutBeforeProcessing,
		onCheckoutValidationBeforeProcessing,
		onCheckoutAfterProcessingWithSuccess,
		onCheckoutAfterProcessingWithError,
		onSubmit,
	} = useCheckoutEventsContext();

	const { onCheckoutValidation, onCheckoutSuccess, onCheckoutFail } =
		checkoutEvents;

	const { isCalculating, isComplete, isIdle, isProcessing, customerId } =
		useSelect( ( select ) => {
			const store = select( checkoutStore );
			return {
				isComplete: store.isComplete(),
				isIdle: store.isIdle(),
				isProcessing: store.isProcessing(),
				customerId: store.getCustomerId(),
				isCalculating: store.isCalculating(),
			};
		}, [] );

	const {
		paymentIsIdle,
		paymentIsStarted,
		paymentIsProcessing,
		paymentHasError,
		paymentIsReady,
		paymentIsDoingExpressPayment,
		activePaymentMethod,
		shouldSavePayment,
	} = useSelect( ( select ) => {
		const store = select( paymentStore );

		return {
			paymentIsIdle: store.isPaymentIdle(),
			paymentIsStarted: store.isExpressPaymentStarted(),
			paymentIsProcessing: store.isPaymentProcessing(),
			paymentHasError: store.hasPaymentError(),
			paymentIsReady: store.isPaymentReady(),
			paymentIsDoingExpressPayment: store.isExpressPaymentMethodActive(),
			activePaymentMethod: store.getActivePaymentMethod(),
			shouldSavePayment: store.getShouldSavePaymentMethod(),
		};
	}, [] );

	// The paymentStatus is exposed to third parties via the payment method interface so the API must not be changed.
	const paymentStatus = {
		isIdle: paymentIsIdle,
		isStarted: paymentIsStarted,
		isProcessing: paymentIsProcessing,
		hasError: paymentHasError,
		isReady: paymentIsReady,
		isDoingExpressPayment: paymentIsDoingExpressPayment,
		get isPristine() {
			deprecated( 'isPristine', {
				since: '9.6.0',
				alternative: 'isIdle',
				plugin: 'WooCommerce Blocks',
				link: 'https://github.com/woocommerce/woocommerce-blocks/pull/8110',
			} );
			return paymentIsIdle;
		},
		get isFinished() {
			deprecated( 'isFinished', {
				since: '9.6.0',
				plugin: 'WooCommerce Blocks',
				link: 'https://github.com/woocommerce/woocommerce-blocks/pull/8110',
			} );
			return paymentHasError || paymentIsReady;
		},
		get hasFailed() {
			deprecated( 'hasFailed', {
				since: '9.6.0',
				plugin: 'WooCommerce Blocks',
				link: 'https://github.com/woocommerce/woocommerce-blocks/pull/8110',
			} );
			return paymentHasError;
		},
		get isSuccessful() {
			deprecated( 'isSuccessful', {
				since: '9.6.0',
				plugin: 'WooCommerce Blocks',
				link: 'https://github.com/woocommerce/woocommerce-blocks/pull/8110',
			} );
			return paymentIsReady;
		},
	};

	const { __internalSetExpressPaymentError } = useDispatch( paymentStore );

	const { onPaymentProcessing, onPaymentSetup } = usePaymentEventsContext();
	const {
		shippingErrorStatus,
		shippingErrorTypes,
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
	} = useShippingDataContext();
	const {
		shippingRates,
		isLoadingRates,
		selectedRates,
		isSelectingRate,
		selectShippingRate,
		needsShipping,
	} = useShippingData();

	const { billingAddress, shippingAddress } = useSelect(
		( select ) => select( cartStore ).getCustomerData(),
		[]
	);
	const { setShippingAddress } = useDispatch( cartStore );
	const { cartItems, cartFees, cartTotals, extensions } = useStoreCart();
	const { appliedCoupons } = useStoreCartCoupons();
	const currentCartTotals = useRef(
		prepareTotalItems( cartTotals, needsShipping )
	);
	const currentCartTotal = useRef( {
		label: __( 'Total', 'woocommerce' ),
		value: parseInt( cartTotals.total_price, 10 ),
	} );

	useEffect( () => {
		currentCartTotals.current = prepareTotalItems(
			cartTotals,
			needsShipping
		);
		currentCartTotal.current = {
			label: __( 'Total', 'woocommerce' ),
			value: parseInt( cartTotals.total_price, 10 ),
		};
	}, [ cartTotals, needsShipping ] );

	const deprecatedSetExpressPaymentError = useCallback(
		( errorMessage = '' ) => {
			deprecated(
				'setExpressPaymentError should only be used by Express Payment Methods (using the provided onError handler).',
				{
					alternative: '',
					plugin: 'woocommerce-gutenberg-products-block',
					link: 'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4228',
				}
			);
			__internalSetExpressPaymentError( errorMessage );
		},
		[ __internalSetExpressPaymentError ]
	);

	return {
		activePaymentMethod,
		billing: {
			appliedCoupons,
			billingAddress,
			billingData: billingAddress,
			cartTotal: currentCartTotal.current,
			cartTotalItems: currentCartTotals.current,
			currency: getCurrencyFromPriceResponse( cartTotals ),
			customerId,
			displayPricesIncludingTax: getSetting(
				'displayCartPricesIncludingTax',
				false
			) as boolean,
		},
		cartData: {
			cartItems,
			cartFees,
			extensions,
		},
		checkoutStatus: {
			isCalculating,
			isComplete,
			isIdle,
			isProcessing,
		},
		components: {
			LoadingMask,
			PaymentMethodIcons,
			PaymentMethodLabel,
			ValidationInputError,
		},
		emitResponse: {
			noticeContexts,
			responseTypes,
		},
		eventRegistration: {
			onCheckoutAfterProcessingWithError,
			onCheckoutAfterProcessingWithSuccess,
			onCheckoutBeforeProcessing,
			onCheckoutValidationBeforeProcessing,
			onCheckoutSuccess,
			onCheckoutFail,
			onCheckoutValidation,
			onPaymentProcessing,
			onPaymentSetup,
			onShippingRateFail,
			onShippingRateSelectFail,
			onShippingRateSelectSuccess,
			onShippingRateSuccess,
		},
		onSubmit,
		paymentStatus,
		setExpressPaymentError: deprecatedSetExpressPaymentError,
		shippingData: {
			isSelectingRate,
			needsShipping,
			selectedRates,
			setSelectedRates: selectShippingRate,
			setShippingAddress,
			shippingAddress,
			shippingRates,
			shippingRatesLoading: isLoadingRates,
		},
		shippingStatus: {
			shippingErrorStatus,
			shippingErrorTypes,
		},
		shouldSavePayment,
	};
};
