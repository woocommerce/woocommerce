/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CartLineItemRow from './cart-line-item-row';

const placeholderRows = [ ...Array( 3 ) ].map( ( _x, i ) => (
	<CartLineItemRow key={ i } />
) );

const CartLineItemsTable = ( { lineItems = [], isLoading = false } ) => {
	const products = isLoading
		? placeholderRows
		: lineItems.map( ( lineItem ) => {
				return (
					<CartLineItemRow
						key={ lineItem.key }
						lineItem={ lineItem }
					/>
				);
		  } );

	return (
		<table className="wc-block-cart-items">
			<thead>
				<tr className="wc-block-cart-items__header">
					<th className="wc-block-cart-items__header-image">
						<span>
							{ __( 'Product', 'woocommerce' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-product">
						<span>
							{ __( 'Details', 'woocommerce' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-quantity">
						<span>
							{ __( 'Quantity', 'woocommerce' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-total">
						<span>
							{ __( 'Total', 'woocommerce' ) }
						</span>
					</th>
				</tr>
			</thead>
			<tbody>{ products }</tbody>
		</table>
	);
};

CartLineItemsTable.propTypes = {
	lineItems: PropTypes.arrayOf(
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
		} )
	),
	isLoading: PropTypes.bool,
};

export default CartLineItemsTable;
