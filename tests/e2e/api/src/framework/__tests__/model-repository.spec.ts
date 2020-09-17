import { Model } from '../../models/model';
import { ModelRepository } from '../model-repository';
import Mock = jest.Mock;

class DummyModel extends Model {
	public name: string = '';
}

describe( 'ModelRepository', () => {
	let dummyModel: DummyModel;
	let mockCallback: Mock;
	let repository: ModelRepository< DummyModel >;

	beforeEach( () => {
		dummyModel = new DummyModel();
		dummyModel.name = 'test';
		mockCallback = jest.fn();
		repository = new ModelRepository< DummyModel >( mockCallback, mockCallback, mockCallback, mockCallback );
	} );

	it( 'should create', async () => {
		mockCallback.mockResolvedValue( dummyModel );

		await repository.create( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );

	it( 'should read', async () => {
		mockCallback.mockResolvedValue( dummyModel );

		await repository.read( { id: 1 } );
		expect( mockCallback ).toHaveBeenCalledWith( { id: 1 } );
	} );

	it( 'should update', async () => {
		mockCallback.mockResolvedValue( dummyModel );

		await repository.update( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );

	it( 'should delete', async () => {
		mockCallback.mockResolvedValue( true );

		await repository.delete( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );
} );
