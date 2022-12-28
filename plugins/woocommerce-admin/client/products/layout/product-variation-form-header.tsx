/**
 * Internal dependencies
 */
import { ProductVariationFormActions } from '../product-variation-form-actions';
import { ProductTitle } from '../product-title';

export const ProductVariationFormHeader: React.FC = () => {
	return (
		<>
			<ProductTitle />
			<ProductVariationFormActions />
		</>
	);
};
