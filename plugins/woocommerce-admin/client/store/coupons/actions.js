/** @format */

export default {
	setCoupons( coupons, query ) {
		return {
			type: 'SET_COUPONS',
			coupons,
			query: query || {},
		};
	},
	setCouponsError( query ) {
		return {
			type: 'SET_COUPONS_ERROR',
			query: query || {},
		};
	},
};
