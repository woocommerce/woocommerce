/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type AddProductsModalProps = {
	products: Product[];
	onSubmit( value: Product[] ): void;
	onClose(): void;
};
