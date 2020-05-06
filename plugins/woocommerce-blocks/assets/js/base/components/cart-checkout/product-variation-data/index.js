/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import PropTypes from 'prop-types';

/**
 * Returns a formatted element containing variation details.
 */
const ProductVariationData = ( { variation = [] } ) => {
	if ( ! variation ) {
		return null;
	}

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
		<div className="wc-block-product-variation-data">
			{ variationsText }
		</div>
	);
};

ProductVariationData.propTypes = {
	variation: PropTypes.arrayOf(
		PropTypes.shape( {
			attribute: PropTypes.string,
			value: PropTypes.string.isRequired,
		} )
	),
};

export default ProductVariationData;
