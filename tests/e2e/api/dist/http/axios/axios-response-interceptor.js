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
exports.AxiosResponseInterceptor = void 0;
var axios_interceptor_1 = require("./axios-interceptor");
var http_client_1 = require("../http-client");
/**
 * An interceptor for transforming the responses from axios into a consistent format for package consumers.
 */
var AxiosResponseInterceptor = /** @class */ (function (_super) {
    __extends(AxiosResponseInterceptor, _super);
    function AxiosResponseInterceptor() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    /**
     * Transforms the Axios response into our HTTP response.
     *
     * @param {AxiosResponse} response The response that we need to transform.
     * @return {HTTPResponse} The HTTP response.
     */
    AxiosResponseInterceptor.prototype.onResponseSuccess = function (response) {
        return new http_client_1.HTTPResponse(response.status, response.headers, response.data);
    };
    /**
     * Axios throws HTTP errors so we need to eat those errors and pass them normally.
     *
     * @param {*} error The error that was caught.
     */
    AxiosResponseInterceptor.prototype.onResponseRejected = function (error) {
        // Convert HTTP response errors into a form that we can handle them with.
        if (error.response) {
            throw new http_client_1.HTTPResponse(error.response.status, error.response.headers, error.response.data);
        }
        throw error;
    };
    return AxiosResponseInterceptor;
}(axios_interceptor_1.AxiosInterceptor));
exports.AxiosResponseInterceptor = AxiosResponseInterceptor;
