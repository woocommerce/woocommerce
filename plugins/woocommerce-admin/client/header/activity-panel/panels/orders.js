/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import interpolateComponents from 'interpolate-components';
import { keyBy, map, merge } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	EmptyContent,
	Flag,
	Link,
	OrderStatus,
	Section,
} from '@woocommerce/components';
import { formatCurrency } from 'lib/currency-format';
import { getNewPath } from '@woocommerce/navigation';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import ActivityOutboundLink from '../activity-outbound-link';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import { DEFAULT_ACTIONABLE_STATUSES } from 'analytics/settings/config';
import withSelect from 'wc-api/with-select';

class OrdersPanel extends Component {
	renderEmptyCard() {
		const { hasNonActionableOrders } = this.props;
		if ( hasNonActionableOrders ) {
			return (
				<ActivityCard
					className="woocommerce-empty-activity-card"
					title={ __(
						'You have no orders to fulfill',
						'woocommerce-admin'
					) }
					icon={ <Gridicon icon="checkmark" size={ 48 } /> }
				>
					{ __(
						"Good job, you've fulfilled all of your new orders!",
						'woocommerce-admin'
					) }
				</ActivityCard>
			);
		}

		return (
			<ActivityCard
				className="woocommerce-empty-activity-card"
				title={ __(
					'You have no orders to fulfill',
					'woocommerce-admin'
				) }
				icon={ <Gridicon icon="time" size={ 48 } /> }
				actions={
					<Button
						href="https://docs.woocommerce.com/document/managing-orders/"
						isDefault
						target="_blank"
					>
						{ __( 'Learn more', 'woocommerce-admin' ) }
					</Button>
				}
			>
				{ __(
					"You're still waiting for your customers to make their first orders. " +
						'While you wait why not learn how to manage orders?',
					'woocommerce-admin'
				) }
			</ActivityCard>
		);
	}

	renderOrders() {
		const { orders } = this.props;

		if ( orders.length === 0 ) {
			return this.renderEmptyCard();
		}

		const getCustomerString = ( order ) => {
			const extendedInfo = order.extended_info || {};
			const { first_name: firstName, last_name: lastName } =
				extendedInfo.customer || {};

			if ( ! firstName && ! lastName ) {
				return '';
			}

			const name = [ firstName, lastName ].join( ' ' );
			return sprintf(
				__(
					/* translators: describes who placed an order, e.g. Order #123 placed by John Doe */
					'placed by {{customerLink}}%(customerName)s{{/customerLink}}',
					'woocommerce-admin'
				),
				{
					customerName: name,
				}
			);
		};

		const orderCardTitle = ( order ) => {
			const {
				extended_info: extendedInfo,
				order_id: orderId,
				order_number: orderNumber,
			} = order;
			const { customer } = extendedInfo || {};
			const customerUrl = customer.customer_id
				? getNewPath( {}, '/analytics/customers', {
						filter: 'single_customer',
						customers: customer.customer_id,
				  } )
				: null;

			return (
				<Fragment>
					{ interpolateComponents( {
						mixedString: sprintf(
							__(
								'Order {{orderLink}}#%(orderNumber)s{{/orderLink}} %(customerString)s {{destinationFlag/}}',
								'woocommerce-admin'
							),
							{
								orderNumber,
								customerString: getCustomerString( order ),
							}
						),
						components: {
							orderLink: (
								<Link
									href={ getAdminLink(
										'post.php?action=edit&post=' + orderId
									) }
									type="wp-admin"
								/>
							),
							destinationFlag: customer.country ? (
								<Flag
									code={ customer.country }
									round={ false }
								/>
							) : null,
							customerLink: customerUrl ? (
								<Link href={ customerUrl } type="wc-admin" />
							) : (
								<span />
							),
						},
					} ) }
				</Fragment>
			);
		};

		const cards = [];
		orders.forEach( ( order ) => {
			const extendedInfo = order.extended_info || {};
			const productsCount =
				extendedInfo && extendedInfo.products
					? extendedInfo.products.length
					: 0;

			const total = order.total_sales;

			cards.push(
				<ActivityCard
					key={ order.order_id }
					className="woocommerce-order-activity-card"
					title={ orderCardTitle( order ) }
					date={ order.date_created_gmt }
					subtitle={
						<div>
							<span>
								{ sprintf(
									_n(
										'%d product',
										'%d products',
										productsCount,
										'woocommerce-admin'
									),
									productsCount
								) }
							</span>
							<span>{ formatCurrency( total ) }</span>
						</div>
					}
					actions={
						<Button
							isDefault
							href={ getAdminLink(
								'post.php?action=edit&post=' + order.order_id
							) }
						>
							{ __( 'Begin fulfillment' ) }
						</Button>
					}
				>
					<OrderStatus
						order={ order }
						orderStatusMap={ getSetting( 'orderStatuses', {} ) }
					/>
				</ActivityCard>
			);
		} );
		return (
			<Fragment>
				{ cards }
				<ActivityOutboundLink href={ 'edit.php?post_type=shop_order' }>
					{ __( 'Manage all orders', 'woocommerce-admin' ) }
				</ActivityOutboundLink>
			</Fragment>
		);
	}

	render() {
		const { orders, isRequesting, isError, orderStatuses } = this.props;

		if ( isError ) {
			if ( ! orderStatuses.length ) {
				return (
					<EmptyContent
						title={ __(
							"You currently don't have any actionable statuses. " +
								'To display orders here, select orders that require further review in settings.',
							'woocommerce-admin'
						) }
						actionLabel={ __( 'Settings', 'woocommerce-admin' ) }
						actionURL={ getAdminLink(
							'admin.php?page=wc-admin&path=/analytics/settings'
						) }
					/>
				);
			}

			const title = __(
				'There was an error getting your orders. Please try again.',
				'woocommerce-admin'
			);
			const actionLabel = __( 'Reload', 'woocommerce-admin' );
			const actionCallback = () => {
				// @todo Add tracking for how often an error is displayed, and the reload action is clicked.
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

		const title =
			isRequesting || orders.length
				? __( 'Orders', 'woocommerce-admin' )
				: __( 'No orders to fulfill', 'woocommerce-admin' );

		return (
			<Fragment>
				<ActivityHeader title={ title } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-order-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						this.renderOrders()
					) }
				</Section>
			</Fragment>
		);
	}
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
	withSelect( ( select, props ) => {
		const { hasActionableOrders } = props;
		const {
			getItems,
			getItemsTotalCount,
			getItemsError,
			isGetItemsRequesting,
			getReportItems,
			getReportItemsError,
			isReportItemsRequesting,
		} = select( 'wc-api' );
		const { getSetting: getMutableSetting } = select( SETTINGS_STORE_NAME );
		const {
			woocommerce_actionable_order_statuses: orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
		} = getMutableSetting( 'wc_admin', 'wcAdminSettings', {} );
		if ( ! orderStatuses.length ) {
			return {
				orders: [],
				isError: true,
				isRequesting: false,
				orderStatuses,
			};
		}

		if ( hasActionableOrders ) {
			// Query the core Orders endpoint for the most up-to-date statuses.
			const actionableOrdersQuery = {
				page: 1,
				per_page: QUERY_DEFAULTS.pageSize,
				status: orderStatuses,
				_fields: [ 'id', 'date_created_gmt', 'status' ],
			};
			const actionableOrders = Array.from(
				getItems( 'orders', actionableOrdersQuery ).values()
			);
			const isRequestingActionable = isGetItemsRequesting(
				'orders',
				actionableOrdersQuery
			);

			if ( isRequestingActionable ) {
				return {
					isError: Boolean(
						getItemsError( 'orders', actionableOrdersQuery )
					),
					isRequesting: isRequestingActionable,
					orderStatuses,
				};
			}

			// Retrieve the Order stats data from our reporting table.
			const ordersQuery = {
				page: 1,
				per_page: QUERY_DEFAULTS.pageSize,
				extended_info: true,
				order_includes: map( actionableOrders, 'id' ),
			};

			const reportOrders = getReportItems( 'orders', ordersQuery ).data;
			const isError = Boolean(
				getReportItemsError( 'orders', ordersQuery )
			);
			const isRequesting = isReportItemsRequesting(
				'orders',
				ordersQuery
			);
			let orders = [];

			if ( reportOrders && reportOrders.length ) {
				// Merge the core endpoint data with our reporting table.
				const actionableOrdersById = keyBy( actionableOrders, 'id' );
				orders = reportOrders.map( ( order ) =>
					merge(
						{},
						order,
						actionableOrdersById[ order.order_id ] || {}
					)
				);
			}

			return { orders, isError, isRequesting, orderStatuses };
		}

		// Get a count of all orders for messaging purposes.
		// @todo Add a property to settings api for this?
		const allOrdersQuery = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
		};

		getItems( 'orders', allOrdersQuery );
		const totalNonActionableOrders = getItemsTotalCount(
			'orders',
			allOrdersQuery
		);
		const isError = Boolean( getItemsError( 'orders', allOrdersQuery ) );
		const isRequesting = isGetItemsRequesting( 'orders', allOrdersQuery );

		return {
			hasNonActionableOrders: totalNonActionableOrders > 0,
			isError,
			isRequesting,
			orderStatuses,
		};
	} )
)( OrdersPanel );
