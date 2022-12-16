/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { PartialProduct, ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductFormHeader } from './layout/product-form-header';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductFormFooter } from './layout/product-form-footer';
import { ProductFormTab } from './product-form-tab';
import { PricingSection } from './sections/pricing-section';
import { ProductInventorySection } from './sections/product-inventory-section';
import { ProductShippingSection } from './sections/product-shipping-section';

export const ProductVariationForm: React.FC< {
	product: PartialProduct;
	productVariation: Partial< ProductVariation >;
} > = ( { productVariation } ) => {
	return (
		<Form< Partial< ProductVariation > >
			initialValues={ productVariation }
			errors={ {} }
		>
			<ProductFormHeader />
			<ProductFormLayout>
				<ProductFormTab name="general" title="General">
					<>General</>
				</ProductFormTab>
				<ProductFormTab name="pricing" title="Pricing">
					<PricingSection />
				</ProductFormTab>
				<ProductFormTab name="inventory" title="Inventory">
					<ProductInventorySection />
				</ProductFormTab>
				<ProductFormTab name="shipping" title="Shipping">
					<ProductShippingSection
						product={ productVariation as PartialProduct }
					/>
				</ProductFormTab>
			</ProductFormLayout>
			<ProductFormFooter />
		</Form>
	);
};
