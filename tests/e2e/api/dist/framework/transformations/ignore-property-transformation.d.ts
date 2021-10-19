import { ModelTransformation, TransformationOrder } from '../model-transformer';
export declare class IgnorePropertyTransformation implements ModelTransformation {
    readonly fromModelOrder = TransformationOrder.Normal;
    /**
     * A list of properties that should be removed.
     *
     * @type {Array.<string>}
     * @private
     */
    private readonly ignoreList;
    /**
     * Creates a new transformation.
     *
     * @param {Array.<string>} ignoreList The properties to ignore.
     */
    constructor(ignoreList: string[]);
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
//# sourceMappingURL=ignore-property-transformation.d.ts.map