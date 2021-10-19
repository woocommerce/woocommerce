import { HTTPClient } from '../../http';
import { orderRESTRepository } from '../../repositories';
import { ModelRepositoryParams, CreatesModels, ListsModels, ReadsModels, UpdatesModels, DeletesModels } from '../../framework';
import { OrderAddressUpdateParams, OrderCouponUpdateParams, OrderDataUpdateParams, OrderFeeUpdateParams, OrderLineItemUpdateParams, OrderRefundUpdateParams, OrderShippingUpdateParams, OrderTaxUpdateParams, OrderTotalUpdateParams, OrderItemMeta, OrderAddress, OrderCouponLine, OrderFeeLine, OrderLineItem, OrderRefundLine, OrderShippingLine, OrderStatus, OrderTaxRate } from './shared';
import { ObjectLinks } from '../shared-types';
/**
 * The parameters that orders can update.
 */
declare type OrderUpdateParams = OrderAddressUpdateParams & OrderCouponUpdateParams & OrderDataUpdateParams & OrderFeeUpdateParams & OrderLineItemUpdateParams & OrderRefundUpdateParams & OrderShippingUpdateParams & OrderTaxUpdateParams & OrderTotalUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type OrderRepositoryParams = ModelRepositoryParams<Order, never, never, OrderUpdateParams>;
/**
 * An interface for creating orders using the repository.
 *
 * @typedef CreatesOrders
 * @alias CreatesModels.<Order>
 */
export declare type CreatesOrders = CreatesModels<OrderRepositoryParams>;
/**
 * An interface for reading orders using the repository.
 *
 * @typedef ReadsOrders
 * @alias ReadsModels.<Order>
 */
export declare type ReadsOrders = ReadsModels<OrderRepositoryParams>;
/**
 * An interface for updating orders using the repository.
 *
 * @typedef UpdatesOrders
 * @alias UpdatesModels.<Order>
 */
export declare type UpdatesOrders = UpdatesModels<OrderRepositoryParams>;
/**
 * An interface for listing orders using the repository.
 *
 * @typedef ListsOrders
 * @alias ListsModels.<Order>
 */
export declare type ListsOrders = ListsModels<OrderRepositoryParams>;
/**
 * An interface for deleting orders using the repository.
 *
 * @typedef DeletesOrders
 * @alias DeletesModels.<Orders>
 */
export declare type DeletesOrders = DeletesModels<OrderRepositoryParams>;
/**
 * An order object.
 */
export declare class Order extends OrderItemMeta {
    /**
     * The parent order id.
     *
     * @type {number}
     */
    readonly parentId: number;
    /**
     * The order status.
     *
     * @type {string}
     */
    readonly status: OrderStatus;
    /**
     * The order currency.
     *
     * @type {string}
     */
    readonly currency: string;
    /**
     * The WC version used to create the order.
     *
     * @type {string}
     */
    readonly version: string;
    /**
     * Flags if the prices include tax.
     *
     * @type {boolean}
     */
    readonly pricesIncludeTax: boolean;
    /**
     * The total of the discounts on the order.
     *
     * @type {string}
     */
    readonly discountTotal: string;
    /**
     * The total of the tax on discounts on the order.
     *
     * @type {string}
     */
    readonly discountTax: string;
    /**
     * The total of the shipping on the order.
     *
     * @type {string}
     */
    readonly shippingTotal: string;
    /**
     * The total of the tax on shipping on the order.
     *
     * @type {string}
     */
    readonly shippingTax: string;
    /**
     * The total cart tax on the order.
     *
     * @type {string}
     */
    readonly cartTax: string;
    /**
     * The total for the order including adjustments.
     *
     * @type {string}
     */
    readonly total: string;
    /**
     * The total tax for the order including adjustments.
     *
     * @type {string}
     */
    readonly totalTax: string;
    /**
     * The customer id.
     *
     * @type {number}
     */
    readonly customerId: number;
    /**
     * A unique key assigned to the order.
     *
     * @type {string}
     */
    readonly orderKey: string;
    /**
     * The billing address.
     *
     * @type {OrderAddress}
     */
    readonly billing: OrderAddress | null;
    /**
     * The shipping address.
     *
     * @type {OrderAddress}
     */
    readonly shipping: OrderAddress | null;
    /**
     * Name of the payment method.
     *
     * @type {string}
     */
    readonly paymentMethod: string;
    /**
     * Title of the payment method
     *
     * @type {string}
     */
    readonly paymentMethodTitle: string;
    /**
     * Payment transaction ID.
     *
     * @type {string}
     */
    readonly transactionId: string;
    /**
     * Customer IP address.
     *
     * @type {string}
     */
    readonly customerIpAddress: string;
    /**
     * Customer web browser user agent.
     *
     * @type {string}
     */
    readonly customerUserAgent: string;
    /**
     * Method used to create the order.
     *
     * @type {string}
     */
    readonly createdVia: string;
    /**
     * Customer note.
     *
     * @type {string}
     */
    readonly customerNote: string;
    /**
     * Date the order was completed.
     *
     * @type {string}
     */
    readonly dateCompleted: Date | null;
    /**
     * Date the order was paid.
     *
     * @type {string}
     */
    readonly datePaid: Date | null;
    /**
     * Hash of the cart's contents.
     *
     * @type {string}
     */
    readonly cartHash: string;
    /**
     * Number assigned to the order.
     *
     * @type {string}
     */
    readonly orderNumber: string;
    /**
     * Currency symbol for the order.
     *
     * @type {string}
     */
    readonly currencySymbol: string;
    /**
     * The order's paid state.
     *
     * @type {boolean}
     */
    readonly setPaid: boolean;
    /**
     * The order's line items.
     *
     * @type {ReadonlyArray.<OrderLineItem>}
     */
    readonly lineItems: OrderLineItem[];
    /**
     * The order's tax rates.
     *
     * @type {ReadonlyArray.<OrderTaxRate>}
     */
    readonly taxLines: OrderTaxRate[];
    /**
     * The order's shipping charges.
     *
     * @type {ReadonlyArray.<OrderShippingLine>}
     */
    readonly shippingLines: OrderShippingLine[];
    /**
     * The order's fees.
     *
     * @type {ReadonlyArray.<OrderFeeLine>}
     */
    readonly feeLines: OrderFeeLine[];
    /**
     * The coupons used on the order.
     *
     * @type {ReadonlyArray.<OrderCouponLine>}
     */
    readonly couponLines: OrderCouponLine[];
    /**
     * The refunds to the order.
     *
     * @type {ReadonlyArray.<OrderRefundLine>}
     */
    readonly refunds: OrderRefundLine[];
    /**
     * The order's links.
     *
     * @type {ReadonlyArray.<ObjectLinks>}
     */
    readonly links: ObjectLinks;
    /**
     * Creates a new order instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<Order>);
    /**
     * Returns the repository for interacting with this type of model.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof orderRESTRepository>;
}
export {};
//# sourceMappingURL=orders.d.ts.map