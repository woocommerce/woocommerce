/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationActionsMenuProps = {
	disabled?: boolean;
	selection: ProductVariation | ProductVariation[];
	onChange( variation: Partial< ProductVariation > ): void;
	onDelete(
		variation: ProductVariation | Partial< ProductVariation >[]
	): void;
	type?: string;
};

export type SingleVariationMenuItemProps = {
	children?: React.ReactNode;
	order?: number;
	group?: string;
	type?: string;
};
