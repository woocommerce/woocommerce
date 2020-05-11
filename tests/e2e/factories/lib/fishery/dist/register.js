"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
// TODO: would like to type this argument as AnyFactories but issue with
// inheritance since user-defined Factories will not have index property set
// see: https://github.com/Microsoft/TypeScript/issues/15300
exports.register = function (allFactories) {
    var factories = allFactories;
    Object.keys(factories).forEach(function (key) {
        factories[key].setFactories(factories);
    });
    return allFactories;
};
