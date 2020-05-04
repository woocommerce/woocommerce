/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductName = ( { name, permalink, disabled } ) => {
	return (
		// we use tabIndex -1 to prevent the link from being focused, pointer-events
		// disabled click events, so we get an almost disabled link.
		<a
			className="wc-block-product-name"
			href={ permalink }
			tabIndex={ disabled ? -1 : 0 }
		>
			{ decodeEntities( name ) }
		</a>
	);
};

ProductName.propTypes = {
	name: PropTypes.string,
	permalink: PropTypes.string,
};

export default ProductName;
