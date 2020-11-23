/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductName = ( { name, permalink, disabled = false } ) => {
	return disabled ? (
		<span className="wc-block-components-product-name">
			{ decodeEntities( name ) }
		</span>
	) : (
		<a className="wc-block-components-product-name" href={ permalink }>
			{ decodeEntities( name ) }
		</a>
	);
};

ProductName.propTypes = {
	disabled: PropTypes.bool,
	name: PropTypes.string,
	permalink: PropTypes.string,
};

export default ProductName;
