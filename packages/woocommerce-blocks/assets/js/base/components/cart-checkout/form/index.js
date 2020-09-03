/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const CheckoutForm = ( {
	className,
	children,
	onSubmit = ( event ) => void event,
} ) => {
	const formOnSubmit = ( event ) => {
		event.preventDefault();
		onSubmit( event );
	};
	return (
		<form
			className={ classnames(
				'wc-block-components-checkout-form',
				className
			) }
			onSubmit={ formOnSubmit }
		>
			{ children }
		</form>
	);
};

CheckoutForm.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	onSubmit: PropTypes.func,
};

export default CheckoutForm;
