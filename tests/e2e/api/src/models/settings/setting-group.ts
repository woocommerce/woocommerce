import { Model, ModelID } from '../model';
import { HTTPClient } from '../../http';
import { settingGroupRESTRepository } from '../../repositories';
import { ListsModels, ModelRepositoryParams } from '../../framework';

/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type SettingGroupRepositoryParams = ModelRepositoryParams< SettingGroup >;

/**
 * An interface for listing setting groups using the repository.
 *
 * @typedef ListsSettingGroups
 * @alias ListsModels.<SettingGroup>
 */
export type ListsSettingGroups = ListsModels< SettingGroupRepositoryParams >;

/**
 * A settings group object.
 */
export class SettingGroup extends Model {
	/**
	 * The label of the setting group.
	 *
	 * @type {string}
	 */
	public readonly label: string = '';

	/**
	 * The description of the setting group.
	 *
	 * @type {string}
	 */
	public readonly description: string = '';

	/**
	 * The ID of the group this is a child of.
	 *
	 * @type {ModelID|null}
	 */
	public readonly parentID: ModelID | null = null;

	/**
	 * Creates a new setting group instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< SettingGroup > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Returns the repository for interacting with this type of model.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof settingGroupRESTRepository > {
		return settingGroupRESTRepository( httpClient );
	}
}
