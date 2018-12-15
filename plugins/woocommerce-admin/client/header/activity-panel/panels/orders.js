/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { noop } from 'lodash';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	EmptyContent,
	Flag,
	Link,
	MenuTitle,
	MenuItem,
	OrderStatus,
	Section,
} from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getAdminLink } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import ActivityOutboundLink from '../activity-outbound-link';
import { getOrderRefundTotal } from 'lib/order-values';
import { QUERY_DEFAULTS } from 'store/constants';
import withSelect from 'wc-api/with-select';

function OrdersPanel( { orders, isRequesting, isError } ) {
	if ( isError ) {
		const title = __( 'There was an error getting your orders. Please try again.', 'wc-admin' );
		const actionLabel = __( 'Reload', 'wc-admin' );
		const actionCallback = () => {
			// TODO Add tracking for how often an error is displayed, and the reload action is clicked.
			window.location.reload();
		};

		return (
			<Fragment>
				<EmptyContent
					title={ title }
					actionLabel={ actionLabel }
					actionURL={ null }
					actionCallback={ actionCallback }
				/>
			</Fragment>
		);
	}

	const menu = (
		<EllipsisMenu label="Demo Menu">
			<MenuTitle>Test</MenuTitle>
			<MenuItem onInvoke={ noop }>Test</MenuItem>
		</EllipsisMenu>
	);

	const orderCardTitle = ( order, address ) => {
		const name = `${ address.first_name } ${ address.last_name }`;

		return (
			<Fragment>
				{ interpolateComponents( {
					mixedString: sprintf(
						__(
							/* eslint-disable-next-line max-len */
							'Order {{orderLink}}#%(orderNumber)s{{/orderLink}} placed by {{customerLink}}%(customerName)s{{/customerLink}} {{destinationFlag/}}',
							'wc-admin'
						),
						{
							orderNumber: order.number,
							customerName: name,
						}
					),
					components: {
						orderLink: <Link href={ 'post.php?action=edit&post=' + order.id } type="wp-admin" />,
						// @TODO: Hook up customer name link
						customerLink: <Link href={ '#' } type="wp-admin" />,
						destinationFlag: <Flag order={ order } round={ false } height={ 9 } width={ 12 } />,
					},
				} ) }
			</Fragment>
		);
	};

	return (
		<Fragment>
			<ActivityHeader title={ __( 'Orders', 'wc-admin' ) } menu={ menu } />
			<Section>
				{ isRequesting ? (
					<ActivityCardPlaceholder
						className="woocommerce-order-activity-card"
						hasAction
						hasDate
						lines={ 2 }
					/>
				) : (
					<Fragment>
						{ orders.map( ( order, i ) => {
							// We want the billing address, but shipping can be used as a fallback.
							const address = { ...order.shipping, ...order.billing };
							const productsCount = order.line_items.reduce(
								( total, line ) => total + line.quantity,
								0
							);

							const total = order.total;
							const refundValue = getOrderRefundTotal( order );
							const remainingTotal = getCurrencyFormatDecimal( order.total ) + refundValue;

							return (
								<ActivityCard
									key={ i }
									className="woocommerce-order-activity-card"
									title={ orderCardTitle( order, address ) }
									date={ order.date_created }
									subtitle={
										<div>
											<span>
												{ sprintf(
													_n( '%d product', '%d products', productsCount, 'wc-admin' ),
													productsCount
												) }
											</span>
											{ refundValue ? (
												<span>
													<s>{ formatCurrency( total, order.currency ) }</s>{' '}
													{ formatCurrency( remainingTotal, order.currency ) }
												</span>
											) : (
												<span>{ formatCurrency( total, order.currency ) }</span>
											) }
										</div>
									}
									actions={
										<Button
											isDefault
											href={ getAdminLink( 'post.php?action=edit&post=' + order.id ) }
										>
											{ __( 'Begin fulfillment' ) }
										</Button>
									}
								>
									<OrderStatus order={ order } />
								</ActivityCard>
							);
						} ) }
						<ActivityOutboundLink href={ 'edit.php?post_type=shop_order' }>
							{ __( 'Manage all orders' ) }
						</ActivityOutboundLink>
					</Fragment>
				) }
			</Section>
		</Fragment>
	);
}

OrdersPanel.propTypes = {
	orders: PropTypes.array.isRequired,
	isError: PropTypes.bool,
	isRequesting: PropTypes.bool,
};

OrdersPanel.defaultProps = {
	orders: [],
	isError: false,
	isRequesting: false,
};

export default compose(
	withSelect( select => {
		const { getOrders, getOrdersError, isGetOrdersRequesting } = select( 'wc-api' );
		const ordersQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			status: 'processing',
		};

		const orders = getOrders( ordersQuery );
		const isError = Boolean( getOrdersError( ordersQuery ) );
		const isRequesting = isGetOrdersRequesting( ordersQuery );

		return { orders, isError, isRequesting };
	} )
)( OrdersPanel );
