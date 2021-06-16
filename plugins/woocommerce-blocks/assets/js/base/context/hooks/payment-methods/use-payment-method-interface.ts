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

/**
 * Internal dependencies
 */
import { ValidationInputError } from '../../providers/validation';
import { useStoreCart } from '../cart/use-store-cart';
import { useStoreCartCoupons } from '../cart/use-store-cart-coupons';
import { useEmitResponse } from '../use-emit-response';
import { useCheckoutContext } from '../../providers/cart-checkout/checkout-state';
import { usePaymentMethodDataContext } from '../../providers/cart-checkout/payment-methods';
import { useShippingDataContext } from '../../providers/cart-checkout/shipping';
import { useCustomerDataContext } from '../../providers/cart-checkout/customer';
import { prepareTotalItems } from './utils';

/**
 * Returns am interface to use as payment method props.
 */
export const usePaymentMethodInterface = (): Record< string, unknown > => {
	const {
		isCalculating,
		isComplete,
		isIdle,
		isProcessing,
		onCheckoutBeforeProcessing,
		onCheckoutValidationBeforeProcessing,
		onCheckoutAfterProcessingWithSuccess,
		onCheckoutAfterProcessingWithError,
		onSubmit,
		customerId,
	} = useCheckoutContext();
	const {
		currentStatus,
		activePaymentMethod,
		onPaymentProcessing,
		setExpressPaymentError,
		shouldSavePayment,
	} = usePaymentMethodDataContext();
	const {
		shippingErrorStatus,
		shippingErrorTypes,
		shippingRates,
		shippingRatesLoading,
		selectedRates,
		setSelectedRates,
		isSelectingRate,
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
		needsShipping,
	} = useShippingDataContext();
	const {
		billingData,
		shippingAddress,
		setShippingAddress,
	} = useCustomerDataContext();
	const { cartTotals } = useStoreCart();
	const { appliedCoupons } = useStoreCartCoupons();
	const { noticeContexts, responseTypes } = useEmitResponse();
	const currentCartTotals = useRef(
		prepareTotalItems( cartTotals, needsShipping )
	);
	const currentCartTotal = useRef( {
		label: __( 'Total', 'woo-gutenberg-products-block' ),
		value: parseInt( cartTotals.total_price, 10 ),
	} );

	useEffect( () => {
		currentCartTotals.current = prepareTotalItems(
			cartTotals,
			needsShipping
		);
		currentCartTotal.current = {
			label: __( 'Total', 'woo-gutenberg-products-block' ),
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
					link:
						'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4228',
				}
			);
			setExpressPaymentError( errorMessage );
		},
		[ setExpressPaymentError ]
	);

	return {
		activePaymentMethod,
		billing: {
			billingData,
			cartTotal: currentCartTotal.current,
			currency: getCurrencyFromPriceResponse( cartTotals ),
			cartTotalItems: currentCartTotals.current,
			displayPricesIncludingTax: getSetting(
				'displayCartPricesIncludingTax',
				false
			) as boolean,
			appliedCoupons,
			customerId,
		},
		checkoutStatus: {
			isCalculating,
			isComplete,
			isIdle,
			isProcessing,
		},
		components: {
			ValidationInputError,
			PaymentMethodIcons,
			PaymentMethodLabel,
		},
		emitResponse: {
			noticeContexts,
			responseTypes,
		},
		eventRegistration: {
			onCheckoutBeforeProcessing,
			onCheckoutValidationBeforeProcessing,
			onCheckoutAfterProcessingWithSuccess,
			onCheckoutAfterProcessingWithError,
			onShippingRateSuccess,
			onShippingRateFail,
			onShippingRateSelectSuccess,
			onShippingRateSelectFail,
			onPaymentProcessing,
		},
		onSubmit,
		paymentStatus: currentStatus,
		setExpressPaymentError: deprecatedSetExpressPaymentError,
		shippingData: {
			shippingRates,
			shippingRatesLoading,
			selectedRates,
			setSelectedRates,
			isSelectingRate,
			shippingAddress,
			setShippingAddress,
			needsShipping,
		},
		shippingStatus: {
			shippingErrorStatus,
			shippingErrorTypes,
		},
		shouldSavePayment,
	};
};
