"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var add_property_transformation_1 = require("../add-property-transformation");
describe('AddPropertyTransformation', function () {
    var transformation;
    beforeEach(function () {
        transformation = new add_property_transformation_1.AddPropertyTransformation({ toProperty: 'Test' }, { fromProperty: 'Test' });
    });
    it('should add property when missing', function () {
        var transformed = transformation.toModel({ id: 1, name: 'Test' });
        expect(transformed).toMatchObject({
            id: 1,
            name: 'Test',
            toProperty: 'Test',
        });
        transformed = transformation.fromModel({ id: 1, name: 'Test' });
        expect(transformed).toMatchObject({
            id: 1,
            name: 'Test',
            fromProperty: 'Test',
        });
    });
    it('should not add property when present', function () {
        var transformed = transformation.toModel({ id: 1, name: 'Test', toProperty: 'Existing' });
        expect(transformed).toMatchObject({
            id: 1,
            name: 'Test',
            toProperty: 'Existing',
        });
        transformed = transformation.fromModel({ id: 1, name: 'Test', fromProperty: 'Existing' });
        expect(transformed).toMatchObject({
            id: 1,
            name: 'Test',
            fromProperty: 'Existing',
        });
    });
});
