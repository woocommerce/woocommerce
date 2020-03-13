/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getCurrency } from '@woocommerce/base-utils';
import Label from '@woocommerce/base-components/label';
import {
	ProductImage,
	ProductLowStockBadge,
	ProductMetadata,
	ProductName,
	ProductPrice,
} from '@woocommerce/base-components/cart-checkout';
import PropTypes from 'prop-types';

const CheckoutOrderSummaryItem = ( { cartItem } ) => {
	const {
		images,
		low_stock_remaining: lowStockRemaining = null,
		name,
		permalink,
		prices,
		quantity,
		summary,
		variation,
	} = cartItem;

	const currency = getCurrency( prices );
	const regularPrice = parseInt( prices.regular_price, 10 );
	const purchasePrice = parseInt( prices.price, 10 );

	return (
		<div className="wc-block-order-summary-item">
			<div className="wc-block-order-summary-item__image">
				<ProductImage image={ images.length ? images[ 0 ] : {} } />
				<div className="wc-block-order-summary-item__quantity">
					<Label
						label={ quantity }
						screenReaderLabel={ sprintf(
							/* translators: %d number of products of the same type in the cart */
							__( '%d items', 'woo-gutenberg-products-block' ),
							quantity
						) }
					/>
				</div>
			</div>
			<div className="wc-block-order-summary-item__description">
				<div className="wc-block-order-summary-item__header">
					<ProductPrice
						className="wc-block-order-summary-item__total-price"
						currency={ currency }
						value={ purchasePrice * quantity }
					/>
					<ProductName permalink={ permalink } name={ name } />
				</div>
				<div className="wc-block-order-summary-item__prices">
					<ProductPrice
						currency={ currency }
						regularValue={ regularPrice }
						value={ purchasePrice }
					/>
				</div>
				<ProductLowStockBadge lowStockRemaining={ lowStockRemaining } />
				<ProductMetadata summary={ summary } variation={ variation } />
			</div>
		</div>
	);
};

CheckoutOrderSummaryItem.propTypes = {
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

export default CheckoutOrderSummaryItem;
