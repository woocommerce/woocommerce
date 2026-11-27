/**
 * External dependencies
 */
import clsx from 'clsx';
import { sprintf, _n } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';
import { Label } from '@woocommerce/blocks-components';
import ProductPrice from '@woocommerce/base-components/product-price';
import ProductName from '@woocommerce/base-components/product-name';
import {
	getCurrencyFromPriceResponse,
	formatPrice,
} from '@woocommerce/price-format';
import {
	applyCheckoutFilter,
	productPriceScreenReaderValidation,
	productPriceValidation,
} from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import { useMemo } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { CartItem } from '@woocommerce/types';
import { calculateLineSaleAmount } from '@woocommerce/base-utils';
import { dinero, toSnapshot } from 'dinero.js';
import { USD } from 'dinero.js/currencies'; // USD is used as a placeholder currency for arithmetic; actual formatting is handled elsewhere.

/**
 * Internal dependencies
 */
import ProductBackorderBadge from '../product-backorder-badge';
import ProductImage from '../product-image';
import ProductMetadata from '../product-metadata';
import ProductSaleBadge from '../product-sale-badge';

interface OrderSummaryProps {
	cartItem: CartItem;
	disableProductDescriptions: boolean;
}

const OrderSummaryItem = ( {
	cartItem,
	disableProductDescriptions,
}: OrderSummaryProps ): JSX.Element => {
	const {
		images,
		show_backorder_badge: showBackorderBadge,
		name: initialName,
		permalink,
		prices,
		quantity,
		short_description: shortDescription,
		description: fullDescription,
		item_data: itemData,
		variation,
		totals,
		extensions,
	} = cartItem;

	// Prepare props to pass to the applyCheckoutFilter filter.
	// We need to pluck out receiveCart.

	const { receiveCart, ...cart } = useStoreCart();

	const arg = useMemo(
		() => ( {
			context: 'summary',
			cartItem,
			cart,
		} ),
		[ cartItem, cart ]
	);

	const priceCurrency = getCurrencyFromPriceResponse( prices );

	const name = applyCheckoutFilter( {
		filterName: 'itemName',
		defaultValue: initialName,
		extensions,
		arg,
	} );

	// Use the prices the server rounded, so the summary shows the same unit price as the product page and the cart.
	const regularPriceSingle = parseInt( prices.regular_price, 10 );
	const priceSingle = parseInt( prices.price, 10 );
	const totalsCurrency = getCurrencyFromPriceResponse( totals );

	const includeTax = getSetting< boolean >(
		'displayCartPricesIncludingTax',
		false
	);
	let lineSubtotal = parseInt( totals.line_subtotal, 10 );
	if ( includeTax ) {
		lineSubtotal += parseInt( totals.line_subtotal_tax, 10 );
	}
	const subtotalPrice = toSnapshot(
		dinero( {
			amount: lineSubtotal,
			currency: USD,
			scale: totalsCurrency.minorUnit,
		} )
	).amount;

	const lineSaleAmount = calculateLineSaleAmount(
		cartItem,
		totalsCurrency.minorUnit,
		includeTax
	);

	const subtotalPriceFormat = applyCheckoutFilter( {
		filterName: 'subtotalPriceFormat',
		defaultValue: '<price/>',
		extensions,
		arg,
		validation: productPriceValidation,
	} );

	const saleBadgePriceFormat = applyCheckoutFilter( {
		filterName: 'saleBadgePriceFormat',
		defaultValue: '<price/>',
		extensions,
		arg,
		validation: productPriceValidation,
	} );

	// Allow extensions to filter how the price is displayed. Ie: prepending or appending some values.
	const productPriceFormat = applyCheckoutFilter( {
		filterName: 'cartItemPrice',
		defaultValue: '<price/>',
		extensions,
		arg,
		validation: productPriceValidation,
	} );

	/* translators: <quantity/>, <productName/> and <price/> are placeholders and should not be translated. */
	const productPriceScreenReaderDefault = _n(
		'Total price for <quantity/> <productName/> item: <price/>',
		'Total price for <quantity/> <productName/> items: <price/>',
		quantity,
		'woocommerce'
	);

	const productPriceScreenReaderFormat = applyCheckoutFilter( {
		filterName: 'cartItemScreenReaderPrice',
		defaultValue: productPriceScreenReaderDefault,
		extensions,
		arg,
		validation: productPriceScreenReaderValidation,
	} );

	// Build as one string (not React nodes) so screen readers announce the
	// full sentence as a single unit rather than separate sibling text nodes.
	const productPriceScreenReaderText = productPriceScreenReaderFormat.replace(
		/<(quantity|productName|price)\/>/g,
		( _match, key ) => {
			switch ( key ) {
				case 'quantity':
					return String( quantity );
				case 'productName':
					// `name` is HTML; stripHTML removes tags and decodes entities once, matching the visible name.
					return stripHTML( name );
				case 'price':
					return formatPrice( subtotalPrice, totalsCurrency );
			}
			return '';
		}
	);

	const cartItemClassNameFilter = applyCheckoutFilter( {
		filterName: 'cartItemClass',
		defaultValue: '',
		extensions,
		arg,
	} );

	const productMetaProps = disableProductDescriptions
		? {
				itemData,
				variation,
			}
		: {
				itemData,
				variation,
				shortDescription,
				fullDescription,
			};

	return (
		<div
			className={ clsx(
				'wc-block-components-order-summary-item',
				cartItemClassNameFilter
			) }
		>
			<div className="wc-block-components-order-summary-item__image">
				<div className="wc-block-components-order-summary-item__quantity">
					<Label
						label={ quantity.toString() }
						screenReaderLabel={ sprintf(
							/* translators: %d number of products of the same type in the cart */
							_n(
								'%d item',
								'%d items',
								quantity,
								'woocommerce'
							),
							quantity
						) }
					/>
				</div>
				<ProductImage
					image={ images.length ? images[ 0 ] : {} }
					fallbackAlt={ name }
					width={ 48 }
					height={ 48 }
				/>
			</div>
			<div className="wc-block-components-order-summary-item__description">
				<ProductName
					disabled={ true }
					name={ name }
					permalink={ permalink }
					disabledTagName="h3"
				/>
				<div className="wc-block-cart-item__prices">
					<ProductPrice
						currency={ priceCurrency }
						price={ priceSingle }
						regularPrice={ regularPriceSingle }
						className="wc-block-components-order-summary-item__individual-prices"
						priceClassName="wc-block-components-order-summary-item__individual-price"
						regularPriceClassName="wc-block-components-order-summary-item__regular-individual-price"
						format={ subtotalPriceFormat }
					/>
				</div>
				{ showBackorderBadge && <ProductBackorderBadge /> }
				<ProductMetadata { ...productMetaProps } />
			</div>
			<span className="screen-reader-text">
				{ productPriceScreenReaderText }
			</span>
			<div
				className="wc-block-components-order-summary-item__total-price"
				aria-hidden="true"
			>
				<div className="wc-block-cart-item__total-price-and-sale-badge-wrapper">
					<ProductPrice
						currency={ totalsCurrency }
						format={ productPriceFormat }
						price={ subtotalPrice }
					/>
					<ProductSaleBadge
						currency={ totalsCurrency }
						saleAmount={ lineSaleAmount }
						format={ saleBadgePriceFormat }
					/>
				</div>
			</div>
		</div>
	);
};

export default OrderSummaryItem;
