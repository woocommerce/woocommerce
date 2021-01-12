/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductDetails from '../product-details';
import ProductSummary from '../product-summary';
import './style.scss';

const ProductMetadata = ( {
	shortDescription = '',
	fullDescription = '',
	itemData = [],
	variation = [],
} ) => {
	return (
		<div className="wc-block-components-product-metadata">
			<ProductSummary
				className="wc-block-components-product-metadata__description"
				shortDescription={ shortDescription }
				fullDescription={ fullDescription }
			/>
			<ProductDetails details={ itemData } />
			<ProductDetails
				details={ variation.map( ( { attribute = '', value } ) => ( {
					name: attribute,
					value,
				} ) ) }
			/>
		</div>
	);
};

ProductMetadata.propTypes = {
	shortDescription: PropTypes.string,
	fullDescription: PropTypes.string,
	itemData: PropTypes.array,
	variation: PropTypes.arrayOf(
		PropTypes.shape( {
			attribute: PropTypes.string,
			value: PropTypes.string.isRequired,
		} )
	),
};

export default ProductMetadata;
