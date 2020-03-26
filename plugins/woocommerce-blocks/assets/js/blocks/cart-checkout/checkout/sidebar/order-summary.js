/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, PanelBody, PanelRow } from 'wordpress-components';
import { Icon, cart } from '@woocommerce/icons';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CheckoutOrderSummaryItem from './order-summary-item.js';

const CheckoutOrderSummary = ( { cartItems = [] } ) => {
	return (
		<Card isElevated={ true }>
			<CardBody>
				<PanelBody
					className="wc-block-order-summary"
					title={
						<>
							<Icon
								className="wc-block-order-summary__button-icon"
								srcElement={ cart }
							/>
							<span className="wc-block-order-summary__button-text">
								{ __(
									'Order summary',
									'woo-gutenberg-products-block'
								) }
							</span>
						</>
					}
					initialOpen={ true }
				>
					<PanelRow className="wc-block-order-summary__row">
						{ cartItems.map( ( cartItem ) => {
							return (
								<CheckoutOrderSummaryItem
									key={ cartItem.key }
									cartItem={ cartItem }
								/>
							);
						} ) }
					</PanelRow>
				</PanelBody>
			</CardBody>
		</Card>
	);
};

CheckoutOrderSummary.propTypes = {
	cartItems: PropTypes.arrayOf(
		PropTypes.shape( { key: PropTypes.string.isRequired } )
	),
};

export default CheckoutOrderSummary;
