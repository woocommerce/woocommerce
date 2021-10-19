import { HTTPClient } from './http-client';
/**
 * A factory for generating an HTTPClient with a desired configuration.
 */
export declare class HTTPClientFactory {
    /**
     * The configuration object describing the client we're trying to create.
     *
     * @private
     */
    private clientConfig;
    private constructor();
    /**
     * Creates a new factory that can be used to build clients.
     *
     * @param {string} wpURL The root URL of the WordPress installation we're querying.
     * @return {HTTPClientFactory} The new factory instance.
     */
    static build(wpURL: string): HTTPClientFactory;
    /**
     * Configures the client to utilize OAuth.
     *
     * @param {string} key The OAuth consumer key to use.
     * @param {string} secret The OAuth consumer secret to use.
     * @return {HTTPClientFactory} This factory.
     */
    withOAuth(key: string, secret: string): this;
    /**
     * Configures the client to utilize basic auth.
     *
     * @param {string} username The WordPress username to use.
     * @param {string} password The password for the WordPress user.
     * @return {HTTPClientFactory} This factory.
     */
    withBasicAuth(username: string, password: string): this;
    /**
     * Configures the client to use index permalinks.
     *
     * @return {HTTPClientFactory} This factory.
     */
    withIndexPermalinks(): this;
    /**
     * Configures the client to use query permalinks.
     *
     * @return {HTTPClientFactory} This factory.
     */
    withoutIndexPermalinks(): this;
    /**
     * Creates a client instance using the configuration stored within.
     *
     * @return {HTTPClient} The created client.
     */
    create(): HTTPClient;
}
//# sourceMappingURL=http-client-factory.d.ts.map