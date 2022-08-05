/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Form, Spinner } from '@woocommerce/components';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductFormActions } from './product-form-actions';
import { ProductDetailsSection } from './sections/product-details-section';
import { ProductImagesSection } from './sections/product-images-section';
import './product-page.scss';

const EditProductPage: React.FC = () => {
	const { productId } = useParams();
	const { product, isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { getProduct, hasFinishedResolution } =
			select( PRODUCTS_STORE_NAME );
		if ( productId ) {
			return {
				product: getProduct( parseInt( productId, 10 ), undefined ),
				isLoading: ! hasFinishedResolution( 'getProduct', [
					parseInt( productId, 10 ),
				] ),
			};
		}
		return {
			isLoading: false,
		};
	} );

	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-edit-product">
			{ isLoading && ! product ? <Spinner /> : null }
			{ product && product.status === 'trash' && (
				<ProductFormLayout>
					<div className="woocommerce-edit-product__error">
						{ __(
							'You cannot edit this item because it is in the Trash. Please restore it and try again.',
							'woocommerce'
						) }
					</div>
				</ProductFormLayout>
			) }
			{ product && product.status !== 'trash' && (
				<Form< Partial< Product > >
					initialValues={ product || {} }
					errors={ {} }
				>
					<ProductFormLayout>
						<ProductDetailsSection />
						<ProductImagesSection />

						<ProductFormActions />
					</ProductFormLayout>
				</Form>
			) }
		</div>
	);
};

export default EditProductPage;
