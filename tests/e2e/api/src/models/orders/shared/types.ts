/**
 * An order's status.
 *
 * @typedef OrderStatus
 */
export type OrderStatus = 'pending' | 'processing' | 'complete' | 'on-hold' | 'refunded'
	| 'cancelled' | 'failed' | 'trash' | string;

/**
 * An fee's tax status.
 *
 * @typedef TaxStatus
 */
export type TaxStatus = 'taxable' | 'none';

/**
 * Base order properties
 */
export type OrderDataUpdateParams = 'id' | 'parentId' | 'status' | 'currency' | 'version'
	| 'pricesIncludeTax' | 'discountTotal' | 'discountTax' | 'shippingTotal' | 'shippingTax'
	| 'cartTax' | 'customerId' | 'orderKey' | 'paymentMethod' | 'paymentMethodTitle'
	| 'transactionId' | 'customerIpAddress' | 'customerUserAgent' | 'createdVia' | 'datePaid'
	| 'customerNote' | 'dateCompleted' | 'cartHash' | 'orderNumber' | 'currencySymbol';

/**
 * Common total properties
 */
export type OrderTotalUpdateParams = 'total' | 'totalTax';

/**
 * Order address properties
 */
export type OrderAddressUpdateParams = 'firstName' | 'lastName' | 'companyName' | 'address1'
	| 'address2' | 'city' | 'state' | 'postCode' | 'countryCode' | 'email' | 'phone';

/**
 * Line item properties
 */
export type OrderLineItemUpdateParams = 'name' | 'ProductId' | 'variationId' | 'quantity'
	| 'taxClass' | 'subtotal' | 'subtotalTax' | 'sku' | 'price' | 'parentName';

/**
 * Tax rate properties
 */
export type OrderTaxUpdateParams = 'rateCode' | 'rateId' | 'label' | 'compoundRate'
	| 'taxTotal' | 'shippingTaxTotal' | 'ratePercent';

/**
 * Order shipping properties
 */
export type OrderShippingUpdateParams = 'methodTitle' | 'methodId' | 'instanceId';

/**
 * Order fee properties
 */
export type OrderFeeUpdateParams = 'name' | 'taxClass' | 'taxStatus' | 'amount';

/**
 * Order coupon properties
 */
export type OrderCouponUpdateParams = 'code' | 'discount' | 'discountTax';

/**
 * Order refund properties
 */
export type OrderRefundUpdateParams = 'reason' | 'total';
