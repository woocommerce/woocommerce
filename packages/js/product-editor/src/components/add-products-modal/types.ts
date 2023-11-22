/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type AddProductsModalProps = {
	initialValue: Product[];
	onSubmit( value: Product[] ): void;
	onClose(): void;
};
