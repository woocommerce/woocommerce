"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("../shared");
function createTransformer() {
    return new framework_1.ModelTransformer([]);
}
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
function settingRESTRepository(httpClient) {
    var buildURL = function (parent, id) { return '/wc/v3/settings/' + parent + '/' + id; };
    var transformer = createTransformer();
    return new framework_1.ModelRepository(shared_1.restListChild(function (parent) { return '/wc/v3/settings/' + parent; }, models_1.Setting, httpClient, transformer), null, shared_1.restReadChild(buildURL, models_1.Setting, httpClient, transformer), shared_1.restUpdateChild(buildURL, models_1.Setting, httpClient, transformer), null);
}
exports.default = settingRESTRepository;
