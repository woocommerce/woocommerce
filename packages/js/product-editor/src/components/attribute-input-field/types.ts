/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

/*
 * AttributeInputField item props.
 */
export type AttributeInputFieldItemProps = Pick<
	ProductAttribute,
	'id' | 'name'
> & {
	slug?: string;
	isDisabled?: boolean;
};

export type AttributeInputFieldProps = {
	value?: EnhancedProductAttribute | null;
	onChange: ( value?: AttributeInputFieldItemProps | string ) => void;
	label?: string;
	placeholder?: string;
	disabled?: boolean;
	disabledAttributeIds?: number[];
	disabledAttributeMessage?: string;
	ignoredAttributeIds?: number[];
	createNewAttributesAsGlobal?: boolean;
};

export type MenuAttributeListProps = {
	renderItems: AttributeInputFieldItemProps[];
	highlightedIndex: number;
	disabledAttributeMessage?: string;
	getItemProps: (
		options: UseComboboxGetMenuPropsOptions
	) => getItemPropsType< AttributeInputFieldItemProps >;
};

export interface GetPropsWithRefKey {
	refKey?: string;
}
export interface GetMenuPropsOptions
	extends React.HTMLProps< HTMLElement >,
		GetPropsWithRefKey {
	[ 'aria-label' ]?: string;
}

export interface UseComboboxGetMenuPropsOptions
	extends GetPropsWithRefKey,
		GetMenuPropsOptions {}

export interface GetPropsCommonOptions {
	suppressRefError?: boolean;
}

export interface GetItemPropsOptions< Item >
	extends React.HTMLProps< HTMLElement > {
	index?: number;
	item: Item;
	isSelected?: boolean;
	disabled?: boolean;
}

export interface UseComboboxGetItemPropsOptions< Item >
	extends GetItemPropsOptions< Item >,
		GetPropsWithRefKey {}

export type getMenuPropsType = (
	options?: UseComboboxGetMenuPropsOptions,
	otherOptions?: GetPropsCommonOptions
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
) => any;

export type getItemPropsType< ItemType > = (
	options: UseComboboxGetItemPropsOptions< ItemType >
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
) => any;
