"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.Order = void 0;
var repositories_1 = require("../../repositories");
var shared_1 = require("./shared");
/**
 * An order object.
 */
var Order = /** @class */ (function (_super) {
    __extends(Order, _super);
    /**
     * Creates a new order instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function Order(properties) {
        var _this = _super.call(this) || this;
        /**
         * The parent order id.
         *
         * @type {number}
         */
        _this.parentId = 0;
        /**
         * The order status.
         *
         * @type {string}
         */
        _this.status = '';
        /**
         * The order currency.
         *
         * @type {string}
         */
        _this.currency = '';
        /**
         * The WC version used to create the order.
         *
         * @type {string}
         */
        _this.version = '';
        /**
         * Flags if the prices include tax.
         *
         * @type {boolean}
         */
        _this.pricesIncludeTax = false;
        /**
         * The total of the discounts on the order.
         *
         * @type {string}
         */
        _this.discountTotal = '';
        /**
         * The total of the tax on discounts on the order.
         *
         * @type {string}
         */
        _this.discountTax = '';
        /**
         * The total of the shipping on the order.
         *
         * @type {string}
         */
        _this.shippingTotal = '';
        /**
         * The total of the tax on shipping on the order.
         *
         * @type {string}
         */
        _this.shippingTax = '';
        /**
         * The total cart tax on the order.
         *
         * @type {string}
         */
        _this.cartTax = '';
        /**
         * The total for the order including adjustments.
         *
         * @type {string}
         */
        _this.total = '';
        /**
         * The total tax for the order including adjustments.
         *
         * @type {string}
         */
        _this.totalTax = '';
        /**
         * The customer id.
         *
         * @type {number}
         */
        _this.customerId = 0;
        /**
         * A unique key assigned to the order.
         *
         * @type {string}
         */
        _this.orderKey = '';
        /**
         * The billing address.
         *
         * @type {OrderAddress}
         */
        _this.billing = null;
        /**
         * The shipping address.
         *
         * @type {OrderAddress}
         */
        _this.shipping = null;
        /**
         * Name of the payment method.
         *
         * @type {string}
         */
        _this.paymentMethod = '';
        /**
         * Title of the payment method
         *
         * @type {string}
         */
        _this.paymentMethodTitle = '';
        /**
         * Payment transaction ID.
         *
         * @type {string}
         */
        _this.transactionId = '';
        /**
         * Customer IP address.
         *
         * @type {string}
         */
        _this.customerIpAddress = '';
        /**
         * Customer web browser user agent.
         *
         * @type {string}
         */
        _this.customerUserAgent = '';
        /**
         * Method used to create the order.
         *
         * @type {string}
         */
        _this.createdVia = '';
        /**
         * Customer note.
         *
         * @type {string}
         */
        _this.customerNote = '';
        /**
         * Date the order was completed.
         *
         * @type {string}
         */
        _this.dateCompleted = null;
        /**
         * Date the order was paid.
         *
         * @type {string}
         */
        _this.datePaid = null;
        /**
         * Hash of the cart's contents.
         *
         * @type {string}
         */
        _this.cartHash = '';
        /**
         * Number assigned to the order.
         *
         * @type {string}
         */
        _this.orderNumber = '';
        /**
         * Currency symbol for the order.
         *
         * @type {string}
         */
        _this.currencySymbol = '';
        /**
         * The order's paid state.
         *
         * @type {boolean}
         */
        _this.setPaid = false;
        /**
         * The order's line items.
         *
         * @type {ReadonlyArray.<OrderLineItem>}
         */
        _this.lineItems = [];
        /**
         * The order's tax rates.
         *
         * @type {ReadonlyArray.<OrderTaxRate>}
         */
        _this.taxLines = [];
        /**
         * The order's shipping charges.
         *
         * @type {ReadonlyArray.<OrderShippingLine>}
         */
        _this.shippingLines = [];
        /**
         * The order's fees.
         *
         * @type {ReadonlyArray.<OrderFeeLine>}
         */
        _this.feeLines = [];
        /**
         * The coupons used on the order.
         *
         * @type {ReadonlyArray.<OrderCouponLine>}
         */
        _this.couponLines = [];
        /**
         * The refunds to the order.
         *
         * @type {ReadonlyArray.<OrderRefundLine>}
         */
        _this.refunds = [];
        /**
         * The order's links.
         *
         * @type {ReadonlyArray.<ObjectLinks>}
         */
        _this.links = {
            collection: [{ href: '' }],
            self: [{ href: '' }],
        };
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Returns the repository for interacting with this type of model.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    Order.restRepository = function (httpClient) {
        return repositories_1.orderRESTRepository(httpClient);
    };
    return Order;
}(shared_1.OrderItemMeta));
exports.Order = Order;
