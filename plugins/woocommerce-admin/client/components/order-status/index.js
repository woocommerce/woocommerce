/** @format */

/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const OrderStatus = ( { order, className } ) => {
	const { status } = order;
	const { orderStatuses } = wcSettings;
	const classes = classnames( 'woocommerce-order-status', className );
	const indicatorClasses = classnames( 'woocommerce-order-status__indicator', {
		[ 'is-' + status ]: true,
	} );
	const label = orderStatuses[ 'wc-' + status ] || status;
	return (
		<div className={ classes }>
			<span className={ indicatorClasses } />
			{ label }
		</div>
	);
};

OrderStatus.propTypes = {
	order: PropTypes.object.isRequired,
	className: PropTypes.string,
};

export default OrderStatus;
