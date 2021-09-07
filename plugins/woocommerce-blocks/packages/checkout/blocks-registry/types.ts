/**
 * External dependencies
 */
import type { LazyExoticComponent } from 'react';
import type { BlockConfiguration } from '@wordpress/blocks';

export enum innerBlockAreas {
	CHECKOUT_FIELDS = 'woocommerce/checkout-fields-block',
	CHECKOUT_TOTALS = 'woocommerce/checkout-totals-block',
	CONTACT_INFORMATION = 'woocommerce/checkout-contact-information-block',
	SHIPPING_ADDRESS = 'woocommerce/checkout-shipping-address-block',
	BILLING_ADDRESS = 'woocommerce/checkout-billing-address-block',
	SHIPPING_METHODS = 'woocommerce/checkout-shipping-methods-block',
	PAYMENT_METHODS = 'woocommerce/checkout-payment-methods-block',
}

interface CheckoutBlockOptionsMetadata extends Partial< BlockConfiguration > {
	name: string;
	parent: string[];
}

export type RegisteredBlock = {
	blockName: string;
	metadata: CheckoutBlockOptionsMetadata;
	component:
		| LazyExoticComponent< React.ComponentType< unknown > >
		| ( () => JSX.Element | null )
		| null;
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
