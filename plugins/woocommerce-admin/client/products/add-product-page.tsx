/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductForm } from './product-form';
import { ProductTourContainer } from './tour';
import './product-page.scss';

const AddProductPage: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-add-product">
			<ProductForm />
			<ProductTourContainer />
		</div>
	);
};

export default AddProductPage;
