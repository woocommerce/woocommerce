/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type ProductSelectProps = {
	className?: string;
	placeholder?: string;
	label?: string;
	help?: JSX.Element | string | null;
	selected: Product | Product[] | null;
	items: Product[];
	instanceNumber?: number;
	isLoading?: boolean;
	onAddNew?: ( value: string ) => void;
	filter( value?: string ): Promise< Product[] > | void;
	onSelect?( product: Product ): void;
};

export type ComboboxControlProductSelectOption = {
	label: string;
	value: string;
	product: Product;
};
