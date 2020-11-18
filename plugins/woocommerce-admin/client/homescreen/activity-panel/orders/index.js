/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import interpolateComponents from 'interpolate-components';
import { keyBy, map, merge } from 'lodash';
import {
	EmptyContent,
	Flag,
	H,
	Link,
	OrderStatus,
	Section,
} from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { REPORTS_STORE_NAME, ITEMS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	ActivityCard,
	ActivityCardPlaceholder,
} from '../../../header/activity-panel/activity-card';
import { CurrencyContext } from '../../../lib/currency-context';
import './style.scss';

class OrdersPanel extends Component {
	recordOrderEvent( eventName ) {
		recordEvent( `activity_panel_orders_${ eventName }`, {} );
	}

	renderEmptyCard() {
		return (
			<Fragment>
				<ActivityCard
					className="woocommerce-empty-activity-card"
					title=""
					icon=""
				>
					<span
						className="woocommerce-order-empty__success-icon"
						role="img"
						aria-labelledby="woocommerce-order-empty-message"
					>
						🎉
					</span>
					<H id="woocommerce-order-empty-message">
						{ __(
							'You’ve fulfilled all your orders',
							'woocommerce-admin'
						) }
					</H>
				</ActivityCard>
				<Link
					href={ 'edit.php?post_type=shop_order' }
					onClick={ () => this.recordOrderEvent( 'orders_manage' ) }
					className="woocommerce-layout__activity-panel-outbound-link woocommerce-layout__activity-panel-empty"
					type="wp-admin"
				>
					{ __( 'Manage all orders', 'woocommerce-admin' ) }
				</Link>
			</Fragment>
		);
	}

	renderOrders() {
		const { orders } = this.props;
		const Currency = this.context;

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
			return `{{customerLink}}${ name }{{/customerLink}}`;
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
								'{{orderLink}}Order #%(orderNumber)s{{/orderLink}} %(customerString)s',
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
									onClick={ () =>
										this.recordOrderEvent( 'order_number' )
									}
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
								<Link
									href={ customerUrl }
									onClick={ () =>
										this.recordOrderEvent( 'customer_name' )
									}
									type="wc-admin"
								/>
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
			const {
				date_created_gmt: dateCreatedGmt,
				extended_info: extendedInfo,
				order_id: orderId,
				total_sales: totalSales,
			} = order;
			const productsCount =
				extendedInfo && extendedInfo.products
					? extendedInfo.products.length
					: 0;

			const total = totalSales;

			cards.push(
				<ActivityCard
					key={ orderId }
					className="woocommerce-order-activity-card"
					title={ orderCardTitle( order ) }
					date={ dateCreatedGmt }
					onClick={ ( { target } ) => {
						this.recordOrderEvent( 'orders_begin_fulfillment' );
						if ( ! target.href ) {
							window.location.href = getAdminLink(
								`post.php?action=edit&post=${ orderId }`
							);
						}
					} }
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
							<span>{ Currency.formatAmount( total ) }</span>
						</div>
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
				<Link
					href={ 'edit.php?post_type=shop_order' }
					className="woocommerce-layout__activity-panel-outbound-link"
					onClick={ () => this.recordOrderEvent( 'orders_manage' ) }
					type="wp-admin"
				>
					{ __( 'Manage all orders', 'woocommerce-admin' ) }
				</Link>
			</Fragment>
		);
	}

	render() {
		const { isRequesting, isError, orderStatuses } = this.props;

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

		return (
			<Fragment>
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-order-activity-card"
							hasAction
							hasDate
							lines={ 1 }
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
	isError: PropTypes.bool,
	isRequesting: PropTypes.bool,
	countUnreadOrders: PropTypes.number,
	orders: PropTypes.array.isRequired,
	orderStatuses: PropTypes.array,
};

OrdersPanel.defaultProps = {
	orders: [],
	isError: false,
	isRequesting: false,
};

OrdersPanel.contextType = CurrencyContext;

export default withSelect( ( select, props ) => {
	const { countUnreadOrders, orderStatuses } = props;
	const { getItems, getItemsError } = select( ITEMS_STORE_NAME );
	const { getReportItems, getReportItemsError, isResolving } = select(
		REPORTS_STORE_NAME
	);

	if ( ! orderStatuses.length && countUnreadOrders === 0 ) {
		return { isRequesting: false };
	}

	// Query the core Orders endpoint for the most up-to-date statuses.
	const actionableOrdersQuery = {
		page: 1,
		per_page: 5,
		status: orderStatuses,
		_fields: [ 'id', 'date_created_gmt', 'status' ],
	};
	/* eslint-disable @wordpress/no-unused-vars-before-return */
	const actionableOrders = Array.from(
		getItems( 'orders', actionableOrdersQuery ).values()
	);
	const isRequestingActionable = isResolving( 'getItems', [
		'orders',
		actionableOrdersQuery,
	] );

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
		per_page: 5,
		extended_info: true,
		match: 'any',
		order_includes: map( actionableOrders, 'id' ),
		status_is: orderStatuses,
		_fields: [
			'order_id',
			'order_number',
			'status',
			'total_sales',
			'extended_info.customer',
			'extended_info.products',
		],
	};

	const reportOrders = getReportItems( 'orders', ordersQuery ).data;
	const isError = Boolean( getReportItemsError( 'orders', ordersQuery ) );
	const isRequesting = isResolving( 'getReportItems', [
		'orders',
		ordersQuery,
	] );
	/* eslint-enable @wordpress/no-unused-vars-before-return */
	let orders = [];

	if (
		countUnreadOrders === null ||
		typeof reportOrders === 'undefined' ||
		( reportOrders.length && ! actionableOrders.length ) ||
		isRequesting
	) {
		return { isRequesting: true };
	}

	if ( reportOrders.length ) {
		// Merge the core endpoint data with our reporting table.
		const actionableOrdersById = keyBy( actionableOrders, 'id' );
		orders = reportOrders.map( ( order ) =>
			merge( {}, order, actionableOrdersById[ order.order_id ] || {} )
		);
	}
	return {
		orders,
		isError,
		isRequesting,
		orderStatuses,
	};
} )( OrdersPanel );
