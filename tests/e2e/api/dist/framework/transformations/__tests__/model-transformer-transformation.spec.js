"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var model_transformer_transformation_1 = require("../model-transformer-transformation");
var jest_mock_extended_1 = require("jest-mock-extended");
var dummy_model_1 = require("../../../__test_data__/dummy-model");
describe('ModelTransformerTransformation', function () {
    var mockTransformer;
    var transformation;
    beforeEach(function () {
        mockTransformer = jest_mock_extended_1.mock();
        transformation = new model_transformer_transformation_1.ModelTransformerTransformation('test', dummy_model_1.DummyModel, mockTransformer);
    });
    it('should execute child transformer', function () {
        mockTransformer.toModel.mockReturnValue({ toModel: 'Test' });
        var transformed = transformation.toModel({ test: 'Test' });
        expect(transformed).toMatchObject({ test: { toModel: 'Test' } });
        expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, 'Test');
        mockTransformer.fromModel.mockReturnValue({ fromModel: 'Test' });
        transformed = transformation.fromModel({ test: 'Test' });
        expect(transformed).toMatchObject({ test: { fromModel: 'Test' } });
        expect(mockTransformer.fromModel).toHaveBeenCalledWith('Test');
    });
    it('should execute child transformer on array', function () {
        mockTransformer.toModel.mockReturnValue({ toModel: 'Test' });
        var transformed = transformation.toModel({ test: ['Test', 'Test2'] });
        expect(transformed).toMatchObject({ test: [{ toModel: 'Test' }, { toModel: 'Test' }] });
        expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, 'Test');
        expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, 'Test2');
        mockTransformer.fromModel.mockReturnValue({ fromModel: 'Test' });
        transformed = transformation.fromModel({ test: ['Test', 'Test2'] });
        expect(transformed).toMatchObject({ test: [{ fromModel: 'Test' }, { fromModel: 'Test' }] });
        expect(mockTransformer.fromModel).toHaveBeenCalledWith('Test');
        expect(mockTransformer.fromModel).toHaveBeenCalledWith('Test2');
    });
});
