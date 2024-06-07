/**
 * External dependencies
 */
import type { Product } from '@woocommerce/data';

export type AddProductsModalProps = {
	initialValue: Product[];
	onSubmit( value: Product[] ): void;
	onClose(): void;
};

export type ReorderProductsModalProps = {
	products: Product[];
	onSubmit( value: Product[] ): void;
	onClose(): void;
};
