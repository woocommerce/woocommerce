/**
 * External dependencies
 */
import {
	useCheckoutContext,
	usePaymentMethodDataContext,
	useShippingMethodDataContext,
} from '@woocommerce/base-context';
import { __ } from '@wordpress/i18n';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	useStoreOrder,
	useStoreCartCoupons,
	useStoreCart,
	useBillingData,
} from '..';

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').CartTotalItem} CartTotalItem
 */

// @todo similar logic is done in `blocks/cart-checkout/cart/full-cart/index.js`
// we should extract this to use in both places so it's consistent.
// @todo this will need to account for `DISPLAY_PRICES_INCLUDING_TAXES`.
/**
 * @param {Object} totals Current cart total items
 * @param {boolean} needsShipping Whether or not shipping is needed.
 *
 * @return {CartTotalItem[]}  Array for cart total items prepared for use.
 */
const prepareTotalItems = ( totals, needsShipping ) => {
	const newTotals = [];
	newTotals.push( {
		label: __( 'Subtotal:', 'woo-gutenberg-products-block' ),
		value: parseInt( totals.total_items, 10 ),
	} );
	newTotals.push( {
		label: __( 'Fees:', 'woo-gutenberg-products-block' ),
		value: parseInt( totals.total_fees, 10 ),
	} );
	newTotals.push( {
		label: __( 'Discount:', 'woo-gutenberg-products-block' ),
		value: parseInt( totals.total_discount, 10 ),
	} );
	newTotals.push( {
		label: __( 'Taxes:', 'woo-gutenberg-products-block' ),
		value: parseInt( totals.total_tax, 10 ),
	} );
	if ( needsShipping ) {
		newTotals.push( {
			label: __( 'Shipping:', 'woo-gutenberg-products-block' ),
			value: parseInt( totals.total_shipping, 10 ),
		} );
	}
	return newTotals;
};

// @todo This will expose the consistent properties used as the payment method
// interface pulled from the various contexts exposing data for the interface.
// @todo need to also include notices interfaces here (maybe?).
/**
 * @return {RegisteredPaymentMethodProps} Interface to use as payment method props.
 */
export const usePaymentMethodInterface = () => {
	const {
		isCalculating,
		isComplete,
		isIdle,
		isProcessing,
		onCheckoutCompleteSuccess,
		onCheckoutCompleteError,
		onCheckoutProcessing,
		onSubmit,
	} = useCheckoutContext();
	const {
		setPaymentStatus,
		currentStatus,
		activePaymentMethod,
		setActivePaymentMethod,
	} = usePaymentMethodDataContext();
	const {
		shippingErrorStatus,
		shippingErrorTypes,
		shippingRates,
		shippingRatesLoading,
		selectedRates,
		setSelectedRates,
		shippingAddress,
		setShippingAddress,
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
		needsShipping,
	} = useShippingMethodDataContext();
	const { billingData, setBillingData } = useBillingData();
	const { order, isLoading: orderLoading } = useStoreOrder();
	const { cartTotals } = useStoreCart();
	const { appliedCoupons } = useStoreCartCoupons();
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

	return {
		checkoutStatus: {
			isCalculating,
			isComplete,
			isIdle,
			isProcessing,
		},
		paymentStatus: {
			currentStatus,
			setPaymentStatus,
		},
		shippingStatus: {
			shippingErrorStatus,
			shippingErrorTypes,
		},
		shippingData: {
			shippingRates,
			shippingRatesLoading,
			selectedRates,
			setSelectedRates,
			shippingAddress,
			setShippingAddress,
			needsShipping,
		},
		billing: {
			billingData,
			setBillingData,
			order,
			orderLoading,
			cartTotal: currentCartTotal.current,
			currency: getCurrencyFromPriceResponse( cartTotals ),
			// @todo need to pass along the default country set for the site
			// if it's available.
			country: '',
			cartItems: currentCartTotals.current,
			appliedCoupons,
		},
		eventRegistration: {
			onCheckoutCompleteSuccess,
			onCheckoutCompleteError,
			onCheckoutProcessing,
			onShippingRateSuccess,
			onShippingRateFail,
			onShippingRateSelectSuccess,
			onShippingRateSelectFail,
		},
		onSubmit,
		activePaymentMethod,
		setActivePaymentMethod,
	};
};
