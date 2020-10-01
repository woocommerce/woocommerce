import { Model } from '../model';
import { HTTPClient } from '../../http';
import { settingRESTRepository } from '../../repositories/rest/settings/setting';

/**
 * The default types of settings that are available.
 */
type SettingType = 'text' | 'select' | 'multiselect' | 'checkbox' | 'number';

/**
 * A setting object.
 */
export class Setting extends Model {
	/**
	 * The label of the setting.
	 *
	 * @type {string}
	 */
	public readonly label: string = '';

	/**
	 * The description of the setting.
	 *
	 * @type {string}
	 */
	public readonly description: string = '';

	/**
	 * The type of the setting.
	 *
	 * @type {string}
	 */
	public readonly type: string | SettingType = '';

	/**
	 * The options of the setting, if it has any.
	 *
	 * @type {Object.<string, string>|null}
	 */
	public readonly options: { [key: string]: string } | null = null;

	/**
	 * The default value for the setting.
	 *
	 * @type {string}
	 */
	public readonly default: string = '';

	/**
	 * The current value of the setting.
	 *
	 * @type {string}
	 */
	public readonly value: string = '';

	/**
	 * Creates a new setting instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties: Partial< Setting > = {} ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Returns the repository for interacting with this type of model.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof settingRESTRepository > {
		return settingRESTRepository( httpClient );
	}
}
