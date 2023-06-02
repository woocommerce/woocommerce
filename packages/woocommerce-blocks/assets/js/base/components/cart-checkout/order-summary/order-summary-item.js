/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import Label from '@woocommerce/base-components/label';
import ProductPrice from '@woocommerce/base-components/product-price';
import ProductName from '@woocommerce/base-components/product-name';
import { getCurrency } from '@woocommerce/price-format';
import PropTypes from 'prop-types';
import Dinero from 'dinero.js';

/**
 * Internal dependencies
 */
import ProductBackorderBadge from '../product-backorder-badge';
import ProductImage from '../product-image';
import ProductLowStockBadge from '../product-low-stock-badge';
import ProductMetadata from '../product-metadata';

const OrderSummaryItem = ( { cartItem } ) => {
	const {
		images,
		low_stock_remaining: lowStockRemaining = null,
		show_backorder_badge: showBackorderBadge = false,
		name,
		permalink,
		prices,
		quantity,
		short_description: shortDescription,
		description: fullDescription,
		item_data: itemData = [],
		variation,
	} = cartItem;

	const currency = getCurrency( prices );
	const regularPriceSingle = Dinero( {
		amount: parseInt( prices.raw_prices.regular_price, 10 ),
		precision: parseInt( prices.raw_prices.precision, 10 ),
	} )
		.convertPrecision( currency.minorUnit )
		.getAmount();
	const unconvertedLinePrice = Dinero( {
		amount: parseInt( prices.raw_prices.price, 10 ),
		precision: parseInt( prices.raw_prices.precision, 10 ),
	} );
	const linePriceSingle = unconvertedLinePrice
		.convertPrecision( currency.minorUnit )
		.getAmount();
	const linePrice = unconvertedLinePrice
		.multiply( quantity )
		.convertPrecision( currency.minorUnit )
		.getAmount();

	return (
		<div className="wc-block-components-order-summary-item">
			<div className="wc-block-components-order-summary-item__image">
				<div className="wc-block-components-order-summary-item__quantity">
					<Label
						label={ quantity }
						screenReaderLabel={ sprintf(
							/* translators: %d number of products of the same type in the cart */
							__( '%d items', 'woocommerce' ),
							quantity
						) }
					/>
				</div>
				<ProductImage image={ images.length ? images[ 0 ] : {} } />
			</div>
			<div className="wc-block-components-order-summary-item__description">
				<ProductName
					disabled={ true }
					name={ name }
					permalink={ permalink }
				/>
				<ProductPrice
					currency={ currency }
					price={ linePriceSingle }
					regularPrice={ regularPriceSingle }
					className="wc-block-components-order-summary-item__individual-prices"
					priceClassName="wc-block-components-order-summary-item__individual-price"
					regularPriceClassName="wc-block-components-order-summary-item__regular-individual-price"
				/>
				{ showBackorderBadge ? (
					<ProductBackorderBadge />
				) : (
					!! lowStockRemaining && (
						<ProductLowStockBadge
							lowStockRemaining={ lowStockRemaining }
						/>
					)
				) }
				<ProductMetadata
					shortDescription={ shortDescription }
					fullDescription={ fullDescription }
					itemData={ itemData }
					variation={ variation }
				/>
			</div>
			<div className="wc-block-components-order-summary-item__total-price">
				<ProductPrice currency={ currency } price={ linePrice } />
			</div>
		</div>
	);
};

OrderSummaryItem.propTypes = {
	cartItems: PropTypes.shape( {
		images: PropTypes.array,
		low_stock_remaining: PropTypes.number,
		name: PropTypes.string.isRequired,
		permalink: PropTypes.string,
		prices: PropTypes.shape( {
			price: PropTypes.string,
			regular_price: PropTypes.string,
		} ),
		quantity: PropTypes.number,
		summary: PropTypes.string,
		variation: PropTypes.array,
	} ),
};

export default OrderSummaryItem;
