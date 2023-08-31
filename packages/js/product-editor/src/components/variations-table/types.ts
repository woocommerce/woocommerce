/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationActionsMenuItemProps = {
	selection: ProductVariation | ProductVariation[];
	onChange(
		variation: Partial< ProductVariation > | Partial< ProductVariation >[]
	): void;
	onClose(): void;
};
