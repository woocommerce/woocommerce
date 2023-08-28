/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type ShippingMenuItemProps = {
	variation: ProductVariation;
	handlePrompt(
		propertyName: keyof ProductVariation,
		label?: string,
		parser?: ( value: string ) => unknown
	): void;
	onClose(): void;
};
