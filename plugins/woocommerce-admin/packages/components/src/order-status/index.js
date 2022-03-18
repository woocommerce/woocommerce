/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Use `OrderStatus` to display a badge with human-friendly text describing the current order status.
 *
 * @param {Object}  props
 * @param {Object}  props.order
 * @param {string}  props.order.status
 * @param {string}  props.className
 * @param {Object}  props.orderStatusMap
 * @param {boolean} props.labelPositionToLeft
 * @return {Object} -
 */
const OrderStatus = ( {
	order: { status },
	className,
	orderStatusMap,
	labelPositionToLeft = false,
} ) => {
	const indicatorClasses = classnames(
		'woocommerce-order-status__indicator',
		{
			[ 'is-' + status ]: true,
		}
	);
	const label = orderStatusMap[ status ] || status;

	return (
		<div className={ classnames( 'woocommerce-order-status', className ) }>
			{ labelPositionToLeft ? (
				<Fragment>
					{ label }
					<span className={ indicatorClasses } />
				</Fragment>
			) : (
				<Fragment>
					<span className={ indicatorClasses } />
					{ label }
				</Fragment>
			) }
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
