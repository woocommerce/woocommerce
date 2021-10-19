"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.HTTPClientFactory = void 0;
var axios_1 = require("./axios");
var axios_url_to_query_interceptor_1 = require("./axios/axios-url-to-query-interceptor");
/**
 * A factory for generating an HTTPClient with a desired configuration.
 */
var HTTPClientFactory = /** @class */ (function () {
    function HTTPClientFactory(wpURL) {
        this.clientConfig = { wpURL: wpURL };
    }
    /**
     * Creates a new factory that can be used to build clients.
     *
     * @param {string} wpURL The root URL of the WordPress installation we're querying.
     * @return {HTTPClientFactory} The new factory instance.
     */
    HTTPClientFactory.build = function (wpURL) {
        return new HTTPClientFactory(wpURL);
    };
    /**
     * Configures the client to utilize OAuth.
     *
     * @param {string} key The OAuth consumer key to use.
     * @param {string} secret The OAuth consumer secret to use.
     * @return {HTTPClientFactory} This factory.
     */
    HTTPClientFactory.prototype.withOAuth = function (key, secret) {
        this.clientConfig.auth = { type: 'oauth', key: key, secret: secret };
        return this;
    };
    /**
     * Configures the client to utilize basic auth.
     *
     * @param {string} username The WordPress username to use.
     * @param {string} password The password for the WordPress user.
     * @return {HTTPClientFactory} This factory.
     */
    HTTPClientFactory.prototype.withBasicAuth = function (username, password) {
        this.clientConfig.auth = { type: 'basic', username: username, password: password };
        return this;
    };
    /**
     * Configures the client to use index permalinks.
     *
     * @return {HTTPClientFactory} This factory.
     */
    HTTPClientFactory.prototype.withIndexPermalinks = function () {
        this.clientConfig.useIndexPermalinks = true;
        return this;
    };
    /**
     * Configures the client to use query permalinks.
     *
     * @return {HTTPClientFactory} This factory.
     */
    HTTPClientFactory.prototype.withoutIndexPermalinks = function () {
        this.clientConfig.useIndexPermalinks = false;
        return this;
    };
    /**
     * Creates a client instance using the configuration stored within.
     *
     * @return {HTTPClient} The created client.
     */
    HTTPClientFactory.prototype.create = function () {
        var axiosConfig = {};
        var interceptors = [];
        axiosConfig.baseURL = this.clientConfig.wpURL;
        if (!axiosConfig.baseURL.endsWith('/')) {
            axiosConfig.baseURL += '/';
        }
        if (this.clientConfig.useIndexPermalinks) {
            axiosConfig.baseURL += 'wp-json/';
        }
        else {
            interceptors.push(new axios_url_to_query_interceptor_1.AxiosURLToQueryInterceptor('rest_route'));
        }
        if (this.clientConfig.auth) {
            switch (this.clientConfig.auth.type) {
                case 'basic':
                    axiosConfig.auth = {
                        username: this.clientConfig.auth.username,
                        password: this.clientConfig.auth.password,
                    };
                    break;
                case 'oauth':
                    interceptors.push(new axios_1.AxiosOAuthInterceptor(this.clientConfig.auth.key, this.clientConfig.auth.secret));
                    break;
            }
        }
        return new axios_1.AxiosClient(axiosConfig, interceptors);
    };
    return HTTPClientFactory;
}());
exports.HTTPClientFactory = HTTPClientFactory;
