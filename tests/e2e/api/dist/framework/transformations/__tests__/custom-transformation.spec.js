"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var custom_transformation_1 = require("../custom-transformation");
describe('CustomTransformation', function () {
    it('should do nothing without hooks', function () {
        var transformation = new custom_transformation_1.CustomTransformation(0, null, null);
        var expected = { test: 'Test' };
        expect(transformation.toModel(expected)).toMatchObject(expected);
        expect(transformation.fromModel(expected)).toMatchObject(expected);
    });
    it('should execute hooks', function () {
        var toHook = jest.fn();
        toHook.mockReturnValue({ toModel: 'Test' });
        var fromHook = jest.fn();
        fromHook.mockReturnValue({ fromModel: 'Test' });
        var transformation = new custom_transformation_1.CustomTransformation(0, toHook, fromHook);
        expect(transformation.toModel({ test: 'Test' })).toMatchObject({ toModel: 'Test' });
        expect(toHook).toHaveBeenCalledWith({ test: 'Test' });
        expect(transformation.fromModel({ test: 'Test' })).toMatchObject({ fromModel: 'Test' });
        expect(fromHook).toHaveBeenCalledWith({ test: 'Test' });
    });
});
