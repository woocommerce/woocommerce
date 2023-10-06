/**
 * Internal dependencies
 */
import { ProductMoreMenu } from '../product-more-menu';
import { ProductSettings } from '../product-settings';
import { ProductTitle } from '../product-title';

export const ProductFormHeader: React.FC = () => {
	return (
		<>
			<ProductTitle />
			<ProductSettings />
			<ProductMoreMenu />
		</>
	);
};
