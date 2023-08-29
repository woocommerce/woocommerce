/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationsActionsMenuProps = {
	disabled?: boolean;
	onChange( variation: Partial< ProductVariation > ): void;
	onDelete(): void;
};
