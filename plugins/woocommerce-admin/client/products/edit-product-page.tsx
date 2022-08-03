/**
 * Internal dependencies
 */
import { ProductDetailsCategory } from './categories/product-details-category';
import { ProductImagesCategory } from './categories/product-images-category';
import { ProductFormLayout } from './layout/product-form-layout';

const EditProductPage: React.FC = () => {
	return (
		<ProductFormLayout>
			<ProductDetailsCategory />
			<ProductImagesCategory />
		</ProductFormLayout>
	);
};

export default EditProductPage;
