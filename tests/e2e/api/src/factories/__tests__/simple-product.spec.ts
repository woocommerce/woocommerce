import { AsyncFactory } from '../../framework/async-factory';
import { SimpleProduct } from '../../models';
import Mock = jest.Mock;
import { simpleProductFactory } from '../simple-product';

describe( 'simpleProductFactory', () => {
	let mockCreator: Mock;
	let factory: AsyncFactory< SimpleProduct >;

	beforeEach( () => {
		mockCreator = jest.fn();
		factory = simpleProductFactory( mockCreator );
	} );

	it( 'should build', () => {
		const model = factory.build( { name: 'Test Product' } );

		expect( model ).toMatchObject( { name: 'Test Product' } );
		expect( parseFloat( model.regularPrice ) ).toBeGreaterThan( 0.0 );
	} );
} );
