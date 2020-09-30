import { Model, ModelID, ModelParentID } from '../model';

/**
 * The default types of settings that are available.
 */
type SettingType = 'text' | 'select' | 'multiselect' | 'checkbox' | 'number';

/**
 * An interface describing the shape of setting parent data.
 *
 * @typedef SettingParentID
 * @property {ModelID} settingGroupID The ID of the setting group for the setting.
 */
export interface SettingParentID extends ModelParentID {
	settingGroupID: ModelID;
}

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
}
