"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.KeyChangeTransformation = void 0;
var model_transformer_1 = require("../model-transformer");
/**
 * A model transformation that can be used to change property keys between two formats.
 * This transformation has a very high priority so that it will be executed after all
 * other transformations to prevent the changed key from causing problems.
 */
var KeyChangeTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {KeyChanges} changes The changes we want the transformation to make.
     */
    function KeyChangeTransformation(changes) {
        /**
         * Ensure that this transformation always happens at the very end since it changes the keys
         * in the transformed object.
         */
        this.fromModelOrder = model_transformer_1.TransformationOrder.VeryLast + 1;
        this.changes = changes;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    KeyChangeTransformation.prototype.fromModel = function (properties) {
        for (var key in this.changes) {
            var value = this.changes[key];
            if (!properties.hasOwnProperty(key)) {
                continue;
            }
            properties[value] = properties[key];
            delete properties[key];
        }
        return properties;
    };
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    KeyChangeTransformation.prototype.toModel = function (properties) {
        for (var key in this.changes) {
            var value = this.changes[key];
            if (!properties.hasOwnProperty(value)) {
                continue;
            }
            properties[key] = properties[value];
            delete properties[value];
        }
        return properties;
    };
    return KeyChangeTransformation;
}());
exports.KeyChangeTransformation = KeyChangeTransformation;
