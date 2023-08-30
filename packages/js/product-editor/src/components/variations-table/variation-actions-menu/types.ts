/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationActionsMenuProps = {
	selection: ProductVariation;
	onChange( variation: Partial< ProductVariation > ): void;
	onDelete( variation: ProductVariation ): void;
};
