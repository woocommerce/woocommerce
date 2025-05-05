/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { EventObserversType, ObserverType } from './types';

export const getObserversByPriority = (
	observers: EventObserversType,
	eventType: string
): ObserverType[] => {
	return observers[ eventType ]
		? Array.from( observers[ eventType ].values() ).sort( ( a, b ) => {
				return a.priority - b.priority;
		  } )
		: [];
};

export enum noticeContexts {
	CART = 'wc/cart',
	CHECKOUT = 'wc/checkout',
	PAYMENTS = 'wc/checkout/payments',
	EXPRESS_PAYMENTS = 'wc/checkout/express-payments',
	CONTACT_INFORMATION = 'wc/checkout/contact-information',
	SHIPPING_ADDRESS = 'wc/checkout/shipping-address',
	BILLING_ADDRESS = 'wc/checkout/billing-address',
	SHIPPING_METHODS = 'wc/checkout/shipping-methods',
	CHECKOUT_ACTIONS = 'wc/checkout/checkout-actions',
	ORDER_INFORMATION = 'wc/checkout/order-information',
}

export const shouldRetry = ( response: unknown ): boolean => {
	return (
		! isObject( response ) ||
		typeof response.retry === 'undefined' ||
		response.retry === true
	);
};
