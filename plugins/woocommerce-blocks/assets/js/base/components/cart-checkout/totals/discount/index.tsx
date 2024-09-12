/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { RemovableChip, TotalsItem } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import {
	CartResponseCouponItemWithLabel,
	CartTotalsItem,
	Currency,
	LooselyMustHave,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

export interface TotalsDiscountProps {
	cartCoupons: LooselyMustHave<
		CartResponseCouponItemWithLabel,
		'code' | 'label' | 'totals'
	>[];
	currency: Currency;
	isRemovingCoupon: boolean;
	removeCoupon: ( couponCode: string ) => void;
	values: LooselyMustHave<
		CartTotalsItem,
		'total_discount' | 'total_discount_tax'
	>;
}

const filteredCartCouponsFilterArg = {
	context: 'summary',
};

const TotalsDiscount = ( {
	cartCoupons = [],
	currency,
	isRemovingCoupon,
	removeCoupon,
	values,
}: TotalsDiscountProps ): JSX.Element | null => {
	const {
		total_discount: totalDiscount,
		total_discount_tax: totalDiscountTax,
	} = values;
	const discountValue = parseInt( totalDiscount, 10 );

	const filteredCartCoupons = applyCheckoutFilter( {
		arg: filteredCartCouponsFilterArg,
		filterName: 'coupons',
		defaultValue: cartCoupons,
	} );

	if ( ! discountValue && filteredCartCoupons.length === 0 ) {
		return null;
	}

	const discountTaxValue = parseInt( totalDiscountTax, 10 );
	const discountTotalValue = getSetting(
		'displayCartPricesIncludingTax',
		false
	)
		? discountValue + discountTaxValue
		: discountValue;

	return (
		<TotalsItem
			className="wc-block-components-totals-discount"
			currency={ currency }
			description={
				filteredCartCoupons.length !== 0 && (
					<LoadingMask
						screenReaderLabel={ __(
							'Removing coupon…',
							'woocommerce'
						) }
						isLoading={ isRemovingCoupon }
						showSpinner={ false }
					>
						<ul className="wc-block-components-totals-discount__coupon-list">
							{ filteredCartCoupons.map( ( cartCoupon ) => {
								return (
									<RemovableChip
										key={ 'coupon-' + cartCoupon.code }
										className="wc-block-components-totals-discount__coupon-list-item"
										text={ cartCoupon.label }
										screenReaderText={ sprintf(
											/* translators: %s Coupon code. */
											__( 'Coupon: %s', 'woocommerce' ),
											cartCoupon.label
										) }
										disabled={ isRemovingCoupon }
										onRemove={ () => {
											removeCoupon( cartCoupon.code );
										} }
										radius="large"
										ariaLabel={ sprintf(
											/* translators: %s is a coupon code. */
											__(
												'Remove coupon "%s"',
												'woocommerce'
											),
											cartCoupon.label
										) }
									/>
								);
							} ) }
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

export default TotalsDiscount;
