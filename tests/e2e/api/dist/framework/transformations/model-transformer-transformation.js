"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ModelTransformerTransformation = void 0;
var model_transformer_1 = require("../model-transformer");
/**
 * A model transformation that applies another transformer to a property.
 *
 * @template T
 */
var ModelTransformerTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {string} property The property we want to apply the transformer to.
     * @param {ModelConstructor.<T>} modelClass The model to transform into.
     * @param {ModelTransformer} transformer The transformer we want to apply.
     * @template T
     */
    function ModelTransformerTransformation(property, modelClass, transformer) {
        this.fromModelOrder = model_transformer_1.TransformationOrder.Normal;
        this.property = property;
        this.modelClass = modelClass;
        this.transformer = transformer;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    ModelTransformerTransformation.prototype.fromModel = function (properties) {
        var _this = this;
        var val = properties[this.property];
        if (val) {
            if (Array.isArray(val)) {
                properties[this.property] = val.map(function (v) { return _this.transformer.fromModel(v); });
            }
            else {
                properties[this.property] = this.transformer.fromModel(val);
            }
        }
        return properties;
    };
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    ModelTransformerTransformation.prototype.toModel = function (properties) {
        var _this = this;
        var val = properties[this.property];
        if (val) {
            if (Array.isArray(val)) {
                properties[this.property] = val.map(function (v) { return _this.transformer.toModel(_this.modelClass, v); });
            }
            else {
                properties[this.property] = this.transformer.toModel(this.modelClass, val);
            }
        }
        return properties;
    };
    return ModelTransformerTransformation;
}());
exports.ModelTransformerTransformation = ModelTransformerTransformation;
