/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

export const AddProduct: React.FC = () => {
	useEffect( () => {
		recordEvent( 'view_add_product_new_management_experience' );
	}, [] );
	return (
		<div className="woocommerce-add-product">
			<h1>Add Product</h1>
		</div>
	);
};
