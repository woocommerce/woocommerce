import { PropertyType, PropertyTypeTransformation } from '../property-type-transformation';

describe( 'PropertyTypeTransformation', () => {
	let transformation: PropertyTypeTransformation;

	beforeEach( () => {
		transformation = new PropertyTypeTransformation(
			{
				string: PropertyType.String,
				integer: PropertyType.Integer,
				float: PropertyType.Float,
				boolean: PropertyType.Boolean,
				date: PropertyType.Date,
				callback: ( value: string ) => 'Transformed-' + value,
			},
		);
	} );

	it( 'should convert strings', () => {
		let transformed = transformation.toModel( { string: 'Test' } );

		expect( transformed.string ).toStrictEqual( 'Test' );

		transformed = transformation.fromModel( { string: 'Test' } );

		expect( transformed.string ).toStrictEqual( 'Test' );
	} );

	it( 'should convert integers', () => {
		let transformed = transformation.toModel( { integer: '100' } );

		expect( transformed.integer ).toStrictEqual( 100 );

		transformed = transformation.fromModel( { integer: 100 } );

		expect( transformed.integer ).toStrictEqual( '100' );
	} );

	it( 'should convert floats', () => {
		let transformed = transformation.toModel( { float: '2.5' } );

		expect( transformed.float ).toStrictEqual( 2.5 );

		transformed = transformation.fromModel( { float: 2.5 } );

		expect( transformed.float ).toStrictEqual( '2.5' );
	} );

	it( 'should convert booleans', () => {
		let transformed = transformation.toModel( { boolean: 'true' } );

		expect( transformed.boolean ).toStrictEqual( true );

		transformed = transformation.fromModel( { boolean: false } );

		expect( transformed.boolean ).toStrictEqual( 'false' );
	} );

	it( 'should convert dates', () => {
		let transformed = transformation.toModel( { date: '2020-11-06T03:11:41.000Z' } );

		expect( transformed.date ).toStrictEqual( new Date( '2020-11-06T03:11:41.000Z' ) );

		transformed = transformation.fromModel( { date: new Date( '2020-11-06T03:11:41.000Z' ) } );

		expect( transformed.date ).toStrictEqual( '2020-11-06T03:11:41.000Z' );
	} );

	it( 'should use conversion callbacks', () => {
		let transformed = transformation.toModel( { callback: 'Test' } );

		expect( transformed.callback ).toStrictEqual( 'Transformed-Test' );

		transformed = transformation.fromModel( { callback: 'Test' } );

		expect( transformed.callback ).toStrictEqual( 'Transformed-Test' );
	} );

	it( 'should convert arrays', () => {
		let transformed = transformation.toModel( { integer: [ '100', '200', '300' ] } );

		expect( transformed.integer ).toStrictEqual( [ 100, 200, 300 ] );

		transformed = transformation.fromModel( { integer: [ 100, 200, 300 ] } );

		expect( transformed.integer ).toStrictEqual( [ '100', '200', '300' ] );
	} );

	it( 'should do nothing without property', () => {
		let transformed = transformation.toModel( { name: 'Test' } );

		expect( transformed.name ).toStrictEqual( 'Test' );

		transformed = transformation.fromModel( { name: 'Test' } );

		expect( transformed.name ).toStrictEqual( 'Test' );
	} );

	it( 'should preserve null', () => {
		let transformed = transformation.toModel( { integer: null } );

		expect( transformed.integer ).toStrictEqual( null );

		transformed = transformation.fromModel( { integer: null } );

		expect( transformed.integer ).toStrictEqual( null );
	} );
} );
