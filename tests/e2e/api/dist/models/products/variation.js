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
exports.ProductVariation = void 0;
var abstract_1 = require("./abstract");
var shared_1 = require("./shared");
var repositories_1 = require("../../repositories");
/**
 * The base for the product variation object.
 */
var ProductVariation = /** @class */ (function (_super) {
    __extends(ProductVariation, _super);
    /**
     * Creates a new product variation instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function ProductVariation(properties) {
        var _this = _super.call(this) || this;
        /**
         * @see ./abstracts/delivery.ts
         */
        _this.isVirtual = false;
        _this.isDownloadable = false;
        _this.downloads = [];
        _this.downloadLimit = -1;
        _this.daysToDownload = -1;
        _this.purchaseNote = '';
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
         * @see ./abstracts/price.ts
         */
        _this.price = '';
        _this.priceHtml = '';
        _this.regularPrice = '';
        _this.onSale = false;
        _this.salePrice = '';
        _this.saleStart = null;
        _this.saleEnd = null;
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
         * The variation's links.
         *
         * @type {ReadonlyArray.<ObjectLinks>}
         */
        _this.links = {
            collection: [{ href: '' }],
            self: [{ href: '' }],
            up: [{ href: '' }],
        };
        /**
         * The attributes for the variation.
         *
         * @type {ReadonlyArray.<ProductDefaultAttribute>}
         */
        _this.attributes = [];
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    ProductVariation.restRepository = function (httpClient) {
        return repositories_1.productVariationRESTRepository(httpClient);
    };
    return ProductVariation;
}(abstract_1.AbstractProductData));
exports.ProductVariation = ProductVariation;
