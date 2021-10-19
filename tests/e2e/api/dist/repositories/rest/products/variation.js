"use strict";
var __spreadArrays = (this && this.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.productVariationRESTRepository = void 0;
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("./shared");
var shared_2 = require("../shared");
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsProductVariations|
 * 	CreatesProductVariations|
 * 	ReadsProductVariations|
 * 	UpdatesProductVariations|
 * 	DeletesProductVariations
 * } The created repository.
 */
function productVariationRESTRepository(httpClient) {
    var buildURL = function (parent) { return models_1.buildProductURL(parent) + '/variations/'; };
    var buildChildURL = function (parent, id) { return buildURL(parent) + id; };
    var buildDeleteURL = function (parent, id) { return buildChildURL(parent, id) + '?force=true'; };
    var delivery = shared_1.createProductDeliveryTransformation();
    var inventory = shared_1.createProductInventoryTransformation();
    var price = shared_1.createProductPriceTransformation();
    var salesTax = shared_1.createProductSalesTaxTransformation();
    var shipping = shared_1.createProductShippingTransformation();
    var transformations = __spreadArrays(delivery, inventory, price, salesTax, shipping);
    var transformer = shared_1.createProductDataTransformer(transformations);
    return new framework_1.ModelRepository(shared_2.restListChild(buildURL, models_1.ProductVariation, httpClient, transformer), shared_2.restCreateChild(buildURL, models_1.ProductVariation, httpClient, transformer), shared_2.restReadChild(buildChildURL, models_1.ProductVariation, httpClient, transformer), shared_2.restUpdateChild(buildChildURL, models_1.ProductVariation, httpClient, transformer), shared_2.restDeleteChild(buildDeleteURL, httpClient));
}
exports.productVariationRESTRepository = productVariationRESTRepository;
