/**
 * External dependencies
 */
import { Form, FormRef } from '@woocommerce/components';
import { PartialProduct, Product } from '@woocommerce/data';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { ProductFormHeader } from './layout/product-form-header';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductDetailsSection } from './sections/product-details-section';
import { ProductInventorySection } from './sections/product-inventory-section';
import { PricingSection } from './sections/pricing-section';
import { ProductShippingSection } from './sections/product-shipping-section';
import { ImagesSection } from './sections/images-section';
import './product-page.scss';
import { validate } from './product-validation';
import { AttributesSection } from './sections/attributes-section';
import { ProductFormFooter } from './layout/product-form-footer';

export const ProductForm: React.FC< {
	product?: PartialProduct;
	formRef?: Ref< FormRef< Partial< Product > > >;
} > = ( { product, formRef } ) => {
	return (
		<Form< Partial< Product > >
			initialValues={
				product || {
					reviews_allowed: true,
					name: '',
					sku: '',
					stock_quantity: 0,
					stock_status: 'instock',
				}
			}
			ref={ formRef }
			errors={ {} }
			validate={ validate }
		>
			<ProductFormHeader />
			<ProductFormLayout>
				<ProductDetailsSection />
				<PricingSection />
				<ImagesSection />
				<ProductInventorySection />
				<ProductShippingSection product={ product } />
				<AttributesSection />
			</ProductFormLayout>
			<ProductFormFooter />
		</Form>
	);
};
