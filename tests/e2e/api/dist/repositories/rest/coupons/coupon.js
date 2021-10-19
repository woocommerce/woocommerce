"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("../shared");
var transformer_1 = require("./transformer");
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * CreatesCoupons|
 * ListsCoupons|
 * ReadsCoupons|
 * UpdatesCoupons |
 * DeletesCoupons
 * } The created repository.
 */
function couponRESTRepository(httpClient) {
    var buildURL = function (id) { return '/wc/v3/coupons/' + id; };
    // Using `?force=true` permanently deletes the coupon
    var buildDeleteUrl = function (id) { return "/wc/v3/coupons/" + id + "?force=true"; };
    var transformer = transformer_1.createCouponTransformer();
    return new framework_1.ModelRepository(shared_1.restList(function () { return '/wc/v3/coupons'; }, models_1.Coupon, httpClient, transformer), shared_1.restCreate(function () { return '/wc/v3/coupons'; }, models_1.Coupon, httpClient, transformer), shared_1.restRead(buildURL, models_1.Coupon, httpClient, transformer), shared_1.restUpdate(buildURL, models_1.Coupon, httpClient, transformer), shared_1.restDelete(buildDeleteUrl, httpClient));
}
exports.default = couponRESTRepository;
