"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("../shared");
var transformer_1 = require("./transformer");
/**
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 */
function orderRESTRepository(httpClient) {
    var buildURL = function (id) { return '/wc/v3/orders/' + id; };
    // Using `?force=true` permanently deletes the order
    var buildDeleteUrl = function (id) { return "/wc/v3/orders/" + id + "?force=true"; };
    var transformer = transformer_1.createOrderTransformer();
    return new framework_1.ModelRepository(shared_1.restList(function () { return '/wc/v3/orders'; }, models_1.Order, httpClient, transformer), shared_1.restCreate(function () { return '/wc/v3/orders'; }, models_1.Order, httpClient, transformer), shared_1.restRead(buildURL, models_1.Order, httpClient, transformer), shared_1.restUpdate(buildURL, models_1.Order, httpClient, transformer), shared_1.restDelete(buildDeleteUrl, httpClient));
}
exports.default = orderRESTRepository;
