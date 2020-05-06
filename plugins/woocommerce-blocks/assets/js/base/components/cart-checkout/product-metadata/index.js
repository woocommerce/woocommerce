/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductVariationData from '../product-variation-data';
import ProductSummary from '../product-summary';
import './style.scss';

const ProductMetadata = ( {
	shortDescription = '',
	fullDescription = '',
	variation = [],
} ) => {
	return (
		<div className="wc-block-product-metadata">
			<ProductSummary
				shortDescription={ shortDescription }
				fullDescription={ fullDescription }
			/>
			<ProductVariationData variation={ variation } />
		</div>
	);
};

ProductMetadata.propTypes = {
	shortDescription: PropTypes.string,
	fullDescription: PropTypes.string,
	variation: PropTypes.array,
};

export default ProductMetadata;
