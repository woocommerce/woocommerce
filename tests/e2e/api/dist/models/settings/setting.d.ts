import { Model, ModelID } from '../model';
import { HTTPClient } from '../../http';
import { settingRESTRepository } from '../../repositories';
import { ModelRepositoryParams, ListsChildModels, ReadsChildModels, UpdatesChildModels } from '../../framework';
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type SettingRepositoryParams = ModelRepositoryParams<Setting, ModelID, never, 'value'>;
/**
 * An interface for listing settings using the repository.
 *
 * @typedef ListsSettings
 * @alias ListsChildModels.<Setting,SettingParentID>
 */
export declare type ListsSettings = ListsChildModels<SettingRepositoryParams>;
/**
 * An interface for reading settings using the repository.
 *
 * @typedef ReadsSettings
 * @alias ReadsChildModels.<Setting,SettingParentID>
 */
export declare type ReadsSettings = ReadsChildModels<SettingRepositoryParams>;
/**
 * An interface for updating settings using the repository.
 *
 * @typedef UpdatesSettings
 * @alias UpdatesChildModels.<Setting,SettingParentID>
 */
export declare type UpdatesSettings = UpdatesChildModels<SettingRepositoryParams>;
/**
 * The default types of settings that are available.
 */
declare type SettingType = 'text' | 'select' | 'multiselect' | 'checkbox' | 'number';
/**
 * A setting object.
 */
export declare class Setting extends Model {
    /**
     * The label of the setting.
     *
     * @type {string}
     */
    readonly label: string;
    /**
     * The description of the setting.
     *
     * @type {string}
     */
    readonly description: string;
    /**
     * The type of the setting.
     *
     * @type {string}
     */
    readonly type: string | SettingType;
    /**
     * The options of the setting, if it has any.
     *
     * @type {Object.<string, string>|null}
     */
    readonly options: {
        [key: string]: string;
    } | undefined;
    /**
     * The default value for the setting.
     *
     * @type {string}
     */
    readonly default: string;
    /**
     * The current value of the setting.
     *
     * @type {string}
     */
    readonly value: string;
    /**
     * Creates a new setting instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<Setting>);
    /**
     * Returns the repository for interacting with this type of model.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof settingRESTRepository>;
}
export {};
//# sourceMappingURL=setting.d.ts.map