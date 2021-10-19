"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var ignore_property_transformation_1 = require("../ignore-property-transformation");
describe('IgnorePropertyTransformation', function () {
    var transformation;
    beforeEach(function () {
        transformation = new ignore_property_transformation_1.IgnorePropertyTransformation(['skip']);
    });
    it('should remove ignored properties', function () {
        var transformed = transformation.fromModel({
            test: 'Test',
            skip: 'Test',
        });
        expect(transformed).toHaveProperty('test', 'Test');
        expect(transformed).not.toHaveProperty('skip');
        transformed = transformation.toModel({
            test: 'Test',
            skip: 'Test',
        });
        expect(transformed).toHaveProperty('test', 'Test');
        expect(transformed).not.toHaveProperty('skip');
    });
});
