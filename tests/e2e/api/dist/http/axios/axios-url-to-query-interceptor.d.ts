import { AxiosInterceptor } from './axios-interceptor';
import { AxiosRequestConfig } from 'axios';
/**
 * An interceptor for transforming the request's path into a query parameter.
 */
export declare class AxiosURLToQueryInterceptor extends AxiosInterceptor {
    /**
     * The query parameter we want to assign the path to.
     *
     * @type {string}
     * @private
     */
    private readonly queryParam;
    /**
     * Constructs a new interceptor.
     *
     * @param {string} queryParam The query parameter we want to assign the path to.
     */
    constructor(queryParam: string);
    /**
     * Converts the outgoing path into a query parameter.
     *
     * @param {AxiosRequestConfig} config The axios config.
     * @return {AxiosRequestConfig} The axios config.
     */
    protected handleRequest(config: AxiosRequestConfig): AxiosRequestConfig;
}
//# sourceMappingURL=axios-url-to-query-interceptor.d.ts.map