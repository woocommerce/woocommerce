/**
 * @typedef {import('./cart').CartData} CartData
 * @typedef {import('./cart').CartShippingAddress} CartShippingAddress
 */

/**
 * @typedef {Object} StoreCart
 *
 * @property {Array}               cartCoupons       An array of coupons applied
 *                                                   to the cart.
 * @property {Array}               shippingRates     array of selected shipping
 *                                                   rates
 * @property {CartShippingAddress} shippingAddress   Shipping address for the
 *                                                   cart.
 * @property {Array}               cartItems         An array of items in the
 *                                                   cart.
 * @property {number}              cartItemsCount    The number of items in the
 *                                                   cart.
 * @property {number}              cartItemsWeight   The weight of all items in
 *                                                   the cart.
 * @property {boolean}             cartNeedsShipping True when the cart will
 *                                                   require shipping.
 * @property {Array}               cartItemErrors    Item validation errors.
 * @property {Object}              cartTotals        Cart and line total
 *                                                   amounts.
 * @property {boolean}             cartIsLoading     True when cart data is
 *                                                   being loaded.
 * @property {Array}               cartErrors        An array of errors thrown
 *                                                   by the cart.
 */

/**
 * @typedef {Object} StoreCartCoupon
 *
 * @property {Array}    appliedCoupons    Collection of applied coupons from the
 *                                        API.
 * @property {boolean}  isLoading         True when coupon data is being loaded.
 * @property {Function} applyCoupon       Callback for applying a coupon by code.
 * @property {Function} removeCoupon      Callback for removing a coupon by code.
 * @property {boolean}  isApplyingCoupon  True when a coupon is being applied.
 * @property {boolean}  isRemovingCoupon  True when a coupon is being removed.
 */

/**
 * @typedef {Object} StoreCartItemAddToCart
 *
 * @property {number}   cartQuantity           The quantity of the item in the
 *                                             cart.
 * @property {boolean}  addingToCart           Whether the cart item is still
 *                                             being added or not.
 * @property {boolean}  cartIsLoading          Whether the cart is being loaded.
 * @property {Function} addToCart              Callback for adding a cart item.
 */

/**
 * @typedef {Object} StoreCartItemQuantity
 *
 * @property {number}   quantity               The quantity of the item in the
 *                                             cart.
 * @property {boolean}  isPendingDelete        Whether the cart item is being
 *                                             deleted or not.
 * @property {Function} changeQuantity         Callback for changing quantity
 *                                             of item in cart.
 * @property {Function} removeItem             Callback for removing a cart item.
 * @property {Object}   cartItemQuantityErrors An array of errors thrown by
 *                                             the cart.
 */

export {};
