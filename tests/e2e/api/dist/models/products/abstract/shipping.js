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
var model_1 = require("../../model");
/**
 * The base class for product shipping.
 */
var AbstractProductShipping = /** @class */ (function (_super) {
    __extends(AbstractProductShipping, _super);
    function AbstractProductShipping() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The weight of the product in the store's current units.
         *
         * @type {string}
         */
        _this.weight = '';
        /**
         * The length of the product in the store's current units.
         *
         * @type {string}
         */
        _this.length = '';
        /**
         * The width of the product in the store's current units.
         *
         * @type {string}
         */
        _this.width = '';
        /**
         * The height of the product in the store's current units.
         *
         * @type {string}
         */
        _this.height = '';
        /**
         * Indicates that the product must be shipped.
         *
         * @type {boolean}
         */
        _this.requiresShipping = false;
        /**
         * Indicates that the product's shipping is taxable.
         *
         * @type {boolean}
         */
        _this.isShippingTaxable = false;
        /**
         * The shipping class for the product.
         *
         * @type {string}
         */
        _this.shippingClass = '';
        /**
         * The shipping class ID for the product.
         *
         * @type {number}
         */
        _this.shippingClassId = 0;
        return _this;
    }
    return AbstractProductShipping;
}(model_1.Model));
