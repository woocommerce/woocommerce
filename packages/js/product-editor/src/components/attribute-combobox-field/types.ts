/**
 * External dependencies
 */
import type { AttributeProductAttribute } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import type { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

export type AttributesComboboxControlItemProps = AttributeProductAttribute & {
	isDisabled?: boolean;
	takenBy?: number;
};

export type AttributesComboboxControlProps = {
	label?: string;
	help?: JSX.Element | string | null;
	isLoading: boolean;
	placeholder?: string;
	disabled?: boolean;
	instanceNumber?: number;

	current?: AttributeProductAttribute | EnhancedProductAttribute;
	items: AttributesComboboxControlItemProps[];

	disabledAttributeMessage?: string;
	createNewAttributesAsGlobal?: boolean;

	onChange: ( value?: AttributeProductAttribute | string ) => void;
};

export type ComboboxAttributeProps = {
	label: string;
	value: string;
	state?: 'draft' | 'creating' | 'justCreated';
	disabled?: boolean;
};
