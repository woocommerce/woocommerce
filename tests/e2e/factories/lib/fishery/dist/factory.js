"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var builder_1 = require("./builder");
var Factory = /** @class */ (function () {
    function Factory(generator) {
        this.generator = generator;
        this.nextId = 0;
    }
    /**
     * Define a factory. This factory needs to be registered with
     * `register` before use.
     * @param generator - your factory function
     */
    Factory.define = function (generator) {
        return new Factory(generator);
    };
    /**
     * Define a factory that does not need to be registered with `register`. The
     * factory will not have access the `factories` parameter. This can be useful
     * for one-off factories in individual tests
     * @param generator - your factory
     * function
     */
    Factory.defineUnregistered = function (generator) {
        var factory = new Factory(generator);
        factory.setFactories(null);
        return factory;
    };
    /**
     * Build an object using your factory
     * @param params
     * @param options
     */
    Factory.prototype.build = function (params, options) {
        if (params === void 0) { params = {}; }
        if (options === void 0) { options = {}; }
        if (typeof this.factories === 'undefined') {
            this._throwFactoriesUndefined();
        }
        return new builder_1.FactoryBuilder(this.generator, this.factories, this.nextId++, params, options).build();
    };
    Factory.prototype.buildList = function (number, params, options) {
        if (params === void 0) { params = {}; }
        if (options === void 0) { options = {}; }
        var list = [];
        for (var i = 0; i < number; i++) {
            list.push(this.build(params, options));
        }
        return list;
    };
    /**
     * Asynchronously create an object using your factory. Relies on the `create`
     * function defined in the factory
     * @param params
     * @param options
     */
    Factory.prototype.create = function (params, options) {
        if (params === void 0) { params = {}; }
        if (options === void 0) { options = {}; }
        if (typeof this.factories === 'undefined') {
            this._throwFactoriesUndefined();
        }
        return new builder_1.FactoryBuilder(this.generator, this.factories, this.nextId++, params, options).create();
    };
    Factory.prototype.createList = function (number, params, options) {
        if (params === void 0) { params = {}; }
        if (options === void 0) { options = {}; }
        var promises = [];
        for (var i = 0; i < number; i++) {
            promises.push(this.create(params, options));
        }
        return Promise.all(promises);
    };
    Factory.prototype.setFactories = function (factories) {
        this.factories = factories;
    };
    Factory.prototype._throwFactoriesUndefined = function () {
        throw new Error('Your factory has not been registered. Call `register` before using factories or define your factory with `defineUnregistered` instead of `define`');
    };
    return Factory;
}());
exports.Factory = Factory;
