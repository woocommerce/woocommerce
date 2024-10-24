/**
 * External dependencies
 */
import {
	CanMakePaymentArgument,
	ExpressPaymentMethodConfigInstance,
	PaymentMethodConfigInstance,
	PlainExpressPaymentMethods,
	PlainPaymentMethods,
} from '@woocommerce/types';
import { CURRENT_USER_IS_ADMIN, getSetting } from '@woocommerce/settings';
import { dispatch, select } from '@wordpress/data';
import {
	deriveSelectedShippingRates,
	emptyHiddenAddressFields,
} from '@woocommerce/base-utils';
import { __, sprintf } from '@wordpress/i18n';
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

/**
 * Get the argument that will be passed to a payment method's `canMakePayment` method.
 */
export const getCanMakePaymentArg = (): CanMakePaymentArgument => {
	const isEditor = !! select( 'core/editor' );
	let canPayArgument: CanMakePaymentArgument;

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

		const cartForCanPayArgument = {
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
			paymentMethods: cart.paymentMethods,
			paymentRequirements: cart.paymentRequirements,
		};
	} else {
		const cartForCanPayArgument = {
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
			cartTotals: cartForCanPayArgument.cartTotals,
			cartNeedsShipping: cartForCanPayArgument.cartNeedsShipping,
			billingData: cartForCanPayArgument.billingAddress,
			billingAddress: cartForCanPayArgument.billingAddress,
			shippingAddress: cartForCanPayArgument.shippingAddress,
			selectedShippingMethods: deriveSelectedShippingRates(
				cartForCanPayArgument.shippingRates
			),
			paymentMethods: previewCart.payment_methods,
			paymentRequirements: cartForCanPayArgument.paymentRequirements,
		};
	}

	return canPayArgument;
};

const registrationErrorNotice = (
	paymentMethod:
		| ExpressPaymentMethodConfigInstance
		| PaymentMethodConfigInstance,
	errorMessage: string,
	express = false
) => {
	const { createErrorNotice } = dispatch( 'core/notices' );
	const noticeContext = express
		? noticeContexts.EXPRESS_PAYMENTS
		: noticeContexts.PAYMENTS;
	const errorText = sprintf(
		/* translators: %s the id of the payment method being registered (bank transfer, cheque...) */
		__(
			`There was an error registering the payment method with id '%s': `,
			'woocommerce'
		),
		paymentMethod.paymentMethodId
	);
	createErrorNotice( `${ errorText } ${ errorMessage }`, {
		context: noticeContext,
		id: `wc-${ paymentMethod.paymentMethodId }-registration-error`,
	} );
};

const compareAvailablePaymentMethods = (
	paymentMethods: PlainPaymentMethods | PlainExpressPaymentMethods,
	availablePaymentMethods: PlainPaymentMethods | PlainExpressPaymentMethods
) => {
	const compareKeys1 = Object.keys( paymentMethods );
	const compareKeys2 = Object.keys( availablePaymentMethods );

	return (
		compareKeys1.length === compareKeys2.length &&
		compareKeys1.every( ( current ) => compareKeys2.includes( current ) )
	);
};

export const checkPaymentMethodsCanPay = async ( express = false ) => {
	const availablePaymentMethods:
		| PlainPaymentMethods
		| PlainExpressPaymentMethods = {};

	const paymentMethods = express
		? getExpressPaymentMethods()
		: getPaymentMethods();

	const addAvailablePaymentMethod = (
		paymentMethod:
			| PaymentMethodConfigInstance
			| ExpressPaymentMethodConfigInstance
	) => {
		if ( express ) {
			const { name, title, description, gatewayId, supports } =
				paymentMethod as ExpressPaymentMethodConfigInstance;

			availablePaymentMethods[ name ] = {
				name,
				title,
				description,
				gatewayId,
				supportsStyle: supports?.style || [],
			};
		} else {
			const { name } = paymentMethod as PaymentMethodConfigInstance;

			availablePaymentMethods[ name ] = {
				name,
			};
		}
	};

	// Order payment methods.
	const sortedPaymentMethods = express
		? Object.keys( paymentMethods )
		: Array.from(
				new Set( [
					...( getSetting( 'paymentMethodSortOrder', [] ) as [] ),
					...Object.keys( paymentMethods ),
				] )
		  );
	const canPayArgument = getCanMakePaymentArg();
	const cartPaymentMethods = canPayArgument.paymentMethods as string[];
	const isEditor = !! select( 'core/editor' );

	for ( let i = 0; i < sortedPaymentMethods.length; i++ ) {
		const paymentMethodName = sortedPaymentMethods[ i ];
		const paymentMethod = paymentMethods[ paymentMethodName ] || {};

		if ( ! paymentMethod ) {
			continue;
		}

		// See if payment method should be available. This always evaluates to true in the editor context.
		try {
			const validForCart =
				isEditor || express
					? true
					: cartPaymentMethods.includes( paymentMethodName );
			const canPay = isEditor
				? true
				: validForCart &&
				  ( await Promise.resolve(
						paymentMethod.canMakePayment( canPayArgument )
				  ) );

			if ( canPay ) {
				if ( typeof canPay === 'object' && canPay?.error ) {
					throw new Error( canPay.error.message );
				}
				addAvailablePaymentMethod( paymentMethod );
			}
		} catch ( e ) {
			if ( CURRENT_USER_IS_ADMIN || isEditor ) {
				registrationErrorNotice( paymentMethod, e as string, express );
			}
		}
	}

	const currentPaymentMethods = express
		? select( PAYMENT_STORE_KEY ).getAvailableExpressPaymentMethods()
		: select( PAYMENT_STORE_KEY ).getAvailablePaymentMethods();

	if (
		! compareAvailablePaymentMethods(
			availablePaymentMethods,
			currentPaymentMethods
		)
	) {
		const {
			__internalSetAvailablePaymentMethods,
			__internalSetAvailableExpressPaymentMethods,
		} = dispatch( PAYMENT_STORE_KEY );

		if ( express ) {
			__internalSetAvailableExpressPaymentMethods(
				availablePaymentMethods as PlainExpressPaymentMethods
			);
		} else {
			__internalSetAvailablePaymentMethods(
				availablePaymentMethods as PlainPaymentMethods
			);
		}
	}

	return true;
};
