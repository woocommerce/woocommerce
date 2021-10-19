"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AxiosClient = void 0;
var axios_1 = require("axios");
var axios_response_interceptor_1 = require("./axios-response-interceptor");
/**
 * An HTTPClient implementation that uses Axios to make requests.
 */
var AxiosClient = /** @class */ (function () {
    /**
     * Creates a new axios client.
     *
     * @param {AxiosRequestConfig} config The request configuration.
     * @param {AxiosInterceptor[]} extraInterceptors An array of additional interceptors to apply to the client.
     */
    function AxiosClient(config, extraInterceptors) {
        if (extraInterceptors === void 0) { extraInterceptors = []; }
        this.client = axios_1.default.create(config);
        this.interceptors = extraInterceptors;
        // The response interceptor needs to be last to prevent the other interceptors from
        // receiving the transformed HTTPResponse type instead of an AxiosResponse.
        this.interceptors.push(new axios_response_interceptor_1.AxiosResponseInterceptor());
        for (var _i = 0, _a = this.interceptors; _i < _a.length; _i++) {
            var interceptor = _a[_i];
            interceptor.start(this.client);
        }
    }
    /**
     * Performs a GET request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} params Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    AxiosClient.prototype.get = function (path, params) {
        return this.client.get(path, { params: params });
    };
    /**
     * Performs a POST request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    AxiosClient.prototype.post = function (path, data) {
        return this.client.post(path, data);
    };
    /**
     * Performs a PUT request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    AxiosClient.prototype.put = function (path, data) {
        return this.client.put(path, data);
    };
    /**
     * Performs a PATCH request.
     *
     * @param {string} path The path we should query.
     * @param {*} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    AxiosClient.prototype.patch = function (path, data) {
        return this.client.patch(path, data);
    };
    /**
     * Performs a DELETE request.
     *
     * @param {string} path The path we should send the request to.
     * @param {*} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    AxiosClient.prototype.delete = function (path, data) {
        return this.client.delete(path, { data: data });
    };
    return AxiosClient;
}());
exports.AxiosClient = AxiosClient;
