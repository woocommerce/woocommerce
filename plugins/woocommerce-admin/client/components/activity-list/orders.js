/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { compose } from '@wordpress/element';
import { Dashicon, withAPIData } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ActivityCard from 'components/activity-card';
import { getCurrencyFormatDecimal, getCurrencyFormatString } from 'lib/currency';
import { getOrderRefundTotal } from 'lib/order-values';
import { Section } from 'layout/section';

function OrdersList( { orders } ) {
	const { data = [], isLoading } = orders;

	return (
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
										<s>{ getCurrencyFormatString( total ) }</s>{' '}
										{ getCurrencyFormatString( remainingTotal ) }
									</span>
								) : (
									<span>{ getCurrencyFormatString( total ) }</span>
								) }
							</div>
						</ActivityCard>
					);
				} )
			) }
		</Section>
	);
}

OrdersList.propTypes = {
	orders: PropTypes.object.isRequired,
};

export default compose( [
	withAPIData( () => ( {
		orders: '/wc/v3/orders',
	} ) ),
] )( OrdersList );
