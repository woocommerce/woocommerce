/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationStockStatusFormProps = {
	initialValue?: Partial< ProductVariation >;
	onSubmit?( value: Pick< ProductVariation, 'manage_stock' > ): void;
	onCancel?(): void;
};
