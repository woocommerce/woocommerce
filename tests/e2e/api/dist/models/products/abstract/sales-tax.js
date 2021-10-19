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
var shared_1 = require("../shared");
/**
 * The base for products with sales tax.
 */
var AbstractProductSalesTax = /** @class */ (function (_super) {
    __extends(AbstractProductSalesTax, _super);
    function AbstractProductSalesTax() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The taxability of the product.
         *
         * @type {Taxability}
         */
        _this.taxStatus = shared_1.Taxability.ProductAndShipping;
        /**
         * The tax class of the product
         *
         * @type {string}
         */
        _this.taxClass = '';
        return _this;
    }
    return AbstractProductSalesTax;
}(model_1.Model));
