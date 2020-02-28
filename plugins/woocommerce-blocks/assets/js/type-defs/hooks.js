/**
 * @typedef {Object} StoreCart
 *
 * @property {Array}   cartCoupons       An array of coupons applied to the cart.
 * @property {Array}   cartItems         An array of items in the cart.
 * @property {number}  cartItemsCount    The number of items in the cart.
 * @property {number}  cartItemsWeight   The weight of all items in the cart.
 * @property {boolean} cartNeedsShipping True when the cart will require shipping.
 * @property {Object}  cartTotals        Cart and line total amounts.
 * @property {boolean} cartIsLoading     True when cart data is being loaded.
 * @property {Array}   cartErrors        An array of errors thrown by the cart.
 */

/**
 * @typedef {Object} StoreCartCoupon
 *
 * @property {Array}    appliedCoupons   Collection of applied coupons from the
 *                                       API.
 * @property {boolean}  isLoading        True when coupon data is being loaded.
 * @property {Function} applyCoupon      Callback for applying a coupon by code.
 * @property {Function} removeCoupon     Callback for removing a coupon by code.
 * @property {boolean}  isApplyingCoupon True when a coupon is being applied.
 * @property {boolean}  isRemovingCoupon True when a coupon is being removed.
 */

/**
 * @typedef {Object} StoreCartItems
 *
 * @property {boolean}  isLoading             True when cart items are being loaded.
 * @property {Array}    cartItems             An array of items in the cart.
 * @property {Function} isItemQuantityPending Callback for determining if a cart item
 *                                            is currently updating (i.e. remoe / change
 *                                            quantity).
 * @property {Function} removeItemFromCart    Callback for removing a cart item.
 */

 export {};
