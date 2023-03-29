/**
 * External dependencies
 */
import {
	__experimentalProductMVPCESFooter as ProductMVPCESFooter,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
} from '@woocommerce/product-editor';

export const ProductFormFooter: React.FC = () => {
	return (
		<>
			<ProductMVPCESFooter />
			<ProductMVPFeedbackModalContainer />
		</>
	);
};
