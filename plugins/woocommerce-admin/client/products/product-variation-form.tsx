/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { Form, FormRef } from '@woocommerce/components';
import { PartialProduct, ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import PostsNavigation from './shared/posts-navigation';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductFormFooter } from './layout/product-form-footer';
import { ProductFormTab } from './product-form-tab';
import { PricingSection } from './sections/pricing-section';
import { ProductInventorySection } from './sections/product-inventory-section';
import { ProductShippingSection } from './sections/product-shipping-section';
import { ProductVariationDetailsSection } from './sections/product-variation-details-section';
import { ProductVariationFormHeader } from './layout/product-variation-form-header';
import useProductVariationNavigation from './hooks/use-product-variation-navigation';
import './product-variation-form.scss';

export const ProductVariationForm: React.FC< {
	product: PartialProduct;
	productVariation: Partial< ProductVariation >;
} > = ( { product, productVariation } ) => {
	const previousVariationIdRef = useRef< number >();
	const formRef = useRef< FormRef< Partial< ProductVariation > > >( null );

	const navigationProps = useProductVariationNavigation( {
		product,
		productVariation,
	} );

	useEffect( () => {
		if (
			productVariation &&
			previousVariationIdRef.current !== productVariation.id
		) {
			formRef.current?.resetForm( productVariation );
			previousVariationIdRef.current = productVariation.id;
		}
	}, [ productVariation ] );

	return (
		<Form< Partial< ProductVariation > >
			initialValues={ productVariation }
			errors={ {} }
			ref={ formRef }
		>
			<ProductVariationFormHeader />
			<ProductFormLayout key={ productVariation.id }>
				<ProductFormTab name="general" title="General">
					<ProductVariationDetailsSection />
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

			<div className="product-variation-form__navigation">
				<PostsNavigation
					{ ...navigationProps }
					actionLabel={ __(
						'Return to main product',
						'woocommerce'
					) }
					prevLabel={ __(
						'Previous product variation',
						'woocommerce'
					) }
					nextLabel={ __( 'Next product variation', 'woocommerce' ) }
				/>
			</div>
		</Form>
	);
};
