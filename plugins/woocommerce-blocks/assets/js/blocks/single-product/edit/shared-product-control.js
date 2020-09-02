/**
 * External dependencies
 */
import ProductControl from '@woocommerce/editor-components/product-control';

/**
 * Allows a product to be selected for display.
 */
const SharedProductControl = ( { attributes, setAttributes } ) => (
	<ProductControl
		selected={ attributes.productId || 0 }
		showVariations
		onChange={ ( value = [] ) => {
			const id = value[ 0 ] ? value[ 0 ].id : 0;
			setAttributes( {
				productId: id,
			} );
		} }
	/>
);

export default SharedProductControl;
