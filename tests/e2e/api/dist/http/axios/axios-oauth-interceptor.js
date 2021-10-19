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
exports.AxiosOAuthInterceptor = void 0;
var createHmac = require("create-hmac");
var OAuth = require("oauth-1.0a");
var axios_interceptor_1 = require("./axios-interceptor");
var utils_1 = require("./utils");
/**
 * An interceptor for adding OAuth 1.0a signatures to HTTP requests.
 */
var AxiosOAuthInterceptor = /** @class */ (function (_super) {
    __extends(AxiosOAuthInterceptor, _super);
    /**
     * Creates a new interceptor.
     *
     * @param {string} consumerKey The consumer key of the API key.
     * @param {string} consumerSecret The consumer secret of the API key.
     */
    function AxiosOAuthInterceptor(consumerKey, consumerSecret) {
        var _this = _super.call(this) || this;
        _this.oauth = new OAuth({
            consumer: {
                key: consumerKey,
                secret: consumerSecret,
            },
            signature_method: 'HMAC-SHA256',
            hash_function: function (base, key) {
                return createHmac('sha256', key).update(base).digest('base64');
            },
        });
        return _this;
    }
    /**
     * Adds WooCommerce API authentication details to the outgoing request.
     *
     * @param {AxiosRequestConfig} request The request that was intercepted.
     * @return {AxiosRequestConfig} The request with the additional authorization headers.
     */
    AxiosOAuthInterceptor.prototype.handleRequest = function (request) {
        var url = utils_1.buildURLWithParams(request);
        if (url.startsWith('https')) {
            request.auth = {
                username: this.oauth.consumer.key,
                password: this.oauth.consumer.secret,
            };
        }
        else {
            request.headers.Authorization = this.oauth.toHeader(this.oauth.authorize({
                url: url,
                method: request.method,
            })).Authorization;
        }
        return request;
    };
    return AxiosOAuthInterceptor;
}(axios_interceptor_1.AxiosInterceptor));
exports.AxiosOAuthInterceptor = AxiosOAuthInterceptor;
