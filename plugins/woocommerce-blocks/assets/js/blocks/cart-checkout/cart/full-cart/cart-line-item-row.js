/**
 * External dependencies
 */
import { RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import QuantitySelector from '@woocommerce/base-components/quantity-selector';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { getCurrency, formatPrice } from '@woocommerce/base-utils';
import { useStoreCartItemQuantity } from '@woocommerce/base-hooks';
import { Icon, trash } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ProductVariationData from './product-variation-data';
import ProductImage from './product-image';
import ProductLowStockBadge from './product-low-stock-badge';

/**
 * @typedef {import('@woocommerce/type-defs/cart').CartItem} CartItem
 */

/**
 *
 * @param {boolean}     backOrdersAllowed Whether to allow backorders or not.
 * @param {number|null} lowStockAmount    If present the number of stock
 *                                        remaining.
 *
 * @return {number} The maximum number value for the quantity input.
 */
const getMaximumQuantity = ( backOrdersAllowed, lowStockAmount ) => {
	const maxQuantityLimit = getSetting( 'quantitySelectLimit', 99 );
	if ( backOrdersAllowed || ! lowStockAmount ) {
		return maxQuantityLimit;
	}
	return Math.min( lowStockAmount, maxQuantityLimit );
};

/**
 * Cart line item table row component.
 */
const CartLineItemRow = ( { lineItem } ) => {
	const {
		name,
		summary,
		low_stock_remaining: lowStockRemaining = null,
		backorders_allowed: backOrdersAllowed,
		permalink,
		images,
		variation,
		prices,
	} = lineItem;

	const {
		quantity,
		changeQuantity,
		removeItem,
		isPending: itemQuantityDisabled,
	} = useStoreCartItemQuantity( lineItem );

	const currency = getCurrency( prices );
	const regularPrice = parseInt( prices.regular_price, 10 ) * quantity;
	const purchasePrice = parseInt( prices.price, 10 ) * quantity;
	const saleAmount = regularPrice - purchasePrice;

	return (
		<tr className="wc-block-cart-items__row">
			<td className="wc-block-cart-item__image">
				<a href={ permalink }>
					<ProductImage image={ images.length ? images[ 0 ] : {} } />
				</a>
			</td>
			<td className="wc-block-cart-item__product">
				<a
					className="wc-block-cart-item__product-name"
					href={ permalink }
				>
					{ name }
				</a>
				<ProductLowStockBadge lowStockRemaining={ lowStockRemaining } />
				<div className="wc-block-cart-item__product-metadata">
					<RawHTML>{ summary }</RawHTML>
					<ProductVariationData variation={ variation } />
				</div>
			</td>
			<td className="wc-block-cart-item__quantity">
				<QuantitySelector
					disabled={ itemQuantityDisabled }
					quantity={ quantity }
					maximum={ getMaximumQuantity(
						backOrdersAllowed,
						lowStockRemaining
					) }
					onChange={ changeQuantity }
					itemName={ name }
				/>
				<button
					disabled={ itemQuantityDisabled }
					className="wc-block-cart-item__remove-link"
					onClick={ removeItem }
				>
					{ __( 'Remove item', 'woo-gutenberg-products-block' ) }
				</button>
				<button
					disabled={ itemQuantityDisabled }
					className="wc-block-cart-item__remove-icon"
					onClick={ removeItem }
				>
					<Icon srcElement={ trash } />
				</button>
			</td>
			<td className="wc-block-cart-item__total">
				{ saleAmount > 0 && (
					<FormattedMonetaryAmount
						className="wc-block-cart-item__regular-price"
						currency={ currency }
						value={ regularPrice }
					/>
				) }
				<FormattedMonetaryAmount
					className="wc-block-cart-item__price"
					currency={ currency }
					value={ purchasePrice }
				/>
				{ saleAmount > 0 && (
					<div className="wc-block-cart-item__sale-badge">
						{ sprintf(
							/* translators: %s discount amount */
							__( 'Save %s!', 'woo-gutenberg-products-block' ),
							formatPrice( saleAmount, currency )
						) }
					</div>
				) }
			</td>
		</tr>
	);
};

CartLineItemRow.propTypes = {
	lineItem: PropTypes.object.isRequired,
};

export default CartLineItemRow;
