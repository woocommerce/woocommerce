/**
 * External dependencies
 */
import type { ProductAttribute } from '@woocommerce/data';

export type AttributesComboboxControlItem = ProductAttribute & {
	isDisabled?: boolean;
	takenBy?: number;
};

export type AttributesComboboxControlComponent = {
	label?: string;
	help?: JSX.Element | string | null;
	isLoading: boolean;
	placeholder?: string;
	disabled?: boolean;
	instanceNumber?: number;

	current?: ProductAttribute;
	items: AttributesComboboxControlItem[];

	disabledAttributeMessage?: string;
	createNewAttributesAsGlobal?: boolean;

	onChange: ( value?: ProductAttribute | string ) => void;
};

export type ComboboxControlOption = {
	label: string;
	value: string;
	state?: 'draft' | 'creating' | 'justCreated';
	disabled?: boolean;
};
