import { ModelTransformation, TransformationOrder } from '../model-transformer';
/**
 * An enum defining all of the property types that we might want to transform.
 *
 * @enum {number}
 */
export declare enum PropertyType {
    String = 0,
    Integer = 1,
    Float = 2,
    Boolean = 3,
    Date = 4
}
/**
 * A callback that can be used to transform property types.
 *
 * @callback PropertyTypeCallback
 * @param {*} value The value to transform.
 * @return {*} The transformed value.
 */
declare type PropertyTypeCallback = (value: any) => any;
/**
 * The types for all of a model's properties.
 *
 * @typedef PropertyTypes
 * @alias Object.<string,PropertyType>
 */
declare type PropertyTypes = {
    [key: string]: PropertyType | PropertyTypeCallback;
};
/**
 * A model transformer for converting property types between representation formats.
 */
export declare class PropertyTypeTransformation implements ModelTransformation {
    /**
     * We want the type transformation to take place after all of the others,
     * since they may be operating on internal data types.
     */
    readonly fromModelOrder = TransformationOrder.VeryLast;
    /**
     * The property types we will want to transform.
     *
     * @type {PropertyTypes}
     * @private
     */
    private readonly types;
    /**
     * Creates a new transformation.
     *
     * @param {PropertyTypes} types The property types we want to transform.
     */
    constructor(types: PropertyTypes);
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
    /**
     * Converts the given value into the requested type.
     *
     * @param {*} value The value to transform.
     * @param {PropertyType} type The type to transform it into.
     * @return {*} The converted type.
     * @private
     */
    private convertTo;
    /**
     * Converts the given type into a string.
     *
     * @param {*} value The value to transform.
     * @param {PropertyType} type The type to transform it into.
     * @return {*} The converted type.
     * @private
     */
    private convertFrom;
}
export {};
//# sourceMappingURL=property-type-transformation.d.ts.map