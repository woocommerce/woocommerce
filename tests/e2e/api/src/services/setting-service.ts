import { Setting, UpdatesSettings } from '../models';

/**
 * A service that wraps setting changes in convenient methods.
 */
export class SettingService {
	/**
	 * The repository that will be used to change the settings.
	 *
	 * @type {UpdatesSettings}
	 * @private
	 */
	private readonly repository: UpdatesSettings;

	/**
	 * Creates a new service class for easily changing store settings.
	 *
	 * @param {UpdatesSettings} repository The repository that will be used to change the settings.
	 */
	public constructor( repository: UpdatesSettings ) {
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
	public updateStoreAddress( address1: string, address2: string, city: string, country: string, postCode: string ): Promise< boolean > {
		const promises: Promise< Setting >[] = [];

		promises.push( this.repository.update( 'general', 'woocommerce_store_address', { value: address1 } ) );
		promises.push( this.repository.update( 'general', 'woocommerce_store_address_2', { value: address2 } ) );
		promises.push( this.repository.update( 'general', 'woocommerce_store_city', { value: city } ) );
		promises.push( this.repository.update( 'general', 'woocommerce_default_country', { value: country } ) );
		promises.push( this.repository.update( 'general', 'woocommerce_store_postcode', { value: postCode } ) );

		return Promise.all( promises ).then( () => true );
	}
}
