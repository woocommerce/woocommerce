/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

class ProductIcon extends Component {
	render() {
		return (
			<img
				src={ this.props.src }
				className={ classnames( this.props.className, 'woocommere-admin-marketing-product-icon' ) }
				alt=""
			/>
		);
	}
}

ProductIcon.propTypes = {
	/**
	 * Icon src.
	 */
	src: PropTypes.string.isRequired,
	/**
	 * Additional classNames.
	 */
	className: PropTypes.string,
};

export default ProductIcon;
