/**
 * External dependencies
 */
import { PartialProductVariation, ProductVariation } from '@woocommerce/data';

export type VariationActionsMenuItemProps = {
	selection: ProductVariation[];
	onChange( values: PartialProductVariation[], showSuccess?: boolean ): void;
	onClose(): void;
	supportsMultipleSelection?: boolean;
};
