"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var model_transformer_1 = require("../model-transformer");
var dummy_model_1 = require("../../__test_data__/dummy-model");
var DummyTransformation = /** @class */ (function () {
    function DummyTransformation(order, fn) {
        this.fromModelOrder = order;
        this.fn = fn;
    }
    DummyTransformation.prototype.fromModel = function (properties) {
        if (!this.fn) {
            return properties;
        }
        return this.fn(properties);
    };
    DummyTransformation.prototype.toModel = function (properties) {
        if (!this.fn) {
            return properties;
        }
        return this.fn(properties);
    };
    return DummyTransformation;
}());
describe('ModelTransformer', function () {
    it('should order transformers correctly', function () {
        var fn1 = jest.fn();
        fn1.mockReturnValue({ name: 'fn1' });
        var fn2 = jest.fn();
        fn2.mockReturnValue({ name: 'fn2' });
        var transformer = new model_transformer_1.ModelTransformer([
            // Ensure the orders are backwards so sorting is tested.
            new DummyTransformation(1, fn2),
            new DummyTransformation(0, fn1),
        ]);
        var transformed = transformer.fromModel(new dummy_model_1.DummyModel({ name: 'fn0' }));
        expect(fn1).toHaveBeenCalledWith({ name: 'fn0' });
        expect(fn2).toHaveBeenCalledWith({ name: 'fn1' });
        expect(transformed).toMatchObject({ name: 'fn2' });
        // Reset and make sure "toModel" happens in reverse order.
        fn1.mockClear();
        fn2.mockClear();
        transformed = transformer.toModel(dummy_model_1.DummyModel, { name: 'fn3' });
        expect(fn2).toHaveBeenCalledWith({ name: 'fn3' });
        expect(fn1).toHaveBeenCalledWith({ name: 'fn2' });
        expect(transformed).toMatchObject({ name: 'fn1' });
    });
    it('should transform to model', function () {
        var transformer = new model_transformer_1.ModelTransformer([
            new DummyTransformation(0, function (p) {
                p.name = 'Transformed-' + p.name;
                return p;
            }),
        ]);
        var model = transformer.toModel(dummy_model_1.DummyModel, { name: 'Test' });
        expect(model).toBeInstanceOf(dummy_model_1.DummyModel);
        expect(model.name).toEqual('Transformed-Test');
    });
    it('should transform from model', function () {
        var transformer = new model_transformer_1.ModelTransformer([
            new DummyTransformation(0, function (p) {
                p.name = 'Transformed-' + p.name;
                return p;
            }),
        ]);
        var transformed = transformer.fromModel(new dummy_model_1.DummyModel({ name: 'Test' }));
        expect(transformed).not.toBeInstanceOf(dummy_model_1.DummyModel);
        expect(transformed.name).toEqual('Transformed-Test');
    });
});
