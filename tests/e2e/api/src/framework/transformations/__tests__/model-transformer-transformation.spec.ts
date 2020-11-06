import { ModelTransformerTransformation } from '../model-transformer-transformation';
import { ModelTransformer } from '../../model-transformer';
import { mock, MockProxy } from 'jest-mock-extended';
import { DummyModel } from '../../../__test_data__/dummy-model';

describe( 'ModelTransformerTransformation', () => {
	let mockTransformer: MockProxy< ModelTransformer< any > > & ModelTransformer< any >;
	let transformation: ModelTransformerTransformation< any >;

	beforeEach( () => {
		mockTransformer = mock< ModelTransformer< any > >();
		transformation = new ModelTransformerTransformation< DummyModel >(
			'test',
			DummyModel,
			mockTransformer,
		);
	} );

	it( 'should execute child transformer', () => {
		mockTransformer.toModel.mockReturnValue( { toModel: 'Test' } );

		let transformed = transformation.toModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { toModel: 'Test' } } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, 'Test' );

		mockTransformer.fromModel.mockReturnValue( { fromModel: 'Test' } );

		transformed = transformation.fromModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { fromModel: 'Test' } } );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( 'Test' );
	} );

	it( 'should execute child transformer on array', () => {
		mockTransformer.toModel.mockReturnValue( { toModel: 'Test' } );

		let transformed = transformation.toModel( { test: [ 'Test', 'Test2' ] } );

		expect( transformed ).toMatchObject( { test: [ { toModel: 'Test' }, { toModel: 'Test' } ] } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, 'Test' );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, 'Test2' );

		mockTransformer.fromModel.mockReturnValue( { fromModel: 'Test' } );

		transformed = transformation.fromModel( { test: [ 'Test', 'Test2' ] } );

		expect( transformed ).toMatchObject( { test: [ { fromModel: 'Test' }, { fromModel: 'Test' } ] } );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( 'Test' );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( 'Test2' );
	} );
} );
