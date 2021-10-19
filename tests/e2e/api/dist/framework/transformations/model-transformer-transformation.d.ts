import { ModelTransformation, ModelTransformer, TransformationOrder } from '../model-transformer';
import { Model, ModelConstructor } from '../../models';
/**
 * A model transformation that applies another transformer to a property.
 *
 * @template T
 */
export declare class ModelTransformerTransformation<T extends Model> implements ModelTransformation {
    readonly fromModelOrder = TransformationOrder.Normal;
    /**
     * The property that the transformation should be applied to.
     *
     * @type {string}
     * @private
     */
    private readonly property;
    /**
     * The model class we want to transform into.
     *
     * @type {Function.<T>}
     * @private
     * @template T
     */
    private readonly modelClass;
    /**
     * The transformer that should be used.
     *
     * @type {ModelTransformer}
     * @private
     */
    private readonly transformer;
    /**
     * Creates a new transformation.
     *
     * @param {string} property The property we want to apply the transformer to.
     * @param {ModelConstructor.<T>} modelClass The model to transform into.
     * @param {ModelTransformer} transformer The transformer we want to apply.
     * @template T
     */
    constructor(property: string, modelClass: ModelConstructor<T>, transformer: ModelTransformer<T>);
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
//# sourceMappingURL=model-transformer-transformation.d.ts.map