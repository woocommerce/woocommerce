/**
 * An order's status.
 *
 * @typedef OrderStatus
 */
export declare type OrderStatus = 'pending' | 'processing' | 'complete' | 'on-hold' | 'refunded' | 'cancelled' | 'failed' | 'trash' | string;
/**
 * An fee's tax status.
 *
 * @typedef TaxStatus
 */
export declare type TaxStatus = 'taxable' | 'none';
/**
 * Base order properties
 */
export declare type OrderDataUpdateParams = 'id' | 'parentId' | 'status' | 'currency' | 'version' | 'pricesIncludeTax' | 'discountTotal' | 'discountTax' | 'shippingTotal' | 'shippingTax' | 'cartTax' | 'customerId' | 'orderKey' | 'paymentMethod' | 'paymentMethodTitle' | 'transactionId' | 'customerIpAddress' | 'customerUserAgent' | 'createdVia' | 'datePaid' | 'customerNote' | 'dateCompleted' | 'cartHash' | 'orderNumber' | 'currencySymbol';
/**
 * Common total properties
 */
export declare type OrderTotalUpdateParams = 'total' | 'totalTax';
/**
 * Order address properties
 */
export declare type OrderAddressUpdateParams = 'firstName' | 'lastName' | 'companyName' | 'address1' | 'address2' | 'city' | 'state' | 'postCode' | 'countryCode' | 'email' | 'phone';
/**
 * Line item properties
 */
export declare type OrderLineItemUpdateParams = 'name' | 'ProductId' | 'variationId' | 'quantity' | 'taxClass' | 'subtotal' | 'subtotalTax' | 'sku' | 'price' | 'parentName';
/**
 * Tax rate properties
 */
export declare type OrderTaxUpdateParams = 'rateCode' | 'rateId' | 'label' | 'compoundRate' | 'taxTotal' | 'shippingTaxTotal' | 'ratePercent';
/**
 * Order shipping properties
 */
export declare type OrderShippingUpdateParams = 'methodTitle' | 'methodId' | 'instanceId';
/**
 * Order fee properties
 */
export declare type OrderFeeUpdateParams = 'name' | 'taxClass' | 'taxStatus' | 'amount';
/**
 * Order coupon properties
 */
export declare type OrderCouponUpdateParams = 'code' | 'discount' | 'discountTax';
/**
 * Order refund properties
 */
export declare type OrderRefundUpdateParams = 'reason' | 'total';
//# sourceMappingURL=types.d.ts.map