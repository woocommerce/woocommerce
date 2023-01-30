import { ModelTransformerTransformation } from '../model-transformer-transformation';
import { ModelTransformer } from '../../model-transformer';
import { DummyModel } from '../../../__test_data__/dummy-model';

jest.mock( '../../model-transformer' );

describe( 'ModelTransformerTransformation', () => {
	let propertyTransformer: ModelTransformer< any >;
	let transformation: ModelTransformerTransformation< any >;

	beforeEach( () => {
		propertyTransformer = new ModelTransformer( [] );
		transformation = new ModelTransformerTransformation< DummyModel >(
			'test',
			DummyModel,
			propertyTransformer
		);
	} );

	it( 'should execute child transformer', () => {
		jest.mocked( propertyTransformer.toModel ).mockReturnValue( {
			toModel: 'Test',
		} );

		let transformed = transformation.toModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { toModel: 'Test' } } );
		expect( propertyTransformer.toModel ).toHaveBeenCalledWith(
			DummyModel,
			'Test'
		);

		jest.mocked( propertyTransformer.fromModel ).mockReturnValue( {
			fromModel: 'Test',
		} );

		transformed = transformation.fromModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { fromModel: 'Test' } } );
		expect( propertyTransformer.fromModel ).toHaveBeenCalledWith( 'Test' );
	} );

	it( 'should execute child transformer on array', () => {
		jest.mocked( propertyTransformer.toModel ).mockReturnValue( {
			toModel: 'Test',
		} );

		let transformed = transformation.toModel( {
			test: [ 'Test', 'Test2' ],
		} );

		expect( transformed ).toMatchObject( {
			test: [ { toModel: 'Test' }, { toModel: 'Test' } ],
		} );
		expect( propertyTransformer.toModel ).toHaveBeenCalledWith(
			DummyModel,
			'Test'
		);
		expect( propertyTransformer.toModel ).toHaveBeenCalledWith(
			DummyModel,
			'Test2'
		);

		jest.mocked( propertyTransformer.fromModel ).mockReturnValue( {
			fromModel: 'Test',
		} );

		transformed = transformation.fromModel( { test: [ 'Test', 'Test2' ] } );

		expect( transformed ).toMatchObject( {
			test: [ { fromModel: 'Test' }, { fromModel: 'Test' } ],
		} );
		expect( propertyTransformer.fromModel ).toHaveBeenCalledWith( 'Test' );
		expect( propertyTransformer.fromModel ).toHaveBeenCalledWith( 'Test2' );
	} );
} );
