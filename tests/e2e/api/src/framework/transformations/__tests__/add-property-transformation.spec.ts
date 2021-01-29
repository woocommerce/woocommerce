import { AddPropertyTransformation } from '../add-property-transformation';

describe( 'AddPropertyTransformation', () => {
	let transformation: AddPropertyTransformation;

	beforeEach( () => {
		transformation = new AddPropertyTransformation(
			{ toProperty: 'Test' },
			{ fromProperty: 'Test' },
		);
	} );

	it( 'should add property when missing', () => {
		let transformed = transformation.toModel( { id: 1, name: 'Test' } );

		expect( transformed ).toMatchObject(
			{
				id: 1,
				name: 'Test',
				toProperty: 'Test',
			},
		);

		transformed = transformation.fromModel( { id: 1, name: 'Test' } );

		expect( transformed ).toMatchObject(
			{
				id: 1,
				name: 'Test',
				fromProperty: 'Test',
			},
		);
	} );

	it( 'should not add property when present', () => {
		let transformed = transformation.toModel( { id: 1, name: 'Test', toProperty: 'Existing' } );

		expect( transformed ).toMatchObject(
			{
				id: 1,
				name: 'Test',
				toProperty: 'Existing',
			},
		);

		transformed = transformation.fromModel( { id: 1, name: 'Test', fromProperty: 'Existing' } );

		expect( transformed ).toMatchObject(
			{
				id: 1,
				name: 'Test',
				fromProperty: 'Existing',
			},
		);
	} );
} );
