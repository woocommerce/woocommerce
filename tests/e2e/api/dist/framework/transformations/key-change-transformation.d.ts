import { ModelTransformation } from '../model-transformer';
import { Model } from '../../models';
/**
 * @typedef KeyChanges
 * @alias Object.<string,string>
 */
declare type KeyChanges<T extends Model> = {
    readonly [key in keyof Partial<T>]: string;
};
/**
 * A model transformation that can be used to change property keys between two formats.
 * This transformation has a very high priority so that it will be executed after all
 * other transformations to prevent the changed key from causing problems.
 */
export declare class KeyChangeTransformation<T extends Model> implements ModelTransformation {
    /**
     * Ensure that this transformation always happens at the very end since it changes the keys
     * in the transformed object.
     */
    readonly fromModelOrder: number;
    /**
     * The key change transformations that this object should perform.
     * This is structured with the model's property key as the key
     * of the object and the raw property key as the value.
     *
     * @type {KeyChanges}
     * @private
     */
    private readonly changes;
    /**
     * Creates a new transformation.
     *
     * @param {KeyChanges} changes The changes we want the transformation to make.
     */
    constructor(changes: KeyChanges<T>);
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
//# sourceMappingURL=key-change-transformation.d.ts.map