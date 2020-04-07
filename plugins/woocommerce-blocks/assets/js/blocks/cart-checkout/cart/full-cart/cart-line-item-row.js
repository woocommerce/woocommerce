/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import QuantitySelector from '@woocommerce/base-components/quantity-selector';
import { getCurrency } from '@woocommerce/base-utils';
import { useStoreCartItemQuantity } from '@woocommerce/base-hooks';
import { Icon, trash } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import {
	ProductImage,
	ProductLowStockBadge,
	ProductMetadata,
	ProductName,
	ProductPrice,
	ProductSaleBadge,
} from '@woocommerce/base-components/cart-checkout';
import Dinero from 'dinero.js';

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
 * Convert a Dinero object with precision to store currency minor unit.
 *
 * @param {Dinero} priceObject Price object to convert.
 * @param {Object} currency    Currency data.
 * @return {number} Amount with new minor unit precision.
 */
const getAmountFromRawPrice = ( priceObject, currency ) => {
	return priceObject.convertPrecision( currency.minorUnit ).getAmount();
};

/**
 * Cart line item table row component.
 */
const CartLineItemRow = ( { lineItem } ) => {
	const {
		name = '',
		summary = '',
		low_stock_remaining: lowStockRemaining = null,
		backorders_allowed: backOrdersAllowed = false,
		permalink = '',
		images = [],
		variation = [],
		prices = {
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
			raw_prices: {
				precision: 6,
				price: '0',
				regular_price: '0',
				sale_price: '0',
			},
		},
	} = lineItem;

	const {
		quantity,
		changeQuantity,
		removeItem,
		isPendingDelete,
	} = useStoreCartItemQuantity( lineItem );

	const currency = getCurrency( prices );
	const regularAmount = Dinero( {
		amount: parseInt( prices.raw_prices.regular_price, 10 ),
		precision: parseInt( prices.raw_prices.precision, 10 ),
	} ).multiply( quantity );
	const purchaseAmount = Dinero( {
		amount: parseInt( prices.raw_prices.price, 10 ),
		precision: parseInt( prices.raw_prices.precision, 10 ),
	} ).multiply( quantity );
	const saleAmount = regularAmount.subtract( purchaseAmount );
	const firstImage = images.length ? images[ 0 ] : {};

	return (
		<tr
			className={ classnames( 'wc-block-cart-items__row', {
				'is-disabled': isPendingDelete,
			} ) }
		>
			{ /* If the image has no alt text, this link is unnecessary and can be hidden. */ }
			<td
				className="wc-block-cart-item__image"
				aria-hidden={ ! firstImage.alt }
			>
				{ /* We don't need to make it focusable, because product name has the same link. */ }
				<a href={ permalink } tabIndex={ -1 }>
					<ProductImage image={ firstImage } />
				</a>
			</td>
			<td className="wc-block-cart-item__product">
				<ProductName
					permalink={ permalink }
					name={ name }
					disabled={ isPendingDelete }
				/>
				<ProductLowStockBadge lowStockRemaining={ lowStockRemaining } />
				<ProductMetadata summary={ summary } variation={ variation } />
			</td>
			<td className="wc-block-cart-item__quantity">
				<QuantitySelector
					disabled={ isPendingDelete }
					quantity={ quantity }
					maximum={ getMaximumQuantity(
						backOrdersAllowed,
						lowStockRemaining
					) }
					onChange={ changeQuantity }
					itemName={ name }
				/>
				<button
					className="wc-block-cart-item__remove-link"
					onClick={ removeItem }
					disabled={ isPendingDelete }
				>
					{ __( 'Remove item', 'woo-gutenberg-products-block' ) }
				</button>
				<button
					className="wc-block-cart-item__remove-icon"
					onClick={ removeItem }
				>
					<Icon srcElement={ trash } />
				</button>
			</td>
			<td className="wc-block-cart-item__total">
				<ProductPrice
					currency={ currency }
					regularValue={ getAmountFromRawPrice(
						regularAmount,
						currency
					) }
					value={ getAmountFromRawPrice( purchaseAmount, currency ) }
				/>
				<ProductSaleBadge
					currency={ currency }
					saleAmount={ getAmountFromRawPrice( saleAmount, currency ) }
				/>
			</td>
		</tr>
	);
};

CartLineItemRow.propTypes = {
	lineItem: PropTypes.object,
};

export default CartLineItemRow;
