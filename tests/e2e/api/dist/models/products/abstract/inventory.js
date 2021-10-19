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
 * The base for inventory products.
 */
var AbstractProductInventory = /** @class */ (function (_super) {
    __extends(AbstractProductInventory, _super);
    function AbstractProductInventory() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * Indicates that only one of a product may be held in the order at a time.
         *
         * @type {boolean}
         */
        _this.onePerOrder = false;
        /**
         * Indicates that a product should use the inventory system.
         *
         * @type {boolean}
         */
        _this.trackInventory = false;
        /**
         * The number of inventory units remaining for this product.
         *
         * @type {number}
         */
        _this.remainingStock = -1;
        /**
         * The product's stock status.
         *
         * @type {StockStatus}
         */
        _this.stockStatus = '';
        /**
         * The status of backordering for a product.
         *
         * @type {BackorderStatus}
         */
        _this.backorderStatus = shared_1.BackorderStatus.Allowed;
        /**
         * Indicates whether or not a product can be backordered.
         *
         * @type {boolean}
         */
        _this.canBackorder = false;
        /**
         * Indicates whether or not a product is on backorder.
         *
         * @type {boolean}
         */
        _this.isOnBackorder = false;
        return _this;
    }
    return AbstractProductInventory;
}(model_1.Model));
