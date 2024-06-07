/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type ShippingMenuItemProps = {
	variation: ProductVariation;
	handlePrompt(
		label?: string,
		parser?: ( value: string ) => Partial< ProductVariation > | null
	): void;
	onClose(): void;
};
