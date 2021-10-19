import { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
/**
 * A base class for encapsulating the start and stop functionality required by all Axios interceptors.
 */
export declare abstract class AxiosInterceptor {
    /**
     * An array of the active interceptor records for all of the clients this interceptor is attached to.
     *
     * @type {ActiveInterceptor[]}
     * @private
     */
    private readonly activeInterceptors;
    /**
     * Starts intercepting requests and responses.
     *
     * @param {AxiosInstance} client The client to start intercepting the requests/responses of.
     */
    start(client: AxiosInstance): void;
    /**
     * Stops intercepting requests and responses.
     *
     * @param {AxiosInstance} client The client to stop intercepting the requests/responses of.
     */
    stop(client: AxiosInstance): void;
    /**
     * An interceptor method for handling requests before they are made to the server.
     *
     * @param {AxiosRequestConfig} config The Axios request options.
     */
    protected handleRequest(config: AxiosRequestConfig): AxiosRequestConfig;
    /**
     * An interceptor method for handling successful responses.
     *
     * @param {*} response The response from the Axios client.
     */
    protected onResponseSuccess(response: AxiosResponse): any;
    /**
     * An interceptor method for handling response failures.
     *
     * @param {Promise} error The error that occurred.
     */
    protected onResponseRejected(error: any): any;
}
//# sourceMappingURL=axios-interceptor.d.ts.map