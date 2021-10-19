"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var key_change_transformation_1 = require("../key-change-transformation");
describe('KeyChangeTransformation', function () {
    var transformation;
    beforeEach(function () {
        transformation = new key_change_transformation_1.KeyChangeTransformation({
            name: 'new-name',
        });
    });
    it('should transform to model', function () {
        var transformed = transformation.toModel({ 'new-name': 'Test Name' });
        expect(transformed).toHaveProperty('name', 'Test Name');
        expect(transformed).not.toHaveProperty('new-name');
    });
    it('should transform from model', function () {
        var transformed = transformation.fromModel({ name: 'Test Name' });
        expect(transformed).toHaveProperty('new-name', 'Test Name');
        expect(transformed).not.toHaveProperty('name');
    });
});
