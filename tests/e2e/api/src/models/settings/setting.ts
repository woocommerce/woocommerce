import { Model, ModelID } from '../model';
import { HTTPClient } from '../../http';
import { settingRESTRepository } from '../../repositories/rest/settings/setting';
import {
	ModelRepositoryParams,
	ListsChildModels,
	ReadsChildModels,
	UpdatesChildModels,
} from '../../framework/model-repository';

/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type SettingRepositoryParams = ModelRepositoryParams< Setting, ModelID, never, 'value' >;

/**
 * An interface for listing settings using the repository.
 *
 * @typedef ListsSettings
 * @alias ListsChildModels.<Setting,SettingParentID>
 */
export type ListsSettings = ListsChildModels< SettingRepositoryParams >;

/**
 * An interface for reading settings using the repository.
 *
 * @typedef ReadsSettings
 * @alias ReadsChildModels.<Setting,SettingParentID>
 */
export type ReadsSettings = ReadsChildModels< SettingRepositoryParams >;

/**
 * An interface for updating settings using the repository.
 *
 * @typedef UpdatesSettings
 * @alias UpdatesChildModels.<Setting,SettingParentID>
 */
export type UpdatesSettings = UpdatesChildModels< SettingRepositoryParams >;

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
	public readonly options: { [key: string]: string } | undefined;

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
	public constructor( properties?: Partial< Setting > ) {
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
