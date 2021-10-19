"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PropertyTypeTransformation = exports.PropertyType = void 0;
var model_transformer_1 = require("../model-transformer");
/**
 * An enum defining all of the property types that we might want to transform.
 *
 * @enum {number}
 */
var PropertyType;
(function (PropertyType) {
    PropertyType[PropertyType["String"] = 0] = "String";
    PropertyType[PropertyType["Integer"] = 1] = "Integer";
    PropertyType[PropertyType["Float"] = 2] = "Float";
    PropertyType[PropertyType["Boolean"] = 3] = "Boolean";
    PropertyType[PropertyType["Date"] = 4] = "Date";
})(PropertyType = exports.PropertyType || (exports.PropertyType = {}));
/**
 * A model transformer for converting property types between representation formats.
 */
var PropertyTypeTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {PropertyTypes} types The property types we want to transform.
     */
    function PropertyTypeTransformation(types) {
        /**
         * We want the type transformation to take place after all of the others,
         * since they may be operating on internal data types.
         */
        this.fromModelOrder = model_transformer_1.TransformationOrder.VeryLast;
        this.types = types;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    PropertyTypeTransformation.prototype.fromModel = function (properties) {
        for (var key in this.types) {
            if (!properties.hasOwnProperty(key)) {
                continue;
            }
            var value = properties[key];
            var type = this.types[key];
            if (type instanceof Function) {
                properties[key] = type(value);
                continue;
            }
            properties[key] = this.convertFrom(value, type);
        }
        return properties;
    };
    /**
     * Performs a transformation from raw properties to model properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    PropertyTypeTransformation.prototype.toModel = function (properties) {
        for (var key in this.types) {
            if (!properties.hasOwnProperty(key)) {
                continue;
            }
            var value = properties[key];
            var type = this.types[key];
            if (type instanceof Function) {
                properties[key] = type(value);
                continue;
            }
            properties[key] = this.convertTo(value, type);
        }
        return properties;
    };
    /**
     * Converts the given value into the requested type.
     *
     * @param {*} value The value to transform.
     * @param {PropertyType} type The type to transform it into.
     * @return {*} The converted type.
     * @private
     */
    PropertyTypeTransformation.prototype.convertTo = function (value, type) {
        var _this = this;
        if (Array.isArray(value)) {
            return value.map(function (v) { return _this.convertTo(v, type); });
        }
        if (null === value) {
            return null;
        }
        switch (type) {
            case PropertyType.String: return String(value);
            case PropertyType.Integer: return parseInt(value);
            case PropertyType.Float: return parseFloat(value);
            case PropertyType.Boolean: return Boolean(value);
            case PropertyType.Date:
                return new Date(value);
        }
    };
    /**
     * Converts the given type into a string.
     *
     * @param {*} value The value to transform.
     * @param {PropertyType} type The type to transform it into.
     * @return {*} The converted type.
     * @private
     */
    PropertyTypeTransformation.prototype.convertFrom = function (value, type) {
        var _this = this;
        if (Array.isArray(value)) {
            return value.map(function (v) { return _this.convertFrom(v, type); });
        }
        if (null === value) {
            return null;
        }
        switch (type) {
            case PropertyType.String:
            case PropertyType.Integer:
            case PropertyType.Float:
            case PropertyType.Boolean:
                return String(value);
            case PropertyType.Date: {
                return value.toISOString();
            }
        }
    };
    return PropertyTypeTransformation;
}());
exports.PropertyTypeTransformation = PropertyTypeTransformation;
