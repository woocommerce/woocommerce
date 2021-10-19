"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ModelTransformer = exports.TransformationOrder = void 0;
/**
 * An enum for defining the "toModel" transformation order values.
 */
var TransformationOrder;
(function (TransformationOrder) {
    TransformationOrder[TransformationOrder["First"] = 0] = "First";
    TransformationOrder[TransformationOrder["Normal"] = 500000] = "Normal";
    TransformationOrder[TransformationOrder["Last"] = 1000000] = "Last";
    /**
     * A special value reserved for transformations that MUST come after all orders due to
     * the way that they destroy the property keys or values.
     */
    TransformationOrder[TransformationOrder["VeryLast"] = 2000000] = "VeryLast";
})(TransformationOrder = exports.TransformationOrder || (exports.TransformationOrder = {}));
/**
 * A class for transforming models to/from a generic representation.
 */
var ModelTransformer = /** @class */ (function () {
    /**
     * Creates a new model transformer instance.
     *
     * @param {Array.<ModelTransformation>} transformations The transformations to use.
     */
    function ModelTransformer(transformations) {
        // Ensure that the transformations are sorted by priority.
        transformations.sort(function (a, b) { return (a.fromModelOrder > b.fromModelOrder) ? 1 : -1; });
        this.transformations = transformations;
    }
    /**
     * Takes the input model and runs all of the transformations on it before returning the data.
     *
     * @param {Partial.<T>} model The model to transform.
     * @return {*} The transformed data.
     * @template T
     */
    ModelTransformer.prototype.fromModel = function (model) {
        // Convert the model class to raw properties so that the transformations can be simple.
        var raw = Object.assign({}, model);
        return this.transformations.reduce(function (properties, transformer) {
            return transformer.fromModel(properties);
        }, raw);
    };
    /**
     * Takes the input data and runs all of the transformations on it before returning the created model.
     *
     * @param {Function.<T>} modelClass The model class we're trying to create.
     * @param {*} data The data we're transforming.
     * @return {T} The transformed model.
     * @template T
     */
    ModelTransformer.prototype.toModel = function (modelClass, data) {
        var transformed = this.transformations.reduceRight(function (properties, transformer) {
            return transformer.toModel(properties);
        }, data);
        return new modelClass(transformed);
    };
    return ModelTransformer;
}());
exports.ModelTransformer = ModelTransformer;
