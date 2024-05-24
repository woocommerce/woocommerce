/**
 * Internal dependencies
 */
import { ProductMoreMenu } from '../product-more-menu';
import { ProductSettings } from '../product-settings';

export const ProductFormHeader: React.FC = () => {
	return (
		<>
			<ProductSettings />
			<ProductMoreMenu />
		</>
	);
};
