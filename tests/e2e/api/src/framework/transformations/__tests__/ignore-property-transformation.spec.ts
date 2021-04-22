import { IgnorePropertyTransformation } from '../ignore-property-transformation';

describe( 'IgnorePropertyTransformation', () => {
	let transformation: IgnorePropertyTransformation;

	beforeEach( () => {
		transformation = new IgnorePropertyTransformation( [ 'skip' ] );
	} );

	it( 'should remove ignored properties', () => {
		let transformed = transformation.fromModel(
			{
				test: 'Test',
				skip: 'Test',
			},
		);

		expect( transformed ).toHaveProperty( 'test', 'Test' );
		expect( transformed ).not.toHaveProperty( 'skip' );

		transformed = transformation.toModel(
			{
				test: 'Test',
				skip: 'Test',
			},
		);

		expect( transformed ).toHaveProperty( 'test', 'Test' );
		expect( transformed ).not.toHaveProperty( 'skip' );
	} );
} );
