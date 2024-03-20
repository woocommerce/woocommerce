/**
 * External dependencies
 */
import { PartialProduct } from '@woocommerce/data';
import {
	__experimentalProductMVPCESFooter as ProductMVPCESFooter,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
} from '@woocommerce/product-editor';

export const ProductFormFooter: React.FC< {
	product?: PartialProduct;
} > = ( { product } ) => {
	return (
		<>
			{ product && <ProductMVPCESFooter productType={ product.type } /> }
			<ProductMVPFeedbackModalContainer />
		</>
	);
};
