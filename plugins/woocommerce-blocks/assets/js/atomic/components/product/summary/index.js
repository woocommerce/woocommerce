/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';

const ProductSummary = ( { className, product } ) => {
	if ( ! product.description ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-grid__product-summary'
			) }
			dangerouslySetInnerHTML={ {
				__html: product.description,
			} }
		/>
	);
};

ProductSummary.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductSummary;
