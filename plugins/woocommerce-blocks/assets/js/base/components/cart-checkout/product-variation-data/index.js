/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Returns a formatted element containing variation details.
 *
 * @param {Object}        props           Incoming props for the component.
 * @param {string}        props.className CSS class used.
 * @param {Array<Object>} props.variation Variations in use.
 */
const ProductVariationData = ( { className, variation = [] } ) => {
	if ( ! variation || variation.length === 0 ) {
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
		<div
			className={ classNames(
				'wc-block-components-product-variation-data',
				className
			) }
		>
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
