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
};

export type VariationQuickUpdateSlotProps = {
	group: string;
	supportsMultipleSelection: boolean;
	selection: ProductVariation | ProductVariation[];
	onChange: (
		variation: Partial< ProductVariation > | Partial< ProductVariation >[]
	) => void;
	onClose: () => void;
};

export type MenuItemProps = {
	children?: React.ReactNode;
	order?: number;
	group?: string;
	supportsMultipleSelection?: boolean;
	onClick?: ( {
		onChange,
		onClose,
		selection,
	}: {
		[ K in
			| 'onChange'
			| 'onClose'
			| 'selection' ]: VariationQuickUpdateSlotProps[ K ];
	} ) => void;
	onChange?: (
		variation: Partial< ProductVariation > | Partial< ProductVariation >[]
	) => void;
	onClose?: () => void;
};
