/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductBadge = ( { children, className } ) => {
	return (
		<div
			className={ classNames(
				'wc-block-components-product-badge',
				className
			) }
		>
			{ children }
		</div>
	);
};

ProductBadge.propTypes = {
	className: PropTypes.string,
};

export default ProductBadge;
