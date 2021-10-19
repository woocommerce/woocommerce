import { HTTPClient, HTTPResponse } from '../http-client';
import { AxiosRequestConfig } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
/**
 * An HTTPClient implementation that uses Axios to make requests.
 */
export declare class AxiosClient implements HTTPClient {
    /**
     * An instance of the Axios client for making HTTP requests.
     *
     * @type {AxiosInstance}
     * @private
     */
    private readonly client;
    /**
     * An array of interceptors that should be applied to the client.
     *
     * @type {AxiosInterceptor[]}
     * @private
     */
    private readonly interceptors;
    /**
     * Creates a new axios client.
     *
     * @param {AxiosRequestConfig} config The request configuration.
     * @param {AxiosInterceptor[]} extraInterceptors An array of additional interceptors to apply to the client.
     */
    constructor(config: AxiosRequestConfig, extraInterceptors?: AxiosInterceptor[]);
    /**
     * Performs a GET request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} params Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    get<T = any>(path: string, params?: object): Promise<HTTPResponse<T>>;
    /**
     * Performs a POST request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    post<T = any>(path: string, data?: object): Promise<HTTPResponse<T>>;
    /**
     * Performs a PUT request.
     *
     * @param {string} path The path we should send the request to.
     * @param {Object} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    put<T = any>(path: string, data?: object): Promise<HTTPResponse<T>>;
    /**
     * Performs a PATCH request.
     *
     * @param {string} path The path we should query.
     * @param {*} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    patch<T = any>(path: string, data?: object): Promise<HTTPResponse<T>>;
    /**
     * Performs a DELETE request.
     *
     * @param {string} path The path we should send the request to.
     * @param {*} data Any parameters that should be passed in the request.
     * @return {Promise.<HTTPResponse>} The response from the API.
     */
    delete<T = any>(path: string, data?: object): Promise<HTTPResponse<T>>;
}
//# sourceMappingURL=axios-client.d.ts.map