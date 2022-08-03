/**
 * Internal dependencies
 */
import { ProductDetailsSection } from './sections/product-details-section';
import { ProductImagesSection } from './sections/product-images-section';
import { ProductFormLayout } from './layout/product-form-layout';

const EditProductPage: React.FC = () => {
	return (
		<ProductFormLayout>
			<ProductDetailsSection />
			<ProductImagesSection />
		</ProductFormLayout>
	);
};

export default EditProductPage;
