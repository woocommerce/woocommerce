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
import { getAdminLink, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import ActivityOutboundLink from '../activity-outbound-link';
import { DEFAULT_ACTIONABLE_STATUSES, QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';

function OrdersPanel( { orders, isRequesting, isError, orderStatuses } ) {
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
					actionURL={ getAdminLink( 'admin.php?page=wc-admin#/analytics/settings' ) }
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

	const menu = (
		<EllipsisMenu label="Demo Menu">
			<MenuTitle>Test</MenuTitle>
			<MenuItem onInvoke={ noop }>Test</MenuItem>
		</EllipsisMenu>
	);

	const getCustomerString = order => {
		const extended_info = order.extended_info || {};
		const { first_name, last_name } = extended_info.customer || {};

		if ( ! first_name && ! last_name ) {
			return '';
		}

		const name = [ first_name, last_name ].join( ' ' );
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

	const orderCardTitle = order => {
		const { extended_info, order_id } = order;
		const { customer } = extended_info || {};
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
							orderNumber: order_id,
							customerString: getCustomerString( order ),
						}
					),
					components: {
						orderLink: <Link href={ 'post.php?action=edit&post=' + order_id } type="wp-admin" />,
						destinationFlag: customer.country ? (
							<Flag code={ customer.country } round={ false } />
						) : null,
						customerLink: customerUrl ? <Link href={ customerUrl } type="wc-admin" /> : <span />,
					},
				} ) }
			</Fragment>
		);
	};

	const cards = [];
	orders.forEach( order => {
		const extended_info = order.extended_info || {};
		const productsCount =
			extended_info && extended_info.products ? extended_info.products.length : 0;

		const total = order.gross_total;
		const refundValue = order.refund_total;
		const remainingTotal = getCurrencyFormatDecimal( total ) + refundValue;

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
								_n( '%d product', '%d products', productsCount, 'woocommerce-admin' ),
								productsCount
							) }
						</span>
						{ refundValue ? (
							<span>
								<s>{ formatCurrency( total ) }</s> { formatCurrency( remainingTotal ) }
							</span>
						) : (
							<span>{ formatCurrency( total ) }</span>
						) }
					</div>
				}
				actions={
					<Button isDefault href={ getAdminLink( 'post.php?action=edit&post=' + order.order_id ) }>
						{ __( 'Begin fulfillment' ) }
					</Button>
				}
			>
				<OrderStatus order={ order } />
			</ActivityCard>
		);
	} );

	return (
		<Fragment>
			<ActivityHeader title={ __( 'Orders', 'woocommerce-admin' ) } menu={ menu } />
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
						{ cards }
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
	withSelect( ( select, props ) => {
		const { getReportItems, getReportItemsError, isReportItemsRequesting } = select( 'wc-api' );
		const { isEmpty } = props;
		const orderStatuses =
			wcSettings.wcAdminSettings.woocommerce_actionable_order_statuses ||
			DEFAULT_ACTIONABLE_STATUSES;

		if ( ! orderStatuses.length ) {
			return { orders: [], isError: true, isRequesting: false, orderStatuses };
		}

		if ( isEmpty ) {
			return { orders: [], isError: false, isRequesting: false, orderStatuses };
		}

		const ordersQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			status_is: orderStatuses,
			extended_info: true,
		};

		const orders = getReportItems( 'orders', ordersQuery ).data;
		const isError = Boolean( getReportItemsError( 'orders', ordersQuery ) );
		const isRequesting = isReportItemsRequesting( 'orders', ordersQuery );

		return { orders, isError, isRequesting, orderStatuses };
	} )
)( OrdersPanel );
