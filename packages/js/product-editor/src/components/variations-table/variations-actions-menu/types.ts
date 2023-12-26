/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationsActionsMenuProps = {
	disabled?: boolean;
	selection: ProductVariation[];
	onChange( variations: Partial< ProductVariation >[] ): void;
	onDelete( variations: Partial< ProductVariation >[] ): void;
};
