/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { Form, FormRef, SlotContextProvider } from '@woocommerce/components';
import { PluginArea } from '@wordpress/plugins';
import { PartialProduct, ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import PostsNavigation from './shared/posts-navigation';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductFormFooter } from './layout/product-form-footer';
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
		<SlotContextProvider>
			<Form< Partial< ProductVariation > >
				initialValues={ productVariation }
				errors={ {} }
				ref={ formRef }
			>
				<ProductVariationFormHeader />
				<ProductFormLayout
					key={ productVariation.id }
					id="variation"
					product={ productVariation as PartialProduct }
				/>
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
						nextLabel={ __(
							'Next product variation',
							'woocommerce'
						) }
					/>
				</div>
				{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
				<PluginArea scope="woocommerce-product-editor" />
			</Form>
		</SlotContextProvider>
	);
};
