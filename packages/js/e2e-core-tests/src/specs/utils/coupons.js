/**
 * Internal dependencies
 */
const { createCoupon } = require( '@woocommerce/e2e-utils' );

const couponsTable = [
	[ 'fixed cart', { text: '$5.00' }, { text: '$4.99' } ],
	[ 'percentage', { text: '$4.99' }, { text: '$5.00' } ],
	[ 'fixed product', { text: '$5.00' }, { text: '$4.99' } ],
];

let couponFixedCart;
let couponPercentage;
let couponFixedProduct;

/**
 * Get a test coupon Id. Create the coupon if it does not exist.
 *
 * @param {string} couponType Coupon type.
 * @return {string} Coupon code.
 */
const getCouponId = async ( couponType ) => {
	switch ( couponType ) {
		case 'fixed cart':
			if ( ! couponFixedCart ) {
				couponFixedCart = await createCoupon();
			}
			return couponFixedCart;
		case 'percentage':
			if ( ! couponPercentage ) {
				couponPercentage = await createCoupon(
					'50',
					'Percentage discount'
				);
			}
			return couponPercentage;
		case 'fixed product':
			if ( ! couponFixedProduct ) {
				couponFixedProduct = await createCoupon(
					'5',
					'Fixed product discount'
				);
			}
			return couponFixedProduct;
	}
};

const getCouponsTable = () => {
	return couponsTable;
};

module.exports = {
	getCouponsTable,
	getCouponId,
};
