/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductDetailsSection } from './sections/product-details-section';
import { ProductImagesSection } from './sections/product-images-section';

const AddProductPage: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );
	return (
		<div className="woocommerce-add-product">
			<ProductFormLayout>
				<ProductDetailsSection />
				<ProductImagesSection />
			</ProductFormLayout>
		</div>
	);
};

export default AddProductPage;
