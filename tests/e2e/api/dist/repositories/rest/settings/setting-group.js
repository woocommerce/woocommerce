"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("../shared");
function createTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.KeyChangeTransformation({ parentID: 'parent_id' }),
    ]);
}
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettingGroups} The created repository.
 */
function settingGroupRESTRepository(httpClient) {
    var transformer = createTransformer();
    return new framework_1.ModelRepository(shared_1.restList(function () { return '/wc/v3/settings'; }, models_1.SettingGroup, httpClient, transformer), null, null, null, null);
}
exports.default = settingGroupRESTRepository;
