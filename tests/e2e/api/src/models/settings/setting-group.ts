import { Model, ModelID } from '../model';

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
	public constructor( properties: Partial< SettingGroup > = {} ) {
		super();
		Object.assign( this, properties );
	}
}
