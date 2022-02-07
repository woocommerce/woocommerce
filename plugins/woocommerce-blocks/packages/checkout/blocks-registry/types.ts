/**
 * External dependencies
 */
import type { LazyExoticComponent } from 'react';
import type { BlockConfiguration } from '@wordpress/blocks';
import type { RegisteredBlockComponent } from '@woocommerce/types';

export enum innerBlockAreas {
	CHECKOUT = 'woocommerce/checkout',
	CHECKOUT_FIELDS = 'woocommerce/checkout-fields-block',
	CHECKOUT_TOTALS = 'woocommerce/checkout-totals-block',
	CONTACT_INFORMATION = 'woocommerce/checkout-contact-information-block',
	SHIPPING_ADDRESS = 'woocommerce/checkout-shipping-address-block',
	BILLING_ADDRESS = 'woocommerce/checkout-billing-address-block',
	SHIPPING_METHODS = 'woocommerce/checkout-shipping-methods-block',
	PAYMENT_METHODS = 'woocommerce/checkout-payment-methods-block',
	CART = 'woocommerce/cart',
	EMPTY_CART = 'woocommerce/empty-cart-block',
	FILLED_CART = 'woocommerce/filled-cart-block',
	CART_ITEMS = 'woocommerce/cart-items-block',
	CART_TOTALS = 'woocommerce/cart-totals-block',
	MINI_CART = 'woocommerce/mini-cart-contents',
	EMPTY_MINI_CART = 'woocommerce/empty-mini-cart-contents-block',
	FILLED_MINI_CART = 'woocommerce/filled-mini-cart-contents-block',
	MINI_CART_ITEMS = 'woocommerce/mini-cart-items-block',
}

interface CheckoutBlockOptionsMetadata extends Partial< BlockConfiguration > {
	name: string;
	parent: string[];
}

export type RegisteredBlock = {
	blockName: string;
	metadata: CheckoutBlockOptionsMetadata;
	component: RegisteredBlockComponent;
	force: boolean;
};

export type RegisteredBlocks = Record< string, RegisteredBlock >;

export type CheckoutBlockOptions = {
	metadata: CheckoutBlockOptionsMetadata;
	component:
		| LazyExoticComponent< React.ComponentType< unknown > >
		| ( () => JSX.Element | null )
		| null;
};
