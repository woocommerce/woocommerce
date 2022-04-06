import { KeyChangeTransformation } from '../key-change-transformation';
import { DummyModel } from '../../../__test_data__/dummy-model';

describe( 'KeyChangeTransformation', () => {
	let transformation: KeyChangeTransformation< DummyModel >;

	beforeEach( () => {
		transformation = new KeyChangeTransformation< DummyModel >(
			{
				name: 'new-name',
			},
		);
	} );

	it( 'should transform to model', () => {
		const transformed = transformation.toModel( { 'new-name': 'Test Name' } );

		expect( transformed ).toHaveProperty( 'name', 'Test Name' );
		expect( transformed ).not.toHaveProperty( 'new-name' );
	} );

	it( 'should transform from model', () => {
		const transformed = transformation.fromModel( { name: 'Test Name' } );

		expect( transformed ).toHaveProperty( 'new-name', 'Test Name' );
		expect( transformed ).not.toHaveProperty( 'name' );
	} );
} );
