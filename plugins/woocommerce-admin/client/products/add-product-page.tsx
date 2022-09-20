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
import { PricingSection } from './sections/pricing-section';
import { ProductShippingSection } from './sections/product-shipping-section';
import './product-page.scss';
import { validate } from './product-validation';
import { AttributesSection } from './sections/attributes-section';

const AddProductPage: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-add-product">
			<Form< Partial< Product > >
				initialValues={ {} }
				errors={ {} }
				validate={ validate }
			>
				<ProductFormLayout>
					<ProductDetailsSection />
					<PricingSection />
					<ProductShippingSection />
					<AttributesSection />
					<ProductFormActions />
				</ProductFormLayout>
			</Form>
		</div>
	);
};

export default AddProductPage;
