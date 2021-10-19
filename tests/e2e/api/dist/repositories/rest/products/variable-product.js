"use strict";
var __spreadArrays = (this && this.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.variableProductRESTRepository = void 0;
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("./shared");
var shared_2 = require("../shared");
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsVariableProducts|
 * 	CreatesVariableProducts|
 * 	ReadsVariableProducts|
 * 	UpdatesVariableProducts|
 * 	DeletesVariableProducts
 * } The created repository.
 */
function variableProductRESTRepository(httpClient) {
    var crossSells = shared_1.createProductCrossSellsTransformation();
    var inventory = shared_1.createProductInventoryTransformation();
    var salesTax = shared_1.createProductSalesTaxTransformation();
    var shipping = shared_1.createProductShippingTransformation();
    var upsells = shared_1.createProductUpSellsTransformation();
    var variable = shared_1.createProductVariableTransformation();
    var transformations = __spreadArrays(crossSells, inventory, salesTax, shipping, upsells, variable);
    var transformer = shared_1.createProductTransformer('variable', transformations);
    return new framework_1.ModelRepository(shared_2.restList(models_1.baseProductURL, models_1.VariableProduct, httpClient, transformer), shared_2.restCreate(models_1.baseProductURL, models_1.VariableProduct, httpClient, transformer), shared_2.restRead(models_1.buildProductURL, models_1.VariableProduct, httpClient, transformer), shared_2.restUpdate(models_1.buildProductURL, models_1.VariableProduct, httpClient, transformer), shared_2.restDelete(models_1.deleteProductURL, httpClient));
}
exports.variableProductRESTRepository = variableProductRESTRepository;
