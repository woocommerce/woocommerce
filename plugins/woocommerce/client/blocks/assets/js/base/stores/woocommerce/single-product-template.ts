/**
 * External dependencies
 */
import type {
	OriginalProductData,
	ProductData,
} from '@woocommerce/type-defs/product';

export type SingleProductTemplateStore = {
	state: {
		singleProductTemplate: {
			originalProductData: OriginalProductData;
			productData: ProductData;
		};
	};
};
