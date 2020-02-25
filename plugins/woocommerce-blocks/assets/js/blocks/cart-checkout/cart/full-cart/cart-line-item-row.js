/**
 * External dependencies
 */
import { useState, RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import QuantitySelector from '@woocommerce/base-components/quantity-selector';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { getCurrency, formatPrice } from '@woocommerce/base-utils';
import { Icon, trash } from '@woocommerce/icons';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Return a currency value as a number for doing calculations.
 * Note this doesn't convert into dollars, currency values are in minor units (e.g. cents).
 *
 * @param {string} currencyValue Currency value string (in minor unit).
 * @return {number} The currency value as int (in minor unit).
 */
const getPriceNumber = ( currencyValue ) => {
	return parseInt( currencyValue, 10 );
};

const ProductVariationDetails = ( { variation } ) => {
	const variationsText = variation
		.map( ( v ) => {
			if ( v.attribute ) {
				return `${ decodeEntities( v.attribute ) }: ${ decodeEntities(
					v.value
				) }`;
			}
			// Support for product attributes with no name/key
			return `${ decodeEntities( v.value ) }`;
		} )
		.join( ' / ' );

	return (
		<div className="wc-block-cart-item__product-attributes">
			{ variationsText }
		</div>
	);
};

const CartLineItemRow = ( { lineItem } ) => {
	const {
		name,
		summary,
		permalink,
		images,
		variation,
		quantity,
		low_stock_remaining: lowStockRemaining,
		prices,
	} = lineItem;

	const imageProps = {};
	if ( images && images.length ) {
		imageProps.src = lineItem.images[ 0 ].src || '';
		imageProps.alt = lineItem.images[ 0 ].alt || '';
		imageProps.srcSet = lineItem.images[ 0 ].srcset || '';
		imageProps.sizes = lineItem.images[ 0 ].sizes || '';
	}

	const [ lineQuantity, setLineQuantity ] = useState( quantity );
	const currency = getCurrency();

	const lineFullPrice = getPriceNumber( prices.regular_price ) * lineQuantity;
	const purchasePrice = getPriceNumber( prices.price ) * lineQuantity;
	const saleDiscountAmount = lineFullPrice - purchasePrice;

	let fullPrice = null,
		saleBadge = null;
	if ( saleDiscountAmount > 0 ) {
		fullPrice = (
			<div className="wc-block-cart-item__full-price">
				<FormattedMonetaryAmount
					currency={ currency }
					value={ lineFullPrice }
				/>
			</div>
		);
		saleBadge = (
			<div className="wc-block-cart-item__sale-badge">
				{ sprintf(
					/* translators: %s discount amount */
					__( 'Save %s!', 'woo-gutenberg-products-block' ),
					formatPrice( saleDiscountAmount, currency )
				) }
			</div>
		);
	}

	// We use this in two places so we can stack the quantity selector under
	// product info on smaller screens.
	const quantitySelector = ( className ) => {
		return (
			<QuantitySelector
				className={ className }
				quantity={ lineQuantity }
				onChange={ setLineQuantity }
				itemName={ name }
			/>
		);
	};

	const lowStockBadge = lowStockRemaining ? (
		<div className="wc-block-cart-item__low-stock-badge">
			{ sprintf(
				/* translators: %s stock amount (number of items in stock for product) */
				__( '%s left in stock', 'woo-gutenberg-products-block' ),
				lowStockRemaining
			) }
		</div>
	) : null;

	return (
		<tr className="wc-block-cart-items__row">
			<td className="wc-block-cart-item__image">
				<a href={ permalink }>
					<img
						{ ...imageProps }
						alt={ decodeEntities( imageProps.alt ) }
					/>
				</a>
			</td>
			<td className="wc-block-cart-item__product">
				<a
					className="wc-block-cart-item__product-name"
					href={ permalink }
				>
					{ name }
				</a>
				{ lowStockBadge }
				<div className="wc-block-cart-item__product-metadata">
					<RawHTML>{ summary }</RawHTML>
					<ProductVariationDetails variation={ variation } />
				</div>
			</td>
			<td className="wc-block-cart-item__quantity">
				{ quantitySelector() }
				<button className="wc-block-cart-item__remove-link">
					{ __( 'Remove item', 'woo-gutenberg-products-block' ) }
				</button>
				<button className="wc-block-cart-item__remove-icon">
					<Icon srcElement={ trash } />
				</button>
			</td>
			<td className="wc-block-cart-item__total">
				{ fullPrice }
				<div className="wc-block-cart-item__line-total">
					<FormattedMonetaryAmount
						currency={ currency }
						value={ purchasePrice }
					/>
				</div>
				{ saleBadge }
			</td>
		</tr>
	);
};

CartLineItemRow.propTypes = {
	lineItem: PropTypes.shape( {
		name: PropTypes.string.isRequired,
		summary: PropTypes.string.isRequired,
		images: PropTypes.array.isRequired,
		quantity: PropTypes.number.isRequired,
		low_stock_remaining: PropTypes.number,
		variation: PropTypes.arrayOf(
			PropTypes.shape( {
				attribute: PropTypes.string.isRequired,
				value: PropTypes.string.isRequired,
			} )
		).isRequired,
		totals: PropTypes.shape( {
			line_subtotal: PropTypes.string.isRequired,
			line_total: PropTypes.string.isRequired,
		} ).isRequired,
		prices: PropTypes.shape( {
			price: PropTypes.string.isRequired,
			regular_price: PropTypes.string.isRequired,
		} ).isRequired,
	} ),
};

export default CartLineItemRow;
