/**
 * External dependencies
 */
import { RawHTML } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductVariationData from '../product-variation-data';
import './style.scss';

const ProductMetadata = ( { summary, variation } ) => {
	return (
		<div className="wc-block-product-metadata">
			{ summary && <RawHTML>{ summary }</RawHTML> }
			{ variation && <ProductVariationData variation={ variation } /> }
		</div>
	);
};

ProductMetadata.propTypes = {
	summary: PropTypes.string,
	variation: PropTypes.array,
};

export default ProductMetadata;
