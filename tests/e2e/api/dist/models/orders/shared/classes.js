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
exports.OrderRefundLine = exports.OrderCouponLine = exports.OrderFeeLine = exports.OrderShippingLine = exports.OrderTaxRate = exports.OrderLineItem = exports.OrderAddress = exports.OrderItemTax = exports.OrderItemMeta = void 0;
var model_1 = require("../../model");
/**
 * Order item meta.
 */
var OrderItemMeta = /** @class */ (function (_super) {
    __extends(OrderItemMeta, _super);
    function OrderItemMeta() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The meta data the order item.
         *
         * @type {ReadonlyArray.<MetaData>}
         */
        _this.metaData = [];
        return _this;
    }
    return OrderItemMeta;
}(model_1.Model));
exports.OrderItemMeta = OrderItemMeta;
/**
 * Order line item tax entry.
 */
var OrderItemTax = /** @class */ (function (_super) {
    __extends(OrderItemTax, _super);
    function OrderItemTax() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The total tax for this tax rate on this item.
         *
         * @type {string}
         */
        _this.total = '';
        /**
         * The subtotal tax for this tax rate on this item.
         *
         * @type {string}
         */
        _this.subtotal = '';
        return _this;
    }
    return OrderItemTax;
}(model_1.Model));
exports.OrderItemTax = OrderItemTax;
/**
 * An order address.
 */
var OrderAddress = /** @class */ (function (_super) {
    __extends(OrderAddress, _super);
    function OrderAddress() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The first name of the person in the address.
         *
         * @type {string}
         */
        _this.firstName = '';
        /**
         * The last name of the person in the address.
         *
         * @type {string}
         */
        _this.lastName = '';
        /**
         * The company name of the person in the address.
         *
         * @type {string}
         */
        _this.companyName = '';
        /**
         * The first address line in the address.
         *
         * @type {string}
         */
        _this.address1 = '';
        /**
         * The second address line in the address.
         *
         * @type {string}
         */
        _this.address2 = '';
        /**
         * The city in the address.
         *
         * @type {string}
         */
        _this.city = '';
        /**
         * The state in the address.
         *
         * @type {string}
         */
        _this.state = '';
        /**
         * The postal code in the address.
         *
         * @type {string}
         */
        _this.postCode = '';
        /**
         * The country code for the address.
         *
         * @type {string}
         */
        _this.countryCode = '';
        /**
         * The email address of the person in the address.
         *
         * @type {string}
         */
        _this.email = '';
        /**
         * The phone number of the person in the address.
         *
         * @type {string}
         */
        _this.phone = '';
        return _this;
    }
    return OrderAddress;
}(model_1.Model));
exports.OrderAddress = OrderAddress;
/**
 * Order Line Item
 */
var OrderLineItem = /** @class */ (function (_super) {
    __extends(OrderLineItem, _super);
    function OrderLineItem() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The name of the product.
         *
         * @type {string}
         */
        _this.name = '';
        /**
         * The ID of the product.
         *
         * @type {number}
         */
        _this.productId = -1;
        /**
         * The ID of the product variation.
         *
         * @type {number}
         */
        _this.variationId = 0;
        /**
         * The quantity of the product.
         *
         * @type {number}
         */
        _this.quantity = -1;
        /**
         * The tax class for the product.
         *
         * @type {string}
         */
        _this.taxClass = '';
        /**
         * The subtotal for the product.
         *
         * @type {string}
         */
        _this.subtotal = '';
        /**
         * The subtotal tax for the product.
         *
         * @type {string}
         */
        _this.subtotalTax = '';
        /**
         * The total for the product including adjustments.
         *
         * @type {string}
         */
        _this.total = '';
        /**
         * The total tax for the product including adjustments.
         *
         * @type {string}
         */
        _this.totalTax = '';
        /**
         * The taxes applied to the product.
         *
         * @type {ReadonlyArray.<OrderItemTax>}
         */
        _this.taxes = [];
        /**
         * The product SKU.
         *
         * @type {string}
         */
        _this.sku = '';
        /**
         * The price of the product.
         *
         * @type {number}
         */
        _this.price = -1;
        /**
         * The name of the parent product.
         *
         * @type {string|null}
         */
        _this.parentName = null;
        return _this;
    }
    return OrderLineItem;
}(OrderItemMeta));
exports.OrderLineItem = OrderLineItem;
/**
 * Order Tax Rate
 */
var OrderTaxRate = /** @class */ (function (_super) {
    __extends(OrderTaxRate, _super);
    function OrderTaxRate() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The tax rate code.
         *
         * @type {string}
         */
        _this.rateCode = '';
        /**
         * The tax rate id.
         *
         * @type {number}
         */
        _this.rateId = 0;
        /**
         * The tax label.
         *
         * @type {string}
         */
        _this.label = '';
        /**
         * Flag indicating whether it's a compound tax rate.
         *
         * @type {boolean}
         */
        _this.compoundRate = false;
        /**
         * The total tax for this rate code.
         *
         * @type {string}
         */
        _this.taxTotal = '';
        /**
         * The total shipping tax for this rate code.
         *
         * @type {string}
         */
        _this.shippingTaxTotal = '';
        /**
         * The tax rate as a percentage.
         *
         * @type {number}
         */
        _this.ratePercent = 0;
        return _this;
    }
    return OrderTaxRate;
}(model_1.Model));
exports.OrderTaxRate = OrderTaxRate;
/**
 * Order shipping line
 */
var OrderShippingLine = /** @class */ (function (_super) {
    __extends(OrderShippingLine, _super);
    function OrderShippingLine() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The shipping method title.
         *
         * @type {string}
         */
        _this.methodTitle = '';
        /**
         * The shipping method id.
         *
         * @type {string}
         */
        _this.methodId = '';
        /**
         * The shipping method instance id.
         *
         * @type {string}
         */
        _this.instanceId = '';
        /**
         * The total shipping amount for this method.
         *
         * @type {string}
         */
        _this.total = '';
        /**
         * The total tax amount for this shipping method.
         *
         * @type {string}
         */
        _this.totalTax = '';
        /**
         * The taxes applied to this shipping method.
         *
         * @type {ReadonlyArray.<OrderItemTax>}
         */
        _this.taxes = [];
        return _this;
    }
    return OrderShippingLine;
}(OrderItemMeta));
exports.OrderShippingLine = OrderShippingLine;
/**
 * Order fee line
 */
var OrderFeeLine = /** @class */ (function (_super) {
    __extends(OrderFeeLine, _super);
    function OrderFeeLine() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The name of the fee.
         *
         * @type {string}
         */
        _this.name = '';
        /**
         * The tax class of the fee.
         *
         * @type {string}
         */
        _this.taxClass = '';
        /**
         * The tax status of the fee.
         *
         * @type {TaxStatus}
         */
        _this.taxStatus = 'taxable';
        /**
         * The total amount for this fee.
         *
         * @type {string}
         */
        _this.amount = '';
        /**
         * The display total amount for this fee.
         *
         * @type {string}
         */
        _this.total = '';
        /**
         * The total tax amount for this fee.
         *
         * @type {string}
         */
        _this.totalTax = '';
        /**
         * The taxes applied to this fee.
         *
         * @type {ReadonlyArray.<OrderItemTax>}
         */
        _this.taxes = [];
        return _this;
    }
    return OrderFeeLine;
}(OrderItemMeta));
exports.OrderFeeLine = OrderFeeLine;
/**
 * Order coupon line
 */
var OrderCouponLine = /** @class */ (function (_super) {
    __extends(OrderCouponLine, _super);
    function OrderCouponLine() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The coupon code
         *
         * @type {string}
         */
        _this.code = '';
        /**
         * The discount amount.
         *
         * @type {string}
         */
        _this.discount = '';
        /**
         * The discount tax.
         *
         * @type {string}
         */
        _this.discountTax = '';
        return _this;
    }
    return OrderCouponLine;
}(OrderItemMeta));
exports.OrderCouponLine = OrderCouponLine;
/**
 * Order refund line
 */
var OrderRefundLine = /** @class */ (function (_super) {
    __extends(OrderRefundLine, _super);
    function OrderRefundLine() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The reason for giving the refund.
         *
         * @type {string}
         */
        _this.reason = '';
        /**
         * The total amount of the refund.
         *
         * @type {string}
         */
        _this.total = '';
        return _this;
    }
    return OrderRefundLine;
}(model_1.Model));
exports.OrderRefundLine = OrderRefundLine;
