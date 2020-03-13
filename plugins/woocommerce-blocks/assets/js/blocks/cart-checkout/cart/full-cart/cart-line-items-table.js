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
	<CartLineItemRow
		key={ i }
		lineItem={ {
			key: '1',
			id: 1,
			quantity: 2,
			name: '',
			summary: '',
			short_description: '',
			description: '',
			sku: '',
			low_stock_remaining: null,
			backorders_allowed: false,
			sold_individually: false,
			permalink: '',
			images: [],
			variation: [],
			prices: {
				currency_code: 'USD',
				currency_minor_unit: 2,
				currency_symbol: '$',
				currency_prefix: '$',
				currency_suffix: '',
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				price: '0',
				regular_price: '0',
				sale_price: '0',
				price_range: null,
			},
			totals: {
				currency_code: 'USD',
				currency_minor_unit: 2,
				currency_symbol: '$',
				currency_prefix: '$',
				currency_suffix: '',
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				line_subtotal: '0',
				line_subtotal_tax: '0',
				line_total: '0',
				line_total_tax: '0',
			},
		} }
	/>
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
							{ __( 'Product', 'woo-gutenberg-products-block' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-product">
						<span>
							{ __( 'Details', 'woo-gutenberg-products-block' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-quantity">
						<span>
							{ __( 'Quantity', 'woo-gutenberg-products-block' ) }
						</span>
					</th>
					<th className="wc-block-cart-items__header-total">
						<span>
							{ __( 'Total', 'woo-gutenberg-products-block' ) }
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
