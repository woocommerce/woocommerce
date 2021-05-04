import { MetaData } from '../../shared-types';
import { Model } from '../../model';
import { TaxStatus } from './types';

/**
 * Order item meta.
 */
export class OrderItemMeta extends Model {
	/**
	 * The meta data the order item.
	 *
	 * @type {ReadonlyArray.<MetaData>}
	 */
	public readonly metaData: MetaData[] = [];
}

/**
 * Order line item tax entry.
 */
export class OrderItemTax extends Model {
	/**
	 * The total tax for this tax rate on this item.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';

	/**
	 * The subtotal tax for this tax rate on this item.
	 *
	 * @type {string}
	 */
	public readonly subtotal: string = '';
}

/**
 * An order address.
 */
export class OrderAddress extends Model {
	/**
	 * The first name of the person in the address.
	 *
	 * @type {string}
	 */
	public readonly firstName: string = '';

	/**
	 * The last name of the person in the address.
	 *
	 * @type {string}
	 */
	public readonly lastName: string = '';

	/**
	 * The company name of the person in the address.
	 *
	 * @type {string}
	 */
	public readonly companyName: string = '';

	/**
	 * The first address line in the address.
	 *
	 * @type {string}
	 */
	public readonly address1: string = '';

	/**
	 * The second address line in the address.
	 *
	 * @type {string}
	 */
	public readonly address2: string = '';

	/**
	 * The city in the address.
	 *
	 * @type {string}
	 */
	public readonly city: string = '';

	/**
	 * The state in the address.
	 *
	 * @type {string}
	 */
	public readonly state: string = '';

	/**
	 * The postal code in the address.
	 *
	 * @type {string}
	 */
	public readonly postCode: string = '';

	/**
	 * The country code for the address.
	 *
	 * @type {string}
	 */
	public readonly countryCode: string = '';

	/**
	 * The email address of the person in the address.
	 *
	 * @type {string}
	 */
	public readonly email: string = '';

	/**
	 * The phone number of the person in the address.
	 *
	 * @type {string}
	 */
	public readonly phone: string = '';
}

/**
 * Order Line Item
 */
export class OrderLineItem extends OrderItemMeta {
	/**
	 * The name of the product.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The ID of the product.
	 *
	 * @type {number}
	 */
	public readonly productId: number = -1;

	/**
	 * The ID of the product variation.
	 *
	 * @type {number}
	 */
	public readonly variationId: number = 0;

	/**
	 * The quantity of the product.
	 *
	 * @type {number}
	 */
	public readonly quantity: number = -1;

	/**
	 * The tax class for the product.
	 *
	 * @type {string}
	 */
	public readonly taxClass: string = '';

	/**
	 * The subtotal for the product.
	 *
	 * @type {string}
	 */
	public readonly subtotal: string = '';

	/**
	 * The subtotal tax for the product.
	 *
	 * @type {string}
	 */
	public readonly subtotalTax: string = '';

	/**
	 * The total for the product including adjustments.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';

	/**
	 * The total tax for the product including adjustments.
	 *
	 * @type {string}
	 */
	public readonly totalTax: string = '';

	/**
	 * The taxes applied to the product.
	 *
	 * @type {ReadonlyArray.<OrderItemTax>}
	 */
	public readonly taxes: OrderItemTax[] = [];

	/**
	 * The product SKU.
	 *
	 * @type {string}
	 */
	public readonly sku: string = '';

	/**
	 * The price of the product.
	 *
	 * @type {number}
	 */
	public readonly price: number = -1;

	/**
	 * The name of the parent product.
	 *
	 * @type {string|null}
	 */
	public readonly parentName: string | null = null;
}

/**
 * Order Tax Rate
 */
export class OrderTaxRate extends Model {
	/**
	 * The tax rate code.
	 *
	 * @type {string}
	 */
	public readonly rateCode: string = '';

	/**
	 * The tax rate id.
	 *
	 * @type {number}
	 */
	public readonly rateId: number = 0;

	/**
	 * The tax label.
	 *
	 * @type {string}
	 */
	public readonly label: string = '';

	/**
	 * Flag indicating whether it's a compound tax rate.
	 *
	 * @type {boolean}
	 */
	public readonly compoundRate: boolean = false;

	/**
	 * The total tax for this rate code.
	 *
	 * @type {string}
	 */
	public readonly taxTotal: string = '';

	/**
	 * The total shipping tax for this rate code.
	 *
	 * @type {string}
	 */
	public readonly shippingTaxTotal: string = '';

	/**
	 * The tax rate as a percentage.
	 *
	 * @type {number}
	 */
	public readonly ratePercent: number = 0;
}

/**
 * Order shipping line
 */
export class OrderShippingLine extends OrderItemMeta {
	/**
	 * The shipping method title.
	 *
	 * @type {string}
	 */
	public readonly methodTitle: string = '';

	/**
	 * The shipping method id.
	 *
	 * @type {string}
	 */
	public readonly methodId: string = '';

	/**
	 * The shipping method instance id.
	 *
	 * @type {string}
	 */
	public readonly instanceId: string = '';

	/**
	 * The total shipping amount for this method.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';

	/**
	 * The total tax amount for this shipping method.
	 *
	 * @type {string}
	 */
	public readonly totalTax: string = '';

	/**
	 * The taxes applied to this shipping method.
	 *
	 * @type {ReadonlyArray.<OrderItemTax>}
	 */
	public readonly taxes: OrderItemTax[] = [];
}

/**
 * Order fee line
 */
export class OrderFeeLine extends OrderItemMeta {
	/**
	 * The name of the fee.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The tax class of the fee.
	 *
	 * @type {string}
	 */
	public readonly taxClass: string = '';

	/**
	 * The tax status of the fee.
	 *
	 * @type {TaxStatus}
	 */
	public readonly taxStatus: TaxStatus = 'taxable';

	/**
	 * The total amount for this fee.
	 *
	 * @type {string}
	 */
	public readonly amount: string = '';

	/**
	 * The display total amount for this fee.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';

	/**
	 * The total tax amount for this fee.
	 *
	 * @type {string}
	 */
	public readonly totalTax: string = '';

	/**
	 * The taxes applied to this fee.
	 *
	 * @type {ReadonlyArray.<OrderItemTax>}
	 */
	public readonly taxes: OrderItemTax[] = [];
}

/**
 * Order coupon line
 */
export class OrderCouponLine extends OrderItemMeta {
	/**
	 * The coupon code
	 *
	 * @type {string}
	 */
	public readonly code: string = '';

	/**
	 * The discount amount.
	 *
	 * @type {string}
	 */
	public readonly discount: string = '';

	/**
	 * The discount tax.
	 *
	 * @type {string}
	 */
	public readonly discountTax: string = '';
}

/**
 * Order refund line
 */
export class OrderRefundLine extends Model {
	/**
	 * The reason for giving the refund.
	 *
	 * @type {string}
	 */
	public readonly reason: string = '';

	/**
	 * The total amount of the refund.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';
}
