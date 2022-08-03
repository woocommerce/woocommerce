/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

export const AddProductPage: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );
	return (
		<div className="woocommerce-add-product">
			<h1>Add Product</h1>
		</div>
	);
};
