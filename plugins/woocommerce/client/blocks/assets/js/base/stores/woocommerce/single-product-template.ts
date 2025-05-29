/**
 * External dependencies
 */
import type { DisplayedProduct } from '@woocommerce/type-defs/product';

export type SingleProductTemplateStore = {
	state: {
		displayedProduct: DisplayedProduct;
	};
};
