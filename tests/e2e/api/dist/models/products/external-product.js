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
exports.ExternalProduct = void 0;
var abstract_1 = require("./abstract");
var shared_1 = require("./shared");
var repositories_1 = require("../../repositories");
/**
 * The base for the external product object.
 */
var ExternalProduct = /** @class */ (function (_super) {
    __extends(ExternalProduct, _super);
    /**
     * Creates a new simple product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function ExternalProduct(properties) {
        var _this = _super.call(this) || this;
        /**
         * @see ./abstracts/external.ts
         */
        _this.buttonText = '';
        _this.externalUrl = '';
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
         * @see ./abstracts/upsell.ts
         */
        _this.upSellIds = [];
        /**
         * @see ./abstracts/sales-tax.ts
         */
        _this.taxStatus = shared_1.Taxability.ProductAndShipping;
        _this.taxClass = '';
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    ExternalProduct.restRepository = function (httpClient) {
        return repositories_1.externalProductRESTRepository(httpClient);
    };
    return ExternalProduct;
}(abstract_1.AbstractProduct));
exports.ExternalProduct = ExternalProduct;
