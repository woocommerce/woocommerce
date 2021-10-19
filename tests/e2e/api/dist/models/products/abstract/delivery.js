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
 * The base for Delivery products.
 */
var AbstractProductDelivery = /** @class */ (function (_super) {
    __extends(AbstractProductDelivery, _super);
    function AbstractProductDelivery() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * Indicates that the product is delivered virtually.
         *
         * @type {boolean}
         */
        _this.isVirtual = false;
        /**
         * Indicates whether or not the product is downloadable.
         *
         * @type {boolean}
         */
        _this.isDownloadable = false;
        /**
         * The downloads available for the product.
         *
         * @type {ReadonlyArray.<ProductDownload>}
         */
        _this.downloads = [];
        /**
         * The maximum number of times a customer may download the product's contents.
         *
         * @type {number}
         */
        _this.downloadLimit = -1;
        /**
         * The number of days after purchase that a customer may still download the product's contents.
         *
         * @type {number}
         */
        _this.daysToDownload = -1;
        /**
         * The text shown to the customer on completing the purchase of this product.
         *
         * @type {string}
         */
        _this.purchaseNote = '';
        return _this;
    }
    return AbstractProductDelivery;
}(model_1.Model));
