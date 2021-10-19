import { AxiosRequestConfig } from 'axios';
/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
export declare function buildURL(request: AxiosRequestConfig): string;
/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request with the query parameters included.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
export declare function buildURLWithParams(request: AxiosRequestConfig): string;
//# sourceMappingURL=utils.d.ts.map