import { MetaData } from '../../shared-types';
import { Model } from '../../model';
import { TaxStatus } from './types';
/**
 * Order item meta.
 */
export declare class OrderItemMeta extends Model {
    /**
     * The meta data the order item.
     *
     * @type {ReadonlyArray.<MetaData>}
     */
    readonly metaData: MetaData[];
}
/**
 * Order line item tax entry.
 */
export declare class OrderItemTax extends Model {
    /**
     * The total tax for this tax rate on this item.
     *
     * @type {string}
     */
    readonly total: string;
    /**
     * The subtotal tax for this tax rate on this item.
     *
     * @type {string}
     */
    readonly subtotal: string;
}
/**
 * An order address.
 */
export declare class OrderAddress extends Model {
    /**
     * The first name of the person in the address.
     *
     * @type {string}
     */
    readonly firstName: string;
    /**
     * The last name of the person in the address.
     *
     * @type {string}
     */
    readonly lastName: string;
    /**
     * The company name of the person in the address.
     *
     * @type {string}
     */
    readonly companyName: string;
    /**
     * The first address line in the address.
     *
     * @type {string}
     */
    readonly address1: string;
    /**
     * The second address line in the address.
     *
     * @type {string}
     */
    readonly address2: string;
    /**
     * The city in the address.
     *
     * @type {string}
     */
    readonly city: string;
    /**
     * The state in the address.
     *
     * @type {string}
     */
    readonly state: string;
    /**
     * The postal code in the address.
     *
     * @type {string}
     */
    readonly postCode: string;
    /**
     * The country code for the address.
     *
     * @type {string}
     */
    readonly countryCode: string;
    /**
     * The email address of the person in the address.
     *
     * @type {string}
     */
    readonly email: string;
    /**
     * The phone number of the person in the address.
     *
     * @type {string}
     */
    readonly phone: string;
}
/**
 * Order Line Item
 */
export declare class OrderLineItem extends OrderItemMeta {
    /**
     * The name of the product.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The ID of the product.
     *
     * @type {number}
     */
    readonly productId: number;
    /**
     * The ID of the product variation.
     *
     * @type {number}
     */
    readonly variationId: number;
    /**
     * The quantity of the product.
     *
     * @type {number}
     */
    readonly quantity: number;
    /**
     * The tax class for the product.
     *
     * @type {string}
     */
    readonly taxClass: string;
    /**
     * The subtotal for the product.
     *
     * @type {string}
     */
    readonly subtotal: string;
    /**
     * The subtotal tax for the product.
     *
     * @type {string}
     */
    readonly subtotalTax: string;
    /**
     * The total for the product including adjustments.
     *
     * @type {string}
     */
    readonly total: string;
    /**
     * The total tax for the product including adjustments.
     *
     * @type {string}
     */
    readonly totalTax: string;
    /**
     * The taxes applied to the product.
     *
     * @type {ReadonlyArray.<OrderItemTax>}
     */
    readonly taxes: OrderItemTax[];
    /**
     * The product SKU.
     *
     * @type {string}
     */
    readonly sku: string;
    /**
     * The price of the product.
     *
     * @type {number}
     */
    readonly price: number;
    /**
     * The name of the parent product.
     *
     * @type {string|null}
     */
    readonly parentName: string | null;
}
/**
 * Order Tax Rate
 */
export declare class OrderTaxRate extends Model {
    /**
     * The tax rate code.
     *
     * @type {string}
     */
    readonly rateCode: string;
    /**
     * The tax rate id.
     *
     * @type {number}
     */
    readonly rateId: number;
    /**
     * The tax label.
     *
     * @type {string}
     */
    readonly label: string;
    /**
     * Flag indicating whether it's a compound tax rate.
     *
     * @type {boolean}
     */
    readonly compoundRate: boolean;
    /**
     * The total tax for this rate code.
     *
     * @type {string}
     */
    readonly taxTotal: string;
    /**
     * The total shipping tax for this rate code.
     *
     * @type {string}
     */
    readonly shippingTaxTotal: string;
    /**
     * The tax rate as a percentage.
     *
     * @type {number}
     */
    readonly ratePercent: number;
}
/**
 * Order shipping line
 */
export declare class OrderShippingLine extends OrderItemMeta {
    /**
     * The shipping method title.
     *
     * @type {string}
     */
    readonly methodTitle: string;
    /**
     * The shipping method id.
     *
     * @type {string}
     */
    readonly methodId: string;
    /**
     * The shipping method instance id.
     *
     * @type {string}
     */
    readonly instanceId: string;
    /**
     * The total shipping amount for this method.
     *
     * @type {string}
     */
    readonly total: string;
    /**
     * The total tax amount for this shipping method.
     *
     * @type {string}
     */
    readonly totalTax: string;
    /**
     * The taxes applied to this shipping method.
     *
     * @type {ReadonlyArray.<OrderItemTax>}
     */
    readonly taxes: OrderItemTax[];
}
/**
 * Order fee line
 */
export declare class OrderFeeLine extends OrderItemMeta {
    /**
     * The name of the fee.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The tax class of the fee.
     *
     * @type {string}
     */
    readonly taxClass: string;
    /**
     * The tax status of the fee.
     *
     * @type {TaxStatus}
     */
    readonly taxStatus: TaxStatus;
    /**
     * The total amount for this fee.
     *
     * @type {string}
     */
    readonly amount: string;
    /**
     * The display total amount for this fee.
     *
     * @type {string}
     */
    readonly total: string;
    /**
     * The total tax amount for this fee.
     *
     * @type {string}
     */
    readonly totalTax: string;
    /**
     * The taxes applied to this fee.
     *
     * @type {ReadonlyArray.<OrderItemTax>}
     */
    readonly taxes: OrderItemTax[];
}
/**
 * Order coupon line
 */
export declare class OrderCouponLine extends OrderItemMeta {
    /**
     * The coupon code
     *
     * @type {string}
     */
    readonly code: string;
    /**
     * The discount amount.
     *
     * @type {string}
     */
    readonly discount: string;
    /**
     * The discount tax.
     *
     * @type {string}
     */
    readonly discountTax: string;
}
/**
 * Order refund line
 */
export declare class OrderRefundLine extends Model {
    /**
     * The reason for giving the refund.
     *
     * @type {string}
     */
    readonly reason: string;
    /**
     * The total amount of the refund.
     *
     * @type {string}
     */
    readonly total: string;
}
//# sourceMappingURL=classes.d.ts.map