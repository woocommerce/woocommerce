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
 * The base for price properties.
 */
var AbstractProductPrice = /** @class */ (function (_super) {
    __extends(AbstractProductPrice, _super);
    function AbstractProductPrice() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The current price of the product.
         *
         * @type {string}
         */
        _this.price = '';
        /**
         * The rendered HTML for the current price of the product.
         *
         * @type {string}
         */
        _this.priceHtml = '';
        /**
         * The regular price of the product when not discounted.
         *
         * @type {string}
         */
        _this.regularPrice = '';
        /**
         * Indicates whether or not the product is currently on sale.
         *
         * @type {boolean}
         */
        _this.onSale = false;
        /**
         * The price of the product when on sale.
         *
         * @type {string}
         */
        _this.salePrice = '';
        /**
         * The GMT datetime when the product should start to be on sale.
         *
         * @type {Date|null}
         */
        _this.saleStart = null;
        /**
         * The GMT datetime when the product should no longer be on sale.
         *
         * @type {Date|null}
         */
        _this.saleEnd = null;
        return _this;
    }
    return AbstractProductPrice;
}(model_1.Model));
