/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductName = ( { name, permalink } ) => {
	return (
		<a className="wc-block-product-name" href={ permalink }>
			{ name }
		</a>
	);
};

ProductName.propTypes = {
	name: PropTypes.string,
	permalink: PropTypes.string,
};

export default ProductName;
