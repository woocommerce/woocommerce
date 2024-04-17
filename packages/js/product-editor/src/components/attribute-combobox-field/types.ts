/**
 * External dependencies
 */
import type {
	QueryProductAttribute,
	ProductAttribute,
} from '@woocommerce/data';
/**
 * Internal dependencies
 */
import type { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

export type NarrowedQueryAttribute = Pick<
	QueryProductAttribute,
	'id' | 'name'
> & {
	slug?: string;
	isDisabled?: boolean;
};

export type AttributeComboboxProps = {
	label?: string;
	currentItem: EnhancedProductAttribute | null;
	items: NarrowedQueryAttribute[];
	isLoading: boolean;
	placeholder?: string;
	disabled?: boolean;
	disabledAttributeIds?: number[];
	disabledAttributeMessage?: string;
	ignoredAttributeIds?: number[];
	createNewAttributesAsGlobal?: boolean;

	onChange: (
		value?:
			| Omit< ProductAttribute, 'position' | 'visible' | 'variation' >
			| string
	) => void;
};

export type ComboboxAttributeProps = {
	label: string;
	value: string;
};
