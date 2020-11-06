import { ModelTransformerTransformation } from '../model-transformer-transformation';
import { ModelTransformer } from '../../model-transformer';
import { mock } from 'jest-mock-extended';
import { DummyModel } from '../../../__test_data__/dummy-model';

describe( 'ModelTransformerTransformation', () => {
	it( 'should execute child transformer', () => {
		const mockTransformer = mock< ModelTransformer< any > >();
		const transformation = new ModelTransformerTransformation< DummyModel >(
			'test',
			DummyModel,
			mockTransformer,
		);

		mockTransformer.toModel.mockReturnValue( { toModel: 'Test' } );

		let transformed = transformation.toModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { toModel: 'Test' } } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, 'Test' );

		mockTransformer.fromModel.mockReturnValue( { fromModel: 'Test' } );

		transformed = transformation.fromModel( { test: 'Test' } );

		expect( transformed ).toMatchObject( { test: { fromModel: 'Test' } } );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( 'Test' );
	} );
} );
