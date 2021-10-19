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
exports.Coupon = void 0;
var model_1 = require("../model");
var repositories_1 = require("../../repositories");
/**
 * A coupon object.
 */
var Coupon = /** @class */ (function (_super) {
    __extends(Coupon, _super);
    /**
     * Creates a new coupon instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function Coupon(properties) {
        var _this = _super.call(this) || this;
        /**
         * The coupon code.
         *
         * @type {string}
         */
        _this.code = '';
        /**
         * The amount of the discount, must always be numeric.
         *
         * @type {string}
         */
        _this.amount = '';
        /**
         * The date the coupon was created.
         *
         * @type {Date}
         */
        _this.dateCreated = new Date();
        /**
         * The date the coupon was modified.
         *
         * @type {Date}
         */
        _this.dateModified = new Date();
        /**
         * The discount type for the coupon.
         *
         * @type {string}
         */
        _this.discountType = '';
        /**
         * The description of the coupon.
         *
         * @type {string}
         */
        _this.description = '';
        /**
         * The date the coupon expires.
         *
         * @type {Date}
         */
        _this.dateExpires = new Date();
        /**
         * The number of times the coupon has already been used.
         *
         * @type {number}
         */
        _this.usageCount = 0;
        /**
         * Flags if the coupon can only be used on its own and not combined with other coupons.
         *
         * @type {boolean}
         */
        _this.individualUse = false;
        /**
         * List of Product IDs that the coupon can be applied to.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.productIds = [];
        /**
         * List of Product IDs that the coupon cannot be applied to.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.excludedProductIds = [];
        /**
         * How many times the coupon can be used.
         *
         * @type {number}
         */
        _this.usageLimit = -1;
        /**
         * How many times the coupon can be used per customer.
         *
         * @type {number}
         */
        _this.usageLimitPerUser = -1;
        /**
         * Max number of items in the cart the coupon can be applied to.
         *
         * @type {number}
         */
        _this.limitUsageToXItems = -1;
        /**
         * Flags if the free shipping option requires a coupon. This coupon will enable free shipping.
         *
         * @type {boolean}
         */
        _this.freeShipping = false;
        /**
         * List of Category IDs the coupon applies to.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.productCategories = [];
        /**
         * List of Category IDs the coupon does not apply to.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.excludedProductCategories = [];
        /**
         * Flags if the coupon applies to items on sale.
         *
         * @type {boolean}
         */
        _this.excludeSaleItems = false;
        /**
         * The minimum order amount that needs to be in the cart before the coupon applies.
         *
         * @type {string}
         */
        _this.minimumAmount = '';
        /**
         * The maximum order amount allowed when using the coupon.
         *
         * @type {string}
         */
        _this.maximumAmount = '';
        /**
         * List of email addresses that can use this coupon.
         *
         * @type {ReadonlyArray.<string>}
         */
        _this.emailRestrictions = [];
        /**
         * List of user IDs (or guest emails) that have used the coupon.
         *
         * @type {ReadonlyArray.<string>}
         */
        _this.usedBy = [];
        /**
         * The coupon's links.
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
    Coupon.restRepository = function (httpClient) {
        return repositories_1.couponRESTRepository(httpClient);
    };
    return Coupon;
}(model_1.Model));
exports.Coupon = Coupon;
