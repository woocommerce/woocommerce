import { Model } from '../../models/model';
import { ModelRepository } from '../model-repository';
import Mock = jest.Mock;

class DummyModel extends Model {
	public name: string = '';

	public onCreated( data: any ): void {
		super.onCreated( data );
		this.name = data.name;
	}
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
		mockCallback.mockReturnValue( Promise.resolve( dummyModel ) );

		await repository.create( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );

	it( 'should read', async () => {
		mockCallback.mockReturnValue( Promise.resolve( dummyModel ) );

		await repository.read( { id: 'test' } );
		expect( mockCallback ).toHaveBeenCalledWith( { id: 'test' } );
	} );

	it( 'should update', async () => {
		mockCallback.mockReturnValue( Promise.resolve( dummyModel ) );

		await repository.update( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );

	it( 'should delete', async () => {
		mockCallback.mockReturnValue( Promise.resolve( true ) );

		await repository.delete( dummyModel );
		expect( mockCallback ).toHaveBeenCalledWith( dummyModel );
	} );
} );
