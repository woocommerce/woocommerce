/**
 * External dependencies
 */
import type { ProductVariation } from '@woocommerce/data';

export type VariationPricingFormProps = {
	initialValue?: Partial< ProductVariation >;
	onSubmit?(
		value: Pick< ProductVariation, 'regular_price' | 'sale_price' >
	): void;
	onCancel?(): void;
};
