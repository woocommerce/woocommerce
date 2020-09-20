/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import Summary from '@woocommerce/base-components/summary';
import { getSetting } from '@woocommerce/settings';

/**
 * Returns an element containing a summary of the product.
 *
 * @param {Object} props                  Incoming props for the component.
 * @param {string} props.className        CSS class name used.
 * @param {string} props.shortDescription Short description for the product.
 * @param {string} props.fullDescription  Full description for the product.
 */
const ProductSummary = ( {
	className,
	shortDescription = '',
	fullDescription = '',
} ) => {
	const source = shortDescription ? shortDescription : fullDescription;

	if ( ! source ) {
		return null;
	}

	return (
		<Summary
			className={ className }
			source={ source }
			maxLength={ 15 }
			countType={ getSetting( 'wordCountType', 'words' ) }
		/>
	);
};

ProductSummary.propTypes = {
	className: PropTypes.string,
	shortDescription: PropTypes.string,
	fullDescription: PropTypes.string,
};

export default ProductSummary;
