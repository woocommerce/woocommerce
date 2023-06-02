/** @typedef { import('@woocommerce/type-defs/cart').CartData } CartData */
/** @typedef { import('@woocommerce/type-defs/cart').CartTotals } CartTotals */

/**
 * Retrieves cart data from state.
 *
 * @param {Object} state The current state.
 * @return {CartData} The data to return.
 */
export const getCartData = ( state ) => {
	return state.cartData;
};

/**
 * Retrieves cart totals from state.
 *
 * @param {Object} state The current state.
 * @return {CartTotals} The data to return.
 */
export const getCartTotals = ( state ) => {
	return (
		state.cartData.totals || {
			currency_code: '',
			currency_symbol: '',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '',
			currency_suffix: '',
			total_items: '0',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_price: '0',
			total_tax: '0',
			tax_lines: [],
		}
	);
};

/**
 * Retrieves cart meta from state.
 *
 * @param {Object} state The current state.
 * @return {Object} The data to return.
 */
export const getCartMeta = ( state ) => {
	return (
		state.metaData || {
			applyingCoupon: '',
			removingCoupon: '',
		}
	);
};

/**
 * Retrieves cart errors from state.
 *
 * @param {Object} state The current state.
 * @return {Array} Array of errors.
 */
export const getCartErrors = ( state ) => {
	return state.errors || [];
};

/**
 * Returns true if any coupon is being applied.
 *
 * @param {Object} state The current state.
 * @return {boolean} True if a coupon is being applied.
 */
export const isApplyingCoupon = ( state ) => {
	return !! state.metaData.applyingCoupon;
};

/**
 * Retrieves the coupon code currently being applied.
 *
 * @param {Object} state The current state.
 * @return {string} The data to return.
 */
export const getCouponBeingApplied = ( state ) => {
	return state.metaData.applyingCoupon || '';
};

/**
 * Returns true if any coupon is being removed.
 *
 * @param {Object} state The current state.
 * @return {boolean} True if a coupon is being removed.
 */
export const isRemovingCoupon = ( state ) => {
	return !! state.metaData.removingCoupon;
};

/**
 * Retrieves the coupon code currently being removed.
 *
 * @param {Object} state The current state.
 * @return {string} The data to return.
 */
export const getCouponBeingRemoved = ( state ) => {
	return state.metaData.removingCoupon || '';
};

/**
 * Returns cart item matching specified key.
 *
 * @param {Object} state The current state.
 * @param {string} cartItemKey Key for a cart item.
 * @return {Object} Cart item object, or undefined if not found.
 */
export const getCartItem = ( state, cartItemKey ) => {
	return state.cartData.items.find(
		( cartItem ) => cartItem.key === cartItemKey
	);
};

/**
 * Returns true if the specified cart item quantity is being updated.
 *
 * @param {Object} state The current state.
 * @param {string} cartItemKey Key for a cart item.
 * @return {boolean} True if a item has a pending request to be updated.
 */
export const isItemPendingQuantity = ( state, cartItemKey ) => {
	return state.cartItemsPendingQuantity.includes( cartItemKey );
};

/**
 * Returns true if the specified cart item quantity is being updated.
 *
 * @param {Object} state The current state.
 * @param {string} cartItemKey Key for a cart item.
 * @return {boolean} True if a item has a pending request to be updated.
 */
export const isItemPendingDelete = ( state, cartItemKey ) => {
	return state.cartItemsPendingDelete.includes( cartItemKey );
};
/**
 * Retrieves if the address is being applied for shipping.
 *
 * @param {Object} state The current state.
 * @return {boolean} are shipping rates loading.
 */
export const isCustomerDataUpdating = ( state ) => {
	return !! state.metaData.updatingCustomerData;
};

/**
 * Retrieves if the shipping rate selection is being persisted.
 *
 * @param {Object} state The current state.
 *
 * @return {boolean} True if the shipping rate selection is being persisted to
 *                   the server.
 */
export const isShippingRateBeingSelected = ( state ) => {
	return !! state.metaData.updatingSelectedRate;
};
