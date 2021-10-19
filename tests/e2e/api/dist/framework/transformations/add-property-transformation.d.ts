import { ModelTransformation, TransformationOrder } from '../model-transformer';
/**
 * @typedef AdditionalProperties
 * @alias Object.<string,string>
 */
declare type AdditionalProperties = {
    [key: string]: any;
};
/**
 * A model transformation that adds a property with
 * a default value if it is not already set.
 */
export declare class AddPropertyTransformation implements ModelTransformation {
    readonly fromModelOrder = TransformationOrder.Normal;
    /**
     *The additional properties to add when executing toModel.
     *
     * @type {AdditionalProperties}
     * @private
     */
    private readonly toProperties;
    /**
     * The additional properties to add when executing fromModel.
     *
     * @type {AdditionalProperties}
     * @private
     */
    private readonly fromProperties;
    /**
     * Creates a new transformation.
     *
     * @param {AdditionalProperties} toProperties The properties to add when executing toModel.
     * @param {AdditionalProperties} fromProperties The properties to add when executing fromModel.
     */
    constructor(toProperties: AdditionalProperties, fromProperties: AdditionalProperties);
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
//# sourceMappingURL=add-property-transformation.d.ts.map