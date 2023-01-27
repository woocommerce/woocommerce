/**
 * Internal dependencies
 */
import { ProductMVPCESFooter } from '~/customer-effort-score-tracks/product-mvp-ces-footer';
import { ProductMVPFeedbackModalContainer } from '~/customer-effort-score-tracks/product-mvp-feedback-modal-container';

export const ProductFormFooter: React.FC = () => {
	return (
		<>
			<ProductMVPCESFooter />
			<ProductMVPFeedbackModalContainer />
		</>
	);
};
