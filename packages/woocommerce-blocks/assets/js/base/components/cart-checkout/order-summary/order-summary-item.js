/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getCurrency } from '@woocommerce/base-utils';
import Label from '@woocommerce/base-components/label';
import ProductPrice from '@woocommerce/base-components/product-price';
import {
	ProductBackorderBadge,
	ProductImage,
	ProductLowStockBadge,
	ProductMetadata,
	ProductName,
} from '@woocommerce/base-components/cart-checkout';
import PropTypes from 'prop-types';
import Dinero from 'dinero.js';

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
		variation,
	} = cartItem;

	const currency = getCurrency( prices );
	const linePrice = Dinero( {
		amount: parseInt( prices.raw_prices.price, 10 ),
		precision: parseInt( prices.raw_prices.precision, 10 ),
	} )
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
				<div className="wc-block-components-order-summary-item__header">
					<ProductName permalink={ permalink } name={ name } />
					<ProductPrice
						currency={ currency }
						price={ linePrice }
						priceClassName="wc-block-components-order-summary-item__total-price"
					/>
				</div>
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
					variation={ variation }
				/>
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
