"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.IgnorePropertyTransformation = void 0;
var model_transformer_1 = require("../model-transformer");
var IgnorePropertyTransformation = /** @class */ (function () {
    /**
     * Creates a new transformation.
     *
     * @param {Array.<string>} ignoreList The properties to ignore.
     */
    function IgnorePropertyTransformation(ignoreList) {
        this.fromModelOrder = model_transformer_1.TransformationOrder.Normal;
        this.ignoreList = ignoreList;
    }
    /**
     * Performs a transformation from model properties to raw properties.
     *
     * @param {*} properties The properties to transform.
     * @return {*} The transformed properties.
     */
    IgnorePropertyTransformation.prototype.fromModel = function (properties) {
        for (var _i = 0, _a = this.ignoreList; _i < _a.length; _i++) {
            var key = _a[_i];
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
    IgnorePropertyTransformation.prototype.toModel = function (properties) {
        for (var _i = 0, _a = this.ignoreList; _i < _a.length; _i++) {
            var key = _a[_i];
            delete properties[key];
        }
        return properties;
    };
    return IgnorePropertyTransformation;
}());
exports.IgnorePropertyTransformation = IgnorePropertyTransformation;
