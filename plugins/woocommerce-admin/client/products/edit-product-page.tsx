/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	EXPERIMENTAL_PRODUCT_FORM_STORE_NAME,
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	PartialProduct,
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner, FormRef } from '@woocommerce/components';
import { useParams } from 'react-router-dom';
/**
 * Internal dependencies
 */
import { ProductForm } from './product-form';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductVariationForm } from './product-variation-form';
import './add-edit-product-page.scss';

const EditProductPage: React.FC = () => {
	const { productId, variationId } = useParams();
	const isProductVariation = !! variationId;
	const previousProductRef = useRef< PartialProduct >();
	const formRef = useRef< FormRef< Partial< Product > > >( null );
	const { product, isLoading, isPendingAction, productVariation } = useSelect(
		( select: WCDataSelector ) => {
			const {
				getProduct,
				hasFinishedResolution: hasProductFinishedResolution,
				isPending,
				getPermalinkParts,
			} = select( PRODUCTS_STORE_NAME );
			const { hasFinishedResolution: hasProductFormFinishedResolution } =
				select( EXPERIMENTAL_PRODUCT_FORM_STORE_NAME );
			const {
				getProductVariation,
				hasFinishedResolution: hasProductVariationFinishedResolution,
			} = select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
			if ( productId ) {
				const retrievedProduct = getProduct(
					parseInt( productId, 10 ),
					undefined
				);
				const permalinkParts = getPermalinkParts(
					parseInt( productId, 10 )
				);
				return {
					product:
						permalinkParts && retrievedProduct
							? retrievedProduct
							: undefined,
					productVariation:
						isProductVariation &&
						getProductVariation( {
							id: parseInt( variationId, 10 ),
							product_id: parseInt( productId, 10 ),
						} ),
					isLoading:
						! hasProductFinishedResolution( 'getProduct', [
							parseInt( productId, 10 ),
						] ) ||
						! hasProductFinishedResolution( 'getPermalinkParts', [
							parseInt( productId, 10 ),
						] ) ||
						! (
							isProductVariation &&
							hasProductVariationFinishedResolution(
								'getProductVariation',
								[ parseInt( variationId, 10 ) ]
							)
						) ||
						! hasProductFormFinishedResolution( 'getProductForm' ),
					isPendingAction:
						isPending( 'createProduct' ) ||
						isPending(
							'deleteProduct',
							parseInt( productId, 10 )
						) ||
						isPending( 'updateProduct', parseInt( productId, 10 ) ),
				};
			}
			return {
				isLoading: false,
				isPendingAction: false,
			};
		}
	);

	useEffect( () => {
		// used for determining the wasDeletedUsingAction condition.
		if (
			previousProductRef.current &&
			product &&
			previousProductRef.current.id !== product.id &&
			formRef.current
		) {
			formRef.current.resetForm( product );
		}
		previousProductRef.current = product;
	}, [ product ] );

	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	const wasDeletedUsingAction =
		previousProductRef.current?.id === product?.id &&
		previousProductRef.current?.status !== 'trash' &&
		product?.status === 'trash';

	return (
		<div className="woocommerce-edit-product">
			{ isLoading && ! product ? (
				<div className="woocommerce-edit-product__spinner">
					<Spinner />
				</div>
			) : null }
			{ product &&
				product.status === 'trash' &&
				! isPendingAction &&
				! wasDeletedUsingAction && (
					<ProductFormLayout id="error">
						<div className="woocommerce-edit-product__error">
							{ __(
								'You cannot edit this item because it is in the Trash. Please restore it and try again.',
								'woocommerce'
							) }
						</div>
					</ProductFormLayout>
				) }
			{ productVariation && product && (
				<ProductVariationForm
					product={ product }
					productVariation={ productVariation }
				/>
			) }
			{ ! isProductVariation &&
				product &&
				( product.status !== 'trash' || wasDeletedUsingAction ) && (
					<ProductForm formRef={ formRef } product={ product } />
				) }
		</div>
	);
};

export default EditProductPage;
