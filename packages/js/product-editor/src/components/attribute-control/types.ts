/**
 * External dependencies
 */
import {
	ProductAttributeTerm,
	ProductProductAttribute,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import { AttributesComboboxControlItem } from '../attribute-combobox-field/types';

export type AttributeControlEmptyStateProps = {
	addAttribute: ( search?: string ) => void;
};

export type AttributeControlProps = {
	value: EnhancedProductAttribute[];
	onAdd?: ( attribute: EnhancedProductAttribute[] ) => void;
	onAddAnother?: () => void;
	onRemoveItem?: () => void;
	onChange: ( value: ProductProductAttribute[] ) => void;
	onEdit?: ( attribute: ProductProductAttribute ) => void;
	onRemove?: ( attribute: ProductProductAttribute ) => void;
	onRemoveCancel?: ( attribute: ProductProductAttribute ) => void;
	onNewModalCancel?: () => void;
	onNewModalClose?: () => void;
	onNewModalOpen?: () => void;
	onEditModalCancel?: ( attribute?: ProductProductAttribute ) => void;
	onEditModalClose?: ( attribute?: ProductProductAttribute ) => void;
	onEditModalOpen?: ( attribute?: ProductProductAttribute ) => void;
	onNoticeDismiss?: () => void;
	renderCustomEmptyState?: ( props: AttributeControlEmptyStateProps ) => void;
	createNewAttributesAsGlobal?: boolean;
	useRemoveConfirmationModal?: boolean;
	disabledAttributeIds?: number[];
	termsAutoSelection?: 'first' | 'all';
	defaultVisibility?: boolean;
	uiStrings?: {
		notice?: string | React.ReactElement;
		emptyStateSubtitle?: string;
		newAttributeListItemLabel?: string;
		newAttributeModalTitle?: string;
		newAttributeModalDescription?: string | React.ReactElement;
		newAttributeModalNotice?: string;
		customAttributeHelperMessage?: string;
		attributeRemoveLabel?: string;
		attributeRemoveConfirmationMessage?: string;
		attributeRemoveConfirmationModalMessage?: string;
		globalAttributeHelperMessage?: string;
		disabledAttributeMessage?: string;
	};
};

export type AttributeTableRowProps = {
	index: number;
	attribute: EnhancedProductAttribute | null;
	attributePlaceholder: string;
	disabledAttributeMessage: string;

	isLoadingAttributes: boolean;
	attributes: EnhancedProductAttribute[];

	termPlaceholder: string;
	termLabel?: string;
	termsAutoSelection?: 'first' | 'all';

	clearButtonDisabled?: boolean;
	removeLabel: string;

	onAttributeSelect: (
		attribute: AttributesComboboxControlItem,
		index: number
	) => void;

	onTermSelect: ( formInput: string, value: ProductAttributeTerm[] ) => void;

	onRemove: ( index: number ) => void;
};
