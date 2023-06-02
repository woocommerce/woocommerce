/**
 * External dependencies
 */
import { text, boolean } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsDiscount from '../';

export default {
	title:
		'WooCommerce Blocks/@base-components/cart-checkout/totals/TotalsDiscount',
	component: TotalsDiscount,
};

export const Default = () => {
	const cartCoupons = [ { code: 'COUPON' } ];
	const currency = currencyKnob();
	const isRemovingCoupon = boolean( 'Toggle isRemovingCoupon state', false );
	const totalDiscount = text( 'Total discount', '1000' );
	const totalDiscountTax = text( 'Total discount tax', '200' );

	return (
		<TotalsDiscount
			cartCoupons={ cartCoupons }
			currency={ currency }
			isRemovingCoupon={ isRemovingCoupon }
			removeCoupon={ () => void null }
			values={ {
				total_discount: totalDiscount,
				total_discount_tax: totalDiscountTax,
			} }
		/>
	);
};
