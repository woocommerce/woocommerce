"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.DummyModel = void 0;
var models_1 = require("../models");
/**
 * A dummy model that can be used in test files.
 */
var DummyModel = /** @class */ (function (_super) {
    __extends(DummyModel, _super);
    function DummyModel(partial) {
        var _this = _super.call(this) || this;
        _this.name = '';
        Object.assign(_this, partial);
        return _this;
    }
    return DummyModel;
}(models_1.Model));
exports.DummyModel = DummyModel;
