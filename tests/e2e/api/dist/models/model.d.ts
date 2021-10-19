/**
 * The ID of a model.
 *
 * @typedef ModelID
 * @alias string|number
 */
export declare type ModelID = string | number;
/**
 * The base class for all models.
 */
export declare abstract class Model {
    /**
     * The ID of the model if it exists.
     *
     * @type {string|number|null}
     */
    readonly id: ModelID | undefined;
}
//# sourceMappingURL=model.d.ts.map