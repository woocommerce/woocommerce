import { ModelTransformation } from '../model-transformer';
/**
 * A callback for transforming model properties.
 *
 * @callback TransformationCallback
 * @param {*} properties The properties to transform.
 * @return {*} The transformed properties.
 */
declare type TransformationCallback = (properties: any) => any;
/**
 * A model transformer for executing arbitrary callbacks on input properties.
 */
export declare class CustomTransformation implements ModelTransformation {
    readonly fromModelOrder: number;
    /**
     * The hook to run for toModel.
     *
     * @type {TransformationCallback|null}
     * @private
     */
    private readonly toHook;
    /**
     * The hook to run for fromModel.
     *
     * @type {TransformationCallback|null}
     * @private
     */
    private readonly fromHook;
    /**
     * Creates a new transformation.
     *
     * @param {number} order The order for the transformation.
     * @param {TransformationCallback|null} toHook The hook to run for toModel.
     * @param {TransformationCallback|null} fromHook The hook to run for fromModel.
     */
    constructor(order: number, toHook: TransformationCallback | null, fromHook: TransformationCallback | null);
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    fromModel(properties: any): any;
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    toModel(properties: any): any;
}
export {};
//# sourceMappingURL=custom-transformation.d.ts.map