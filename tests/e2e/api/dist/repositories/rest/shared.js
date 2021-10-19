"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.restDeleteChild = exports.restDelete = exports.restUpdateChild = exports.restUpdate = exports.restReadChild = exports.restRead = exports.restCreateChild = exports.restCreate = exports.restListChild = exports.restList = exports.createMetaDataTransformer = void 0;
var framework_1 = require("../../framework");
/**
 * Creates a new transformer for metadata models.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createMetaDataTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.IgnorePropertyTransformation(['id']),
        new framework_1.KeyChangeTransformation({
            displayKey: 'display_key',
            displayValue: 'display_value',
        }),
    ]);
}
exports.createMetaDataTransformer = createMetaDataTransformer;
/**
 * Creates a callback for listing models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListFn} The callback for the repository.
 */
function restList(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (params) { return __awaiter(_this, void 0, void 0, function () {
        var response, list, _i, _a, raw;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0: return [4 /*yield*/, httpClient.get(buildURL(), params)];
                case 1:
                    response = _b.sent();
                    list = [];
                    for (_i = 0, _a = response.data; _i < _a.length; _i++) {
                        raw = _a[_i];
                        list.push(transformer.toModel(modelClass, raw));
                    }
                    return [2 /*return*/, Promise.resolve(list)];
            }
        });
    }); };
}
exports.restList = restList;
/**
 * Creates a callback for listing child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListChildFn} The callback for the repository.
 */
function restListChild(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (parent, params) { return __awaiter(_this, void 0, void 0, function () {
        var response, list, _i, _a, raw;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0: return [4 /*yield*/, httpClient.get(buildURL(parent), params)];
                case 1:
                    response = _b.sent();
                    list = [];
                    for (_i = 0, _a = response.data; _i < _a.length; _i++) {
                        raw = _a[_i];
                        list.push(transformer.toModel(modelClass, raw));
                    }
                    return [2 /*return*/, Promise.resolve(list)];
            }
        });
    }); };
}
exports.restListChild = restListChild;
/**
 * Creates a callback for creating models using the REST API.
 *
 * @param {Function} buildURL A callback to build the URL. (This is passed the properties for the new model.)
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {CreateFn} The callback for the repository.
 */
function restCreate(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (properties) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.post(buildURL(properties), transformer.fromModel(properties))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restCreate = restCreate;
/**
 * Creates a callback for creating child models using the REST API.
 *
 * @param {Function} buildURL A callback to build the URL. (This is passed the properties for the new model.)
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {CreateChildFn} The callback for the repository.
 */
function restCreateChild(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (parent, properties) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.post(buildURL(parent, properties), transformer.fromModel(properties))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restCreateChild = restCreateChild;
/**
 * Creates a callback for reading models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadFn} The callback for the repository.
 */
function restRead(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (id) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.get(buildURL(id))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restRead = restRead;
/**
 * Creates a callback for reading child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadChildFn} The callback for the repository.
 */
function restReadChild(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (parent, id) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.get(buildURL(parent, id))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restReadChild = restReadChild;
/**
 * Creates a callback for updating models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateFn} The callback for the repository.
 */
function restUpdate(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (id, params) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.patch(buildURL(id), transformer.fromModel(params))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restUpdate = restUpdate;
/**
 * Creates a callback for updating child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateChildFn} The callback for the repository.
 */
function restUpdateChild(buildURL, modelClass, httpClient, transformer) {
    var _this = this;
    return function (parent, id, params) { return __awaiter(_this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4 /*yield*/, httpClient.patch(buildURL(parent, id), transformer.fromModel(params))];
                case 1:
                    response = _a.sent();
                    return [2 /*return*/, Promise.resolve(transformer.toModel(modelClass, response.data))];
            }
        });
    }); };
}
exports.restUpdateChild = restUpdateChild;
/**
 * Creates a callback for deleting models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteFn} The callback for the repository.
 */
function restDelete(buildURL, httpClient) {
    return function (id) {
        return httpClient.delete(buildURL(id)).then(function () { return true; });
    };
}
exports.restDelete = restDelete;
/**
 * Creates a callback for deleting child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteChildFn} The callback for the repository.
 */
function restDeleteChild(buildURL, httpClient) {
    return function (parent, id) {
        return httpClient.delete(buildURL(parent, id)).then(function () { return true; });
    };
}
exports.restDeleteChild = restDeleteChild;
