/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type ProductSelectProps = {
	className?: string;
	placeholder?: string;
	selected: Product | Product[] | null;
	items: Product[];
	filter( value?: string ): Promise< Product[] >;
	onSelect?( product: Product ): void;
};
