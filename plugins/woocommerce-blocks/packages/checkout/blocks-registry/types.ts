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

export type RegisteredBlockComponent =
	| LazyExoticComponent< React.ComponentType< unknown > >
	| ( () => JSX.Element | null );

export type RegisteredBlock = {
	block: string;
	component: RegisteredBlockComponent | null;
	force: boolean;
};

export type RegisteredBlocks = Record<
	innerBlockAreas,
	Array< RegisteredBlock >
>;

export type CheckoutBlockOptions = {
	// This is a component to render on the frontend in place of this block, when used.
	component: RegisteredBlockComponent;
	// Area(s) to add the block to. This can be a single area (string) or an array of areas.
	areas: Array< innerBlockAreas >;
	// Should this block be forced? If true, it cannot be removed from the editor interface, and will be rendered in defined areas automatically.
	force?: boolean;
	// Standard block configuration object. If not passed, the block will not be registered with WordPress and must be done manually.
	configuration?: Partial< BlockConfiguration >;
};
