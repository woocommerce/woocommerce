/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

export type AttributeControlEmptyStateProps = {
	addAttribute: ( search?: string ) => void;
};

export type AttributeControlProps = {
	value: EnhancedProductAttribute[];
	onAdd?: ( attribute: EnhancedProductAttribute[] ) => void;
	onAddAnother?: () => void;
	onRemoveItem?: () => void;
	onChange: ( value: ProductAttribute[] ) => void;
	onEdit?: ( attribute: ProductAttribute ) => void;
	onRemove?: ( attribute: ProductAttribute ) => void;
	onRemoveCancel?: ( attribute: ProductAttribute ) => void;
	onNewModalCancel?: () => void;
	onNewModalClose?: () => void;
	onNewModalOpen?: () => void;
	onEditModalCancel?: ( attribute?: ProductAttribute ) => void;
	onEditModalClose?: ( attribute?: ProductAttribute ) => void;
	onEditModalOpen?: ( attribute?: ProductAttribute ) => void;
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
