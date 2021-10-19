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
exports.GroupedProduct = void 0;
var abstract_1 = require("./abstract");
var repositories_1 = require("../../repositories");
/**
 * The base for the Grouped product object.
 */
var GroupedProduct = /** @class */ (function (_super) {
    __extends(GroupedProduct, _super);
    /**
     * Creates a new Grouped product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function GroupedProduct(properties) {
        var _this = _super.call(this) || this;
        /**
         * @see ./abstracts/grouped.ts
         */
        _this.groupedProducts = [];
        /**
         * @see ./abstracts/upsell.ts
         */
        _this.upSellIds = [];
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    GroupedProduct.restRepository = function (httpClient) {
        return repositories_1.groupedProductRESTRepository(httpClient);
    };
    return GroupedProduct;
}(abstract_1.AbstractProduct));
exports.GroupedProduct = GroupedProduct;
