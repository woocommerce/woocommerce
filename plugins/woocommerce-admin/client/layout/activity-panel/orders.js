/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { compose, Fragment } from '@wordpress/element';
import { Dashicon, withAPIData } from '@wordpress/components';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import ActivityCard from './activity-card';
import ActivityHeader from './activity-header';
import { EllipsisMenu, MenuTitle, MenuItem } from 'components/ellipsis-menu';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getOrderRefundTotal } from 'lib/order-values';
import { Section } from 'layout/section';

function OrdersPanel( { orders } ) {
	const { data = [], isLoading } = orders;

	const menu = (
		<EllipsisMenu label="Demo Menu">
			<MenuTitle>Test</MenuTitle>
			<MenuItem onInvoke={ noop }>Test</MenuItem>
		</EllipsisMenu>
	);

	return (
		<Fragment>
			<ActivityHeader title={ __( 'Orders', 'woo-dash' ) } menu={ menu } />
			<Section>
				{ isLoading ? (
					<p>Loading</p>
				) : (
					data.map( ( order, i ) => {
						// We want the billing address, but shipping can be used as a fallback.
						const address = { ...order.shipping, ...order.billing };
						const name = `${ address.first_name } ${ address.last_name }`;
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
								label={ __( 'Order', 'woo-dash' ) }
								icon={ <Dashicon icon="format-aside" /> }
								date={ order.date_created }
							>
								<div>{ sprintf( __( '%s placed order #%d', 'woo-dash' ), name, order.id ) }</div>
								<div>
									<span>
										{ sprintf(
											_n( '%d product', '%d products', productsCount, 'woo-dash' ),
											productsCount
										) }
									</span>{' '}
									{ refundValue ? (
										<span>
											<s>{ formatCurrency( total, order.currency ) }</s>{' '}
											{ formatCurrency( remainingTotal, order.currency ) }
										</span>
									) : (
										<span>{ formatCurrency( total, order.currency ) }</span>
									) }
								</div>
							</ActivityCard>
						);
					} )
				) }
			</Section>
		</Fragment>
	);
}

OrdersPanel.propTypes = {
	orders: PropTypes.object.isRequired,
};

export default compose( [
	withAPIData( () => ( {
		orders: '/wc/v3/orders',
	} ) ),
] )( OrdersPanel );
