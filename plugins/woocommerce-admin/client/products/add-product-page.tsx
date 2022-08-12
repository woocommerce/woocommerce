/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductFormActions } from './product-form-actions';
import { ProductDetailsSection } from './sections/product-details-section';
import './product-page.scss';

const AddProductPage: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-add-product">
			<Form< Partial< Product > > initialValues={ {} } errors={ {} }>
				<ProductFormLayout>
					<ProductDetailsSection />

					<ProductFormActions />
				</ProductFormLayout>
			</Form>
		</div>
	);
};

export default AddProductPage;
