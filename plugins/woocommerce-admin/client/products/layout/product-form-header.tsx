/**
 * Internal dependencies
 */
import { ProductFormActions } from '../product-form-actions';
import { ProductMoreMenu } from '../product-more-menu';
import { ProductTitle } from '../product-title';

export const ProductFormHeader: React.FC = () => {
	return (
		<>
			<ProductTitle />
			<ProductFormActions />
			<ProductMoreMenu />
		</>
	);
};
