"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.buildURLWithParams = exports.buildURL = void 0;
// @ts-ignore
var buildFullPath = require("axios/lib/core/buildFullPath");
// @ts-ignore
var appendParams = require("axios/lib/helpers/buildURL");
/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
function buildURL(request) {
    return buildFullPath(request.baseURL, request.url);
}
exports.buildURL = buildURL;
/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request with the query parameters included.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
function buildURLWithParams(request) {
    return appendParams(buildURL(request), request.params, request.paramsSerializer);
}
exports.buildURLWithParams = buildURLWithParams;
