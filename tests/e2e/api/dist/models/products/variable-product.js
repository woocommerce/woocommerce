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
exports.VariableProduct = void 0;
var abstract_1 = require("./abstract");
var shared_1 = require("./shared");
var repositories_1 = require("../../repositories");
/**
 * The base for the Variable product object.
 */
var VariableProduct = /** @class */ (function (_super) {
    __extends(VariableProduct, _super);
    /**
     * Creates a new Variable product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function VariableProduct(properties) {
        var _this = _super.call(this) || this;
        /**
         * @see ./abstracts/cross-sells.ts
         */
        _this.crossSellIds = [];
        /**
         * @see ./abstracts/upsell.ts
         */
        _this.upSellIds = [];
        /**
         * @see ./abstracts/inventory.ts
         */
        _this.onePerOrder = false;
        _this.trackInventory = false;
        _this.remainingStock = -1;
        _this.stockStatus = '';
        _this.backorderStatus = shared_1.BackorderStatus.Allowed;
        _this.canBackorder = false;
        _this.isOnBackorder = false;
        /**
         * @see ./abstracts/sales-tax.ts
         */
        _this.taxStatus = shared_1.Taxability.ProductAndShipping;
        _this.taxClass = '';
        /**
         * @see ./abstracts/shipping.ts
         */
        _this.weight = '';
        _this.length = '';
        _this.width = '';
        _this.height = '';
        _this.requiresShipping = false;
        _this.isShippingTaxable = false;
        _this.shippingClass = '';
        _this.shippingClassId = 0;
        /**
         * Default product attributes.
         *
         * @type {ReadonlyArray.<ProductDefaultAttribute>}
         */
        _this.defaultAttributes = [];
        /**
         * Product variations.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.variations = [];
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    VariableProduct.restRepository = function (httpClient) {
        return repositories_1.variableProductRESTRepository(httpClient);
    };
    return VariableProduct;
}(abstract_1.AbstractProduct));
exports.VariableProduct = VariableProduct;
