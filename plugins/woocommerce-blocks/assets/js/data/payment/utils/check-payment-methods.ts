/**
 * External dependencies
 */
import {
	ExpressPaymentMethodConfigInstance,
	PaymentMethodConfigInstance,
} from '@woocommerce/type-defs/payments';
import { CURRENT_USER_IS_ADMIN, getSetting } from '@woocommerce/settings';
import { dispatch, select } from '@wordpress/data';
import {
	deriveSelectedShippingRates,
	emptyHiddenAddressFields,
} from '@woocommerce/base-utils';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

import {
	getExpressPaymentMethods,
	getPaymentMethods,
} from '@woocommerce/blocks-registry';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { STORE_KEY as CART_STORE_KEY } from '../../cart/constants';
import { STORE_KEY as PAYMENT_STORE_KEY } from '../constants';
import { noticeContexts } from '../../../base/context/event-emit';
import {
	EMPTY_CART_ERRORS,
	EMPTY_CART_ITEM_ERRORS,
	EMPTY_EXTENSIONS,
} from '../../../data/constants';
import { defaultCartState } from '../../../data/cart/default-state';

export const checkPaymentMethodsCanPay = async ( express = false ) => {
	const isEditor = !! select( 'core/editor' );

	let availablePaymentMethods = {};
	const paymentMethods = express
		? getExpressPaymentMethods()
		: getPaymentMethods();

	const addAvailablePaymentMethod = (
		paymentMethod:
			| PaymentMethodConfigInstance
			| ExpressPaymentMethodConfigInstance
	) => {
		const { name } = paymentMethod;
		availablePaymentMethods = {
			...availablePaymentMethods,
			[ paymentMethod.name ]: { name },
		};
	};

	const noticeContext = express
		? noticeContexts.EXPRESS_PAYMENTS
		: noticeContexts.PAYMENTS;

	let cartForCanPayArgument: Record< string, unknown > = {};
	let canPayArgument: Record< string, unknown > = {};

	if ( ! isEditor ) {
		const store = select( CART_STORE_KEY );
		const cart = store.getCartData();
		const cartErrors = store.getCartErrors();
		const cartTotals = store.getCartTotals();
		const cartIsLoading = ! store.hasFinishedResolution( 'getCartData' );
		const isLoadingRates = store.isCustomerDataUpdating();
		const selectedShippingMethods = deriveSelectedShippingRates(
			cart.shippingRates
		);

		cartForCanPayArgument = {
			cartCoupons: cart.coupons,
			cartItems: cart.items,
			crossSellsProducts: cart.crossSells,
			cartFees: cart.fees,
			cartItemsCount: cart.itemsCount,
			cartItemsWeight: cart.itemsWeight,
			cartNeedsPayment: cart.needsPayment,
			cartNeedsShipping: cart.needsShipping,
			cartItemErrors: cart.errors,
			cartTotals,
			cartIsLoading,
			cartErrors,
			billingData: emptyHiddenAddressFields( cart.billingAddress ),
			billingAddress: emptyHiddenAddressFields( cart.billingAddress ),
			shippingAddress: emptyHiddenAddressFields( cart.shippingAddress ),
			extensions: cart.extensions,
			shippingRates: cart.shippingRates,
			isLoadingRates,
			cartHasCalculatedShipping: cart.hasCalculatedShipping,
			paymentRequirements: cart.paymentRequirements,
			receiveCart: dispatch( CART_STORE_KEY ).receiveCart,
		};

		canPayArgument = {
			cart: cartForCanPayArgument,
			cartTotals: cart.totals,
			cartNeedsShipping: cart.needsShipping,
			billingData: cart.billingAddress,
			billingAddress: cart.billingAddress,
			shippingAddress: cart.shippingAddress,
			selectedShippingMethods,
			paymentRequirements: cart.paymentRequirements,
		};
	} else {
		cartForCanPayArgument = {
			cartCoupons: previewCart.coupons,
			cartItems: previewCart.items,
			crossSellsProducts: previewCart.cross_sells,
			cartFees: previewCart.fees,
			cartItemsCount: previewCart.items_count,
			cartItemsWeight: previewCart.items_weight,
			cartNeedsPayment: previewCart.needs_payment,
			cartNeedsShipping: previewCart.needs_shipping,
			cartItemErrors: EMPTY_CART_ITEM_ERRORS,
			cartTotals: previewCart.totals,
			cartIsLoading: false,
			cartErrors: EMPTY_CART_ERRORS,
			billingData: defaultCartState.cartData.billingAddress,
			billingAddress: defaultCartState.cartData.billingAddress,
			shippingAddress: defaultCartState.cartData.shippingAddress,
			extensions: EMPTY_EXTENSIONS,
			shippingRates: previewCart.shipping_rates,
			isLoadingRates: false,
			cartHasCalculatedShipping: previewCart.has_calculated_shipping,
			paymentRequirements: previewCart.payment_requirements,
			receiveCart: () => undefined,
		};
		canPayArgument = {
			cart: cartForCanPayArgument,
			cartTotals: cartForCanPayArgument.totals,
			cartNeedsShipping: cartForCanPayArgument.needsShipping,
			billingData: cartForCanPayArgument.billingAddress,
			billingAddress: cartForCanPayArgument.billingAddress,
			shippingAddress: cartForCanPayArgument.shippingAddress,
			selectedShippingMethods: deriveSelectedShippingRates(
				cartForCanPayArgument.shippingRates
			),
			paymentRequirements: cartForCanPayArgument.paymentRequirements,
		};
	}

	// Order payment methods
	let paymentMethodsOrder;
	if ( express ) {
		paymentMethodsOrder = Object.keys( paymentMethods );
	} else {
		paymentMethodsOrder = Array.from(
			new Set( [
				...( getSetting( 'paymentGatewaySortOrder', [] ) as [] ),
				...Object.keys( paymentMethods ),
			] )
		);
	}

	for ( let i = 0; i < paymentMethodsOrder.length; i++ ) {
		const paymentMethodName = paymentMethodsOrder[ i ];
		const paymentMethod = paymentMethods[ paymentMethodName ];
		if ( ! paymentMethod ) {
			continue;
		}

		// See if payment method should be available. This always evaluates to true in the editor context.
		try {
			const canPay = isEditor
				? true
				: await Promise.resolve(
						paymentMethod.canMakePayment( canPayArgument )
				  );

			if ( canPay ) {
				if ( typeof canPay === 'object' && canPay.error ) {
					throw new Error( canPay.error.message );
				}

				addAvailablePaymentMethod( paymentMethod );
			}
		} catch ( e ) {
			if ( CURRENT_USER_IS_ADMIN || isEditor ) {
				const { createErrorNotice } = dispatch( noticesStore );
				const errorText = sprintf(
					/* translators: %s the id of the payment method being registered (bank transfer, cheque...) */
					__(
						`There was an error registering the payment method with id '%s': `,
						'woo-gutenberg-products-block'
					),
					paymentMethod.paymentMethodId
				);
				createErrorNotice( `${ errorText } ${ e }`, {
					context: noticeContext,
					id: `wc-${ paymentMethod.paymentMethodId }-registration-error`,
				} );
			}
		}
	}
	const currentlyAvailablePaymentMethods = express
		? select( PAYMENT_STORE_KEY ).getAvailableExpressPaymentMethods()
		: select( PAYMENT_STORE_KEY ).getAvailablePaymentMethods();

	const availablePaymentMethodNames = Object.keys( availablePaymentMethods );
	if (
		Object.keys( currentlyAvailablePaymentMethods ).length ===
			availablePaymentMethodNames.length &&
		Object.keys( currentlyAvailablePaymentMethods ).every( ( current ) =>
			availablePaymentMethodNames.includes( current )
		)
	) {
		// All the names are the same, no need to dispatch more actions.
		return true;
	}

	const {
		__internalSetAvailablePaymentMethods,
		__internalSetAvailableExpressPaymentMethods,
	} = dispatch( PAYMENT_STORE_KEY );
	if ( express ) {
		__internalSetAvailableExpressPaymentMethods( availablePaymentMethods );
		return true;
	}
	__internalSetAvailablePaymentMethods( availablePaymentMethods );
	return true;
};
