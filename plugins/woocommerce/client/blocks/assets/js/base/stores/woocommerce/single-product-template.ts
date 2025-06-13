/**
 * External dependencies
 */
import type { ProductData } from '@woocommerce/type-defs/product';

export type SingleProductTemplateStore = {
	state: {
		singleProductTemplate: {
			originalProductData: ProductData;
			productData: ProductData;
		};
	};
};
