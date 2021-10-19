"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.SettingService = void 0;
/**
 * A service that wraps setting changes in convenient methods.
 */
var SettingService = /** @class */ (function () {
    /**
     * Creates a new service class for easily changing store settings.
     *
     * @param {UpdatesSettings} repository The repository that will be used to change the settings.
     */
    function SettingService(repository) {
        this.repository = repository;
    }
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
    SettingService.prototype.updateStoreAddress = function (address1, address2, city, country, postCode) {
        var promises = [];
        promises.push(this.repository.update('general', 'woocommerce_store_address', { value: address1 }));
        promises.push(this.repository.update('general', 'woocommerce_store_address_2', { value: address2 }));
        promises.push(this.repository.update('general', 'woocommerce_store_city', { value: city }));
        promises.push(this.repository.update('general', 'woocommerce_default_country', { value: country }));
        promises.push(this.repository.update('general', 'woocommerce_store_postcode', { value: postCode }));
        return Promise.all(promises).then(function () { return true; });
    };
    return SettingService;
}());
exports.SettingService = SettingService;
