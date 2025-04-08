/**
 * External dependencies
 */
import { PartialProductVariation, ProductVariation } from '@woocommerce/data';
import { MenuItem } from '@wordpress/components';

export type VariationActionsMenuProps = {
	disabled?: boolean;
	selection: ProductVariation[];
	onChange(
		values: PartialProductVariation[] | React.FormEvent,
		showSuccess?: boolean
	): void;
	onDelete( values: PartialProductVariation[] ): void;
};

export type VariationQuickUpdateSlotProps = {
	group: string;
	supportsMultipleSelection: boolean;
	selection: ProductVariation[];
	onChange(
		values: PartialProductVariation[] | React.FormEvent< HTMLDivElement >,
		showSuccess?: boolean
	): void;
	onClose: () => void;
};

export type MenuItemProps = Omit<
	React.ComponentProps< typeof MenuItem >,
	'onClick'
> & {
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
	onChange?: ( values: PartialProductVariation[] ) => void;
	onClose?: () => void;
};
