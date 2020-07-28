/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Use `OrderStatus` to display a badge with human-friendly text describing the current order status.
 *
 * @param root0
 * @param root0.order
 * @param root0.className
 * @param root0.orderStatusMap
 * @return {Object} -
 */
const OrderStatus = ( { order, className, orderStatusMap } ) => {
	const { status } = order;
	const classes = classnames( 'woocommerce-order-status', className );
	const indicatorClasses = classnames(
		'woocommerce-order-status__indicator',
		{
			[ 'is-' + status ]: true,
		}
	);
	const label = orderStatusMap[ status ] || status;
	return (
		<div className={ classes }>
			<span className={ indicatorClasses } />
			{ label }
		</div>
	);
};

OrderStatus.propTypes = {
	/**
	 * The order to display a status for.
	 */
	order: PropTypes.object.isRequired,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * A map of status to label for order statuses.
	 */
	orderStatusMap: PropTypes.object,
};

export default OrderStatus;
