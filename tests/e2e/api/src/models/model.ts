/**
 * The ID of a model.
 *
 * @typedef ModelID
 * @alias string|number
 */
export type ModelID = string | number;

/**
 * An interface for describing the shape of parent identifiers.
 *
 * @typedef ModelParentID
 * @alias Object.<string,ModelID>
 */
export interface ModelParentID {
	[ key: number ]: ModelID
}

/**
 * The base class for all models.
 */
export abstract class Model {
	/**
	 * The ID of the model if it exists.
	 *
	 * @type {string|number|null}
	 */
	public readonly id: ModelID | undefined;
}
