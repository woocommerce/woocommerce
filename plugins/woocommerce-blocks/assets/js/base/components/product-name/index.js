/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductName = ( {
	className = '',
	disabled = false,
	name,
	permalink = '',
	...props
} ) => {
	const classes = classnames( 'wc-block-components-product-name', className );
	return disabled ? (
		<span className={ classes } { ...props }>
			{ decodeEntities( name ) }
		</span>
	) : (
		<a className={ classes } href={ permalink } { ...props }>
			{ decodeEntities( name ) }
		</a>
	);
};

ProductName.propTypes = {
	className: PropTypes.string,
	disabled: PropTypes.bool,
	name: PropTypes.string.isRequired,
	permalink: PropTypes.string,
};

export default ProductName;
