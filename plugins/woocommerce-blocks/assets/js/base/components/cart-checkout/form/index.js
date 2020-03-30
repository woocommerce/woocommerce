/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const CheckoutForm = ( { className, children } ) => {
	return (
		<form className={ classnames( 'wc-block-checkout-form', className ) }>
			{ children }
		</form>
	);
};

CheckoutForm.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
};

export default CheckoutForm;
