"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.CustomTransformation = void 0;
/**
 * A model transformer for executing arbitrary callbacks on input properties.
 */
var CustomTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {number} order The order for the transformation.
     * @param {TransformationCallback|null} toHook The hook to run for toModel.
     * @param {TransformationCallback|null} fromHook The hook to run for fromModel.
     */
    function CustomTransformation(order, toHook, fromHook) {
        this.fromModelOrder = order;
        this.toHook = toHook;
        this.fromHook = fromHook;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    CustomTransformation.prototype.fromModel = function (properties) {
        if (!this.fromHook) {
            return properties;
        }
        return this.fromHook(properties);
    };
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    CustomTransformation.prototype.toModel = function (properties) {
        if (!this.toHook) {
            return properties;
        }
        return this.toHook(properties);
    };
    return CustomTransformation;
}());
exports.CustomTransformation = CustomTransformation;
