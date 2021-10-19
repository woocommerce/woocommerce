"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AddPropertyTransformation = void 0;
var model_transformer_1 = require("../model-transformer");
/**
 * A model transformation that adds a property with
 * a default value if it is not already set.
 */
var AddPropertyTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {AdditionalProperties} toProperties The properties to add when executing toModel.
     * @param {AdditionalProperties} fromProperties The properties to add when executing fromModel.
     */
    function AddPropertyTransformation(toProperties, fromProperties) {
        this.fromModelOrder = model_transformer_1.TransformationOrder.Normal;
        this.toProperties = toProperties;
        this.fromProperties = fromProperties;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    AddPropertyTransformation.prototype.fromModel = function (properties) {
        for (var key in this.fromProperties) {
            if (properties.hasOwnProperty(key)) {
                continue;
            }
            properties[key] = this.fromProperties[key];
        }
        return properties;
    };
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    AddPropertyTransformation.prototype.toModel = function (properties) {
        for (var key in this.toProperties) {
            if (properties.hasOwnProperty(key)) {
                continue;
            }
            properties[key] = this.toProperties[key];
        }
        return properties;
    };
    return AddPropertyTransformation;
}());
exports.AddPropertyTransformation = AddPropertyTransformation;
