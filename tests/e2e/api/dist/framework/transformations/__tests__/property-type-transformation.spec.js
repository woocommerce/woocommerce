"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var property_type_transformation_1 = require("../property-type-transformation");
describe('PropertyTypeTransformation', function () {
    var transformation;
    beforeEach(function () {
        transformation = new property_type_transformation_1.PropertyTypeTransformation({
            string: property_type_transformation_1.PropertyType.String,
            integer: property_type_transformation_1.PropertyType.Integer,
            float: property_type_transformation_1.PropertyType.Float,
            boolean: property_type_transformation_1.PropertyType.Boolean,
            date: property_type_transformation_1.PropertyType.Date,
            callback: function (value) { return 'Transformed-' + value; },
        });
    });
    it('should convert strings', function () {
        var transformed = transformation.toModel({ string: 'Test' });
        expect(transformed.string).toStrictEqual('Test');
        transformed = transformation.fromModel({ string: 'Test' });
        expect(transformed.string).toStrictEqual('Test');
    });
    it('should convert integers', function () {
        var transformed = transformation.toModel({ integer: '100' });
        expect(transformed.integer).toStrictEqual(100);
        transformed = transformation.fromModel({ integer: 100 });
        expect(transformed.integer).toStrictEqual('100');
    });
    it('should convert floats', function () {
        var transformed = transformation.toModel({ float: '2.5' });
        expect(transformed.float).toStrictEqual(2.5);
        transformed = transformation.fromModel({ float: 2.5 });
        expect(transformed.float).toStrictEqual('2.5');
    });
    it('should convert booleans', function () {
        var transformed = transformation.toModel({ boolean: 'true' });
        expect(transformed.boolean).toStrictEqual(true);
        transformed = transformation.fromModel({ boolean: false });
        expect(transformed.boolean).toStrictEqual('false');
    });
    it('should convert dates', function () {
        var transformed = transformation.toModel({ date: '2020-11-06T03:11:41.000Z' });
        expect(transformed.date).toStrictEqual(new Date('2020-11-06T03:11:41.000Z'));
        transformed = transformation.fromModel({ date: new Date('2020-11-06T03:11:41.000Z') });
        expect(transformed.date).toStrictEqual('2020-11-06T03:11:41.000Z');
    });
    it('should use conversion callbacks', function () {
        var transformed = transformation.toModel({ callback: 'Test' });
        expect(transformed.callback).toStrictEqual('Transformed-Test');
        transformed = transformation.fromModel({ callback: 'Test' });
        expect(transformed.callback).toStrictEqual('Transformed-Test');
    });
    it('should convert arrays', function () {
        var transformed = transformation.toModel({ integer: ['100', '200', '300'] });
        expect(transformed.integer).toStrictEqual([100, 200, 300]);
        transformed = transformation.fromModel({ integer: [100, 200, 300] });
        expect(transformed.integer).toStrictEqual(['100', '200', '300']);
    });
    it('should do nothing without property', function () {
        var transformed = transformation.toModel({ name: 'Test' });
        expect(transformed.name).toStrictEqual('Test');
        transformed = transformation.fromModel({ name: 'Test' });
        expect(transformed.name).toStrictEqual('Test');
    });
    it('should preserve null', function () {
        var transformed = transformation.toModel({ integer: null });
        expect(transformed.integer).toStrictEqual(null);
        transformed = transformation.fromModel({ integer: null });
        expect(transformed.integer).toStrictEqual(null);
    });
});
