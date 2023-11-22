/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type AddProductsModalProps = {
	onSubmit( value: Product[] ): void;
	onClose(): void;
};
