//import { HTTPClient } from '../../http';
//import { orderRESTRepository } from '../../repositories';
import {
	ModelRepositoryParams,
	CreatesModels,
	ListsModels,
	ReadsModels,
	UpdatesModels,
	DeletesModels,
} from '../../framework';
import {
	OrderAddressUpdateParams,
	OrderCouponUpdateParams,
	OrderDataUpdateParams,
	OrderFeeUpdateParams,
	OrderLineItemUpdateParams,
	OrderRefundUpdateParams,
	OrderShippingUpdateParams,
	OrderTaxUpdateParams,
	OrderTotalUpdateParams,
	OrderItemMeta,
	OrderAddress,
	OrderCouponLine,
	OrderFeeLine,
	OrderLineItem,
	OrderRefundLine,
	OrderShippingLine,
	OrderStatus,
	OrderTaxRate,
} from './shared';
import { ObjectLinks } from '../shared-types';

/**
 * The parameters that orders can update.
 */
type OrderUpdateParams = OrderAddressUpdateParams
	& OrderCouponUpdateParams
	& OrderDataUpdateParams
	& OrderFeeUpdateParams
	& OrderLineItemUpdateParams
	& OrderRefundUpdateParams
	& OrderShippingUpdateParams
	& OrderTaxUpdateParams
	& OrderTotalUpdateParams;

/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type OrderRepositoryParams = ModelRepositoryParams< Order, never, never, OrderUpdateParams >;

/**
 * An interface for creating coupons using the repository.
 *
 * @typedef CreatesCoupons
 * @alias CreatesModels.<Coupon>
 */
export type CreatesCoupons = CreatesModels< OrderRepositoryParams >;

/**
 * An interface for reading coupons using the repository.
 *
 * @typedef ReadsCoupons
 * @alias ReadsModels.<Coupon>
 */
export type ReadsCoupons = ReadsModels< OrderRepositoryParams >;

/**
 * An interface for updating coupons using the repository.
 *
 * @typedef UpdatesCoupons
 * @alias UpdatesModels.<Coupon>
 */
export type UpdatesCoupons = UpdatesModels< OrderRepositoryParams >;

/**
 * An interface for listing coupons using the repository.
 *
 * @typedef ListsCoupons
 * @alias ListsModels.<Coupon>
 */
export type ListsCoupons = ListsModels< OrderRepositoryParams >;

/**
 * An interface for deleting coupons using the repository.
 *
 * @typedef DeletesCoupons
 * @alias DeletesModels.<Coupons>
 */
export type DeletesCoupons = DeletesModels< OrderRepositoryParams >;

/**
 * A coupon object.
 */
export class Order extends OrderItemMeta {
	/**
	 * The parent order id.
	 *
	 * @type {number}
	 */
	public readonly parentId: number = 0;

	/**
	 * The order status.
	 *
	 * @type {string}
	 */
	public readonly status: OrderStatus = '';

	/**
	 * The order currency.
	 *
	 * @type {string}
	 */
	public readonly currency: string = '';

	/**
	 * The WC version used to create the order.
	 *
	 * @type {string}
	 */
	public readonly version: string = '';

	/**
	 * Flags if the prices include tax.
	 *
	 * @type {boolean}
	 */
	public readonly pricesIncludeTax: boolean = false;

	/**
	 * The total of the discounts on the order.
	 *
	 * @type {string}
	 */
	public readonly discountTotal: string = '';

	/**
	 * The total of the tax on discounts on the order.
	 *
	 * @type {string}
	 */
	public readonly discountTax: string = '';

	/**
	 * The total of the shipping on the order.
	 *
	 * @type {string}
	 */
	public readonly shippingTotal: string = '';

	/**
	 * The total of the tax on shipping on the order.
	 *
	 * @type {string}
	 */
	public readonly shippingTax: string = '';

	/**
	 * The total cart tax on the order.
	 *
	 * @type {string}
	 */
	public readonly cartTax: string = '';

	/**
	 * The total for the order including adjustments.
	 *
	 * @type {string}
	 */
	public readonly total: string = '';

	/**
	 * The total tax for the order including adjustments.
	 *
	 * @type {string}
	 */
	public readonly totalTax: string = '';

	/**
	 * The customer id.
	 *
	 * @type {number}
	 */
	public readonly customerId: number = 0;

	/**
	 * A unique key assigned to the order.
	 *
	 * @type {string}
	 */
	public readonly orderKey: string = '';

	/**
	 * The billing address.
	 *
	 * @type {OrderAddress}
	 */
	public readonly billing: OrderAddress | null = null;

	/**
	 * The shipping address.
	 *
	 * @type {OrderAddress}
	 */
	public readonly shipping: OrderAddress | null = null;

	/**
	 * Name of the payment method.
	 *
	 * @type {string}
	 */
	public readonly paymentMethod: string = '';

	/**
	 * Title of the payment method
	 *
	 * @type {string}
	 */
	public readonly paymentMethodTitle: string = '';

	/**
	 * Payment transaction ID.
	 *
	 * @type {string}
	 */
	public readonly transactionId: string = '';

	/**
	 * Customer IP address.
	 *
	 * @type {string}
	 */
	public readonly customerIpAddress: string = '';

	/**
	 * Customer web browser user agent.
	 *
	 * @type {string}
	 */
	public readonly customerUserAgent: string = '';

	/**
	 * Method used to create the order.
	 *
	 * @type {string}
	 */
	public readonly createdVia: string = '';

	/**
	 * Customer note.
	 *
	 * @type {string}
	 */
	public readonly customerNote: string = '';

	/**
	 * Date the order was completed.
	 *
	 * @type {string}
	 */
	public readonly dateCompleted: Date | null = null;

	/**
	 * Date the order was paid.
	 *
	 * @type {string}
	 */
	public readonly datePaid: Date | null = null;

	/**
	 * Hash of the cart's contents.
	 *
	 * @type {string}
	 */
	public readonly cartHash: string = '';

	/**
	 * Number assigned to the order.
	 *
	 * @type {string}
	 */
	public readonly orderNumber: string = '';

	/**
	 * Currency symbol for the order.
	 *
	 * @type {string}
	 */
	public readonly currencySymbol: string = '';

	/**
	 * The order's line items.
	 *
	 * @type {ReadonlyArray.<OrderLineItem>}
	 */
	public readonly lineItems: OrderLineItem[] = [];

	/**
	 * The order's tax rates.
	 *
	 * @type {ReadonlyArray.<OrderTaxRate>}
	 */
	public readonly taxLines: OrderTaxRate[] = [];

	/**
	 * The order's shipping charges.
	 *
	 * @type {ReadonlyArray.<OrderShippingLine>}
	 */
	public readonly shippingLines: OrderShippingLine[] = [];

	/**
	 * The order's fees.
	 *
	 * @type {ReadonlyArray.<OrderFeeLine>}
	 */
	public readonly feeLines: OrderFeeLine[] = [];

	/**
	 * The coupons used on the order.
	 *
	 * @type {ReadonlyArray.<OrderCouponLine>}
	 */
	public readonly couponLines: OrderCouponLine[] = [];

	/**
	 * The refunds to the order.
	 *
	 * @type {ReadonlyArray.<OrderRefundLine>}
	 */
	public readonly refunds: OrderRefundLine[] = [];

	/**
	 * The order's links.
	 *
	 * @type {ReadonlyArray.<ObjectLinks>}
	 */
	public readonly links: ObjectLinks = {
		collection: [ { href: '' } ],
		self: [ { href: '' } ],
	};

	/**
	 * Creates a new coupon instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< Order > ) {
		super();
		Object.assign( this, properties );
	}
	/*
	/**
	 * Returns the repository for interacting with this type of model.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	/*
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof orderRESTRepository > {
		return orderRESTRepository( httpClient );
	}
	 */
}
