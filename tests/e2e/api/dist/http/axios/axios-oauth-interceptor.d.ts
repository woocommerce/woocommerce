import type { AxiosRequestConfig } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
/**
 * An interceptor for adding OAuth 1.0a signatures to HTTP requests.
 */
export declare class AxiosOAuthInterceptor extends AxiosInterceptor {
    /**
     * The OAuth class for signing the request.
     *
     * @type {Object}
     * @private
     */
    private readonly oauth;
    /**
     * Creates a new interceptor.
     *
     * @param {string} consumerKey The consumer key of the API key.
     * @param {string} consumerSecret The consumer secret of the API key.
     */
    constructor(consumerKey: string, consumerSecret: string);
    /**
     * Adds WooCommerce API authentication details to the outgoing request.
     *
     * @param {AxiosRequestConfig} request The request that was intercepted.
     * @return {AxiosRequestConfig} The request with the additional authorization headers.
     */
    protected handleRequest(request: AxiosRequestConfig): AxiosRequestConfig;
}
//# sourceMappingURL=axios-oauth-interceptor.d.ts.map