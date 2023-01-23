/**
 * External dependencies
 */
import {
	Form,
	FormRef,
	__experimentalWooProductSectionItem as WooProductSectionItem,
	SlotContextProvider,
} from '@woocommerce/components';
import { PartialProduct, Product } from '@woocommerce/data';
import { PluginArea } from '@wordpress/plugins';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { ProductFormHeader } from './layout/product-form-header';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductInventorySection } from './sections/product-inventory-section';
import { PricingSection } from './sections/pricing-section';
import { ProductVariationsSection } from './sections/product-variations-section';
import { validate } from './product-validation';
import { OptionsSection } from './sections/options-section';
import { ProductFormFooter } from './layout/product-form-footer';
import { ProductFormTab } from './product-form-tab';
import { TAB_GENERAL_ID, TAB_SHIPPING_ID } from './fills/constants';

export const ProductForm: React.FC< {
	product?: PartialProduct;
	formRef?: Ref< FormRef< Partial< Product > > >;
} > = ( { product, formRef } ) => {
	return (
		<SlotContextProvider>
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
					<ProductFormTab name="general" title="General">
						<WooProductSectionItem.Slot
							location={ TAB_GENERAL_ID }
						/>
					</ProductFormTab>
					<ProductFormTab
						name="pricing"
						title="Pricing"
						disabled={ !! product?.variations?.length }
					>
						<PricingSection />
					</ProductFormTab>
					<ProductFormTab
						name="inventory"
						title="Inventory"
						disabled={ !! product?.variations?.length }
					>
						<ProductInventorySection />
					</ProductFormTab>
					<ProductFormTab
						name="shipping"
						title="Shipping"
						disabled={ !! product?.variations?.length }
					>
						<WooProductSectionItem.Slot
							location={ TAB_SHIPPING_ID }
							fillProps={ { product } }
						/>
					</ProductFormTab>
					{ window.wcAdminFeatures[
						'product-variation-management'
					] ? (
						<ProductFormTab name="options" title="Options">
							<OptionsSection />
							<ProductVariationsSection />
						</ProductFormTab>
					) : (
						<></>
					) }
				</ProductFormLayout>
				<ProductFormFooter />
				{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
				<PluginArea scope="woocommerce-product-editor" />
			</Form>
		</SlotContextProvider>
	);
};
