/**
 * External dependencies
 */
import { __experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { ProductMVPCESFooter } from '~/customer-effort-score-tracks/product-mvp-ces-footer';

export const ProductFormFooter: React.FC = () => {
	return (
		<>
			<ProductMVPCESFooter />
			<ProductMVPFeedbackModalContainer />
		</>
	);
};
