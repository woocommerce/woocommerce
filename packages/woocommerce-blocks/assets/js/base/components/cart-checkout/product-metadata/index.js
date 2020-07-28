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
		<div className="wc-block-components-product-metadata">
			<ProductSummary
				className="wc-block-components-product-metadata__description"
				shortDescription={ shortDescription }
				fullDescription={ fullDescription }
			/>
			<ProductVariationData
				className="wc-block-components-product-metadata__variation-data"
				variation={ variation }
			/>
		</div>
	);
};

ProductMetadata.propTypes = {
	shortDescription: PropTypes.string,
	fullDescription: PropTypes.string,
	variation: PropTypes.array,
};

export default ProductMetadata;
