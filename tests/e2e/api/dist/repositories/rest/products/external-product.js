"use strict";
var __spreadArrays = (this && this.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.externalProductRESTRepository = void 0;
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("./shared");
var shared_2 = require("../shared");
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsExternalProducts|
 * 	CreatesExternalProducts|
 * 	ReadsExternalProducts|
 * 	UpdatesExternalProducts|
 * 	DeletesExternalProducts
 * } The created repository.
 */
function externalProductRESTRepository(httpClient) {
    var external = shared_1.createProductExternalTransformation();
    var price = shared_1.createProductPriceTransformation();
    var salesTax = shared_1.createProductSalesTaxTransformation();
    var upsells = shared_1.createProductUpSellsTransformation();
    var transformations = __spreadArrays(external, price, salesTax, upsells);
    var transformer = shared_1.createProductTransformer('external', transformations);
    return new framework_1.ModelRepository(shared_2.restList(models_1.baseProductURL, models_1.ExternalProduct, httpClient, transformer), shared_2.restCreate(models_1.baseProductURL, models_1.ExternalProduct, httpClient, transformer), shared_2.restRead(models_1.buildProductURL, models_1.ExternalProduct, httpClient, transformer), shared_2.restUpdate(models_1.buildProductURL, models_1.ExternalProduct, httpClient, transformer), shared_2.restDelete(models_1.deleteProductURL, httpClient));
}
exports.externalProductRESTRepository = externalProductRESTRepository;
