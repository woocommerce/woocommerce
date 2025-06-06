/**
 * External dependencies
 */
import type { ProductData } from '@woocommerce/type-defs/product';

export type SingleProductTemplateStore = {
	state: {
		singleProductTemplate: {
			productData: ProductData;
		};
	};
};
