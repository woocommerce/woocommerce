"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.HTTPResponse = void 0;
/**
 * A structured response from the HTTP client.
 */
var HTTPResponse = /** @class */ (function () {
    /**
     * Creates a new HTTP response instance.
     *
     * @param {number} statusCode The status code from the HTTP response.
     * @param {Object.<string,string|string[]>} headers The headers from the HTTP response.
     * @param {Object} data The data from the HTTP response.
     */
    function HTTPResponse(statusCode, headers, data) {
        this.statusCode = statusCode;
        this.headers = headers;
        this.data = data;
    }
    return HTTPResponse;
}());
exports.HTTPResponse = HTTPResponse;
