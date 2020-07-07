/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import Chip from '@woocommerce/base-components/chip';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';

const TotalsDiscountItem = ( {
	cartCoupons = [],
	currency,
	isRemovingCoupon,
	removeCoupon,
	values,
} ) => {
	const {
		total_discount: totalDiscount,
		total_discount_tax: totalDiscountTax,
	} = values;
	const discountValue = parseInt( totalDiscount, 10 );

	if ( ! discountValue && cartCoupons.length === 0 ) {
		return null;
	}

	const discountTaxValue = parseInt( totalDiscountTax, 10 );
	const discountTotalValue = DISPLAY_CART_PRICES_INCLUDING_TAX
		? discountValue + discountTaxValue
		: discountValue;

	return (
		<TotalsItem
			currency={ currency }
			description={
				cartCoupons.length !== 0 && (
					<LoadingMask
						screenReaderLabel={ __(
							'Removing coupon…',
							'woocommerce'
						) }
						isLoading={ isRemovingCoupon }
						showSpinner={ false }
					>
						<ul className="wc-block-cart-coupon-list">
							{ cartCoupons.map( ( cartCoupon ) => (
								<Chip
									key={ 'coupon-' + cartCoupon.code }
									className="wc-block-cart-coupon-list__item"
									text={ cartCoupon.code }
									screenReaderText={ sprintf(
										/* Translators: %s Coupon code. */
										__(
											'Coupon: %s',
											'woocommerce'
										),
										cartCoupon.code
									) }
									disabled={ isRemovingCoupon }
									onRemove={ () => {
										removeCoupon( cartCoupon.code );
									} }
									radius="large"
								/>
							) ) }
						</ul>
					</LoadingMask>
				)
			}
			label={
				!! discountTotalValue
					? __( 'Discount', 'woocommerce' )
					: __( 'Coupons', 'woocommerce' )
			}
			value={ discountTotalValue ? discountTotalValue * -1 : '-' }
		/>
	);
};

TotalsDiscountItem.propTypes = {
	cartCoupons: PropTypes.arrayOf(
		PropTypes.shape( {
			code: PropTypes.string.isRequired,
		} )
	),
	currency: PropTypes.object.isRequired,
	isRemovingCoupon: PropTypes.bool.isRequired,
	removeCoupon: PropTypes.func.isRequired,
	values: PropTypes.shape( {
		total_discount: PropTypes.string,
		total_discount_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsDiscountItem;
