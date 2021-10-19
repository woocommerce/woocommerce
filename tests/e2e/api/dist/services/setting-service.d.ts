import { UpdatesSettings } from '../models';
/**
 * A service that wraps setting changes in convenient methods.
 */
export declare class SettingService {
    /**
     * The repository that will be used to change the settings.
     *
     * @type {UpdatesSettings}
     * @private
     */
    private readonly repository;
    /**
     * Creates a new service class for easily changing store settings.
     *
     * @param {UpdatesSettings} repository The repository that will be used to change the settings.
     */
    constructor(repository: UpdatesSettings);
    /**
     * Updates the address for the store.
     *
     * @param {string} address1 The first address line.
     * @param {string} address2 The second address line.
     * @param {string} city The city.
     * @param {string} country The country or country/state.
     * @param {string} postCode The postal code.
     * @return {Promise.<boolean>} Resolves to true if all of the settings are updated.
     */
    updateStoreAddress(address1: string, address2: string, city: string, country: string, postCode: string): Promise<boolean>;
}
//# sourceMappingURL=setting-service.d.ts.map