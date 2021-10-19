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
exports.AxiosURLToQueryInterceptor = void 0;
var axios_interceptor_1 = require("./axios-interceptor");
var utils_1 = require("./utils");
/**
 * An interceptor for transforming the request's path into a query parameter.
 */
var AxiosURLToQueryInterceptor = /** @class */ (function (_super) {
    __extends(AxiosURLToQueryInterceptor, _super);
    /**
     * Constructs a new interceptor.
     *
     * @param {string} queryParam The query parameter we want to assign the path to.
     */
    function AxiosURLToQueryInterceptor(queryParam) {
        var _this = _super.call(this) || this;
        _this.queryParam = queryParam;
        return _this;
    }
    /**
     * Converts the outgoing path into a query parameter.
     *
     * @param {AxiosRequestConfig} config The axios config.
     * @return {AxiosRequestConfig} The axios config.
     */
    AxiosURLToQueryInterceptor.prototype.handleRequest = function (config) {
        var _a;
        var url = new URL(utils_1.buildURL(config));
        // Store the path in the query string.
        if (config.params instanceof URLSearchParams) {
            config.params.set(this.queryParam, url.pathname);
        }
        else if (config.params) {
            config.params[this.queryParam] = url.pathname;
        }
        else {
            config.params = (_a = {}, _a[this.queryParam] = url.pathname, _a);
        }
        // Store the URL without the path now that it's in the query string.
        url.pathname = '';
        config.url = url.toString();
        delete config.baseURL;
        return config;
    };
    return AxiosURLToQueryInterceptor;
}(axios_interceptor_1.AxiosInterceptor));
exports.AxiosURLToQueryInterceptor = AxiosURLToQueryInterceptor;
