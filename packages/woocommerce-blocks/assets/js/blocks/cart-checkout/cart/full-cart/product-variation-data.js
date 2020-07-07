/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Returns a formatted element containing variation details.
 */
const ProductVariationData = ( { variation } ) => {
	const variationsText = variation
		.map( ( v ) => {
			if ( v.attribute ) {
				return `${ decodeEntities( v.attribute ) }: ${ decodeEntities(
					v.value
				) }`;
			}
			// Support for product attributes with no name/key
			return `${ decodeEntities( v.value ) }`;
		} )
		.join( ' / ' );

	return (
		<div className="wc-block-cart-item__product-attributes">
			{ variationsText }
		</div>
	);
};

export default ProductVariationData;
