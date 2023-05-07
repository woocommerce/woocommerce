/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_FORM_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductForm } from './product-form';
import { ProductTourContainer } from './tour';
import './product-page.scss';
import './fills';

const AddProductPage: React.FC = () => {
	const { isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { hasFinishedResolution: hasProductFormFinishedResolution } =
			select( EXPERIMENTAL_PRODUCT_FORM_STORE_NAME );
		return {
			isLoading: ! hasProductFormFinishedResolution( 'getProductForm' ),
		};
	} );
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-add-product">
			{ isLoading ? (
				<div className="woocommerce-edit-product__spinner">
					<Spinner />
				</div>
			) : (
				<>
					<ProductForm />
					<ProductTourContainer />
				</>
			) }
		</div>
	);
};

export default AddProductPage;
