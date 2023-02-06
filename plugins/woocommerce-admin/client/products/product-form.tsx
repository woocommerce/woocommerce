/**
 * External dependencies
 */
import { Form, FormRef, SlotContextProvider } from '@woocommerce/components';
import { PartialProduct, Product } from '@woocommerce/data';
import { PluginArea } from '@wordpress/plugins';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { ProductFormHeader } from './layout/product-form-header';
import { ProductFormLayout } from './layout/product-form-layout';
import { validate } from './product-validation';
import { ProductFormFooter } from './layout/product-form-footer';

export const ProductForm: React.FC< {
	product?: PartialProduct;
	formRef?: Ref< FormRef< Partial< Product > > >;
} > = ( { product, formRef } ) => {
	return (
		<SlotContextProvider>
			<Form< Partial< Product > >
				initialValues={
					product || {
						backorders: 'no',
						name: '',
						reviews_allowed: true,
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
				<ProductFormLayout id="general" product={ product } />
				<ProductFormFooter />
				{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
				<PluginArea scope="woocommerce-product-editor" />
			</Form>
		</SlotContextProvider>
	);
};
