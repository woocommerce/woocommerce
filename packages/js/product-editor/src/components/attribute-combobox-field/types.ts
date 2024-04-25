/**
 * External dependencies
 */
import type { ProductAttribute } from '@woocommerce/data';

export type AttributesComboboxControlItemProps = ProductAttribute & {
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

	current?: ProductAttribute;
	items: AttributesComboboxControlItemProps[];

	disabledAttributeMessage?: string;
	createNewAttributesAsGlobal?: boolean;

	onChange: ( value?: ProductAttribute | string ) => void;
};

export type ComboboxAttributeProps = {
	label: string;
	value: string;
	state?: 'draft' | 'creating' | 'justCreated';
	disabled?: boolean;
};
