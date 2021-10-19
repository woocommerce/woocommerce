import { AxiosResponse } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
import { HTTPResponse } from '../http-client';
/**
 * An interceptor for transforming the responses from axios into a consistent format for package consumers.
 */
export declare class AxiosResponseInterceptor extends AxiosInterceptor {
    /**
     * Transforms the Axios response into our HTTP response.
     *
     * @param {AxiosResponse} response The response that we need to transform.
     * @return {HTTPResponse} The HTTP response.
     */
    protected onResponseSuccess(response: AxiosResponse): HTTPResponse;
    /**
     * Axios throws HTTP errors so we need to eat those errors and pass them normally.
     *
     * @param {*} error The error that was caught.
     */
    protected onResponseRejected(error: any): never;
}
//# sourceMappingURL=axios-response-interceptor.d.ts.map