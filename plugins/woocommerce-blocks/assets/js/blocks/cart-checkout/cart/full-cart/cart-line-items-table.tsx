/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CartResponseItem } from '@woocommerce/type-defs/cart-response';

/**
 * Internal dependencies
 */
import CartLineItemRow from './cart-line-item-row';

const placeholderRows = [ ...Array( 3 ) ].map( ( _x, i ) => (
	<CartLineItemRow lineItem={ {} } key={ i } />
) );

interface CartLineItemsTableProps {
	lineItems: CartResponseItem[];
	isLoading: boolean;
}

const CartLineItemsTable = ( {
	lineItems = [],
	isLoading = false,
}: CartLineItemsTableProps ): JSX.Element => {
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

export default CartLineItemsTable;
