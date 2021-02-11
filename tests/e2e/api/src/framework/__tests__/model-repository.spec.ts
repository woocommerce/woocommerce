import { Model } from '../../models';
import {
	CreatesModels,
	DeletesChildModels,
	DeletesModels,
	ListsChildModels,
	ListsModels,
	ModelRepository,
	ModelRepositoryParams,
	ReadsChildModels,
	ReadsModels,
	UpdatesChildModels,
	UpdatesModels,
} from '../model-repository';
import { DummyModel } from '../../__test_data__/dummy-model';

type DummyModelParams = ModelRepositoryParams< DummyModel, never, { search: string }, 'name' >

class DummyChildModel extends Model {
	public childName: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}
type DummyChildParams = ModelRepositoryParams< DummyChildModel, { parent: string }, { childSearch: string }, 'childName' >

describe( 'ModelRepository', () => {
	it( 'should list', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( [ model ] );
		const repository: ListsModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			callback,
			null,
			null,
			null,
			null,
		);

		const listed = await repository.list( { search: 'test' } );
		expect( listed ).toContain( model );
		expect( callback ).toHaveBeenCalledWith( { search: 'test' } );
	} );

	it( 'should list child', async () => {
		const model = new DummyChildModel();
		const callback = jest.fn().mockResolvedValue( [ model ] );
		const repository: ListsChildModels< DummyChildParams > = new ModelRepository< DummyChildParams >(
			callback,
			null,
			null,
			null,
			null,
		);

		const listed = await repository.list( { parent: 'test' }, { childSearch: 'test' } );
		expect( listed ).toContain( model );
		expect( callback ).toHaveBeenCalledWith( { parent: 'test' }, { childSearch: 'test' } );
	} );

	it( 'should throw error on list without callback', () => {
		const repository: ListsModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			null,
		);

		expect( () => repository.list() ).toThrowError( /not supported/i );
	} );

	it( 'should create', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: CreatesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			callback,
			null,
			null,
			null,
		);

		const created = await repository.create( { name: 'test' } );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( { name: 'test' } );
	} );

	it( 'should create child', async () => {
		const model = new DummyChildModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: CreatesModels< DummyChildParams > = new ModelRepository< DummyChildParams >(
			null,
			callback,
			null,
			null,
			null,
		);

		const created = await repository.create( { childName: 'test' } );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( { childName: 'test' } );
	} );

	it( 'should throw error on create without callback', () => {
		const repository: CreatesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			null,
		);

		expect( () => repository.create( { name: 'test' } ) ).toThrowError( /not supported/i );
	} );

	it( 'should read', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: ReadsModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			callback,
			null,
			null,
		);

		const created = await repository.read( 1 );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( 1 );
	} );

	it( 'should read child', async () => {
		const model = new DummyChildModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: ReadsChildModels< DummyChildParams > = new ModelRepository< DummyChildParams >(
			null,
			null,
			callback,
			null,
			null,
		);

		const created = await repository.read( { parent: 'yes' }, 1 );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( { parent: 'yes' }, 1 );
	} );

	it( 'should throw error on read without callback', () => {
		const repository: ReadsModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			null,
		);

		expect( () => repository.read( 1 ) ).toThrowError( /not supported/i );
	} );

	it( 'should update', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: UpdatesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			callback,
			null,
		);

		const updated = await repository.update( 1, { name: 'new-name' } );
		expect( updated ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( 1, { name: 'new-name' } );
	} );

	it( 'should update child', async () => {
		const model = new DummyChildModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository: UpdatesChildModels< DummyChildParams > = new ModelRepository< DummyChildParams >(
			null,
			null,
			null,
			callback,
			null,
		);

		const updated = await repository.update( { parent: 'test' }, 1, { childName: 'new-name' } );
		expect( updated ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( { parent: 'test' }, 1, { childName: 'new-name' } );
	} );

	it( 'should throw error on update without callback', () => {
		const repository: UpdatesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			null,
		);

		expect( () => repository.update( 1, { name: 'new-name' } ) ).toThrowError( /not supported/i );
	} );

	it( 'should delete', async () => {
		const callback = jest.fn().mockResolvedValue( true );
		const repository: DeletesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			callback,
		);

		const success = await repository.delete( 1 );
		expect( success ).toBe( true );
		expect( callback ).toHaveBeenCalledWith( 1 );
	} );

	it( 'should delete child', async () => {
		const callback = jest.fn().mockResolvedValue( true );
		const repository: DeletesChildModels< DummyChildParams > = new ModelRepository< DummyChildParams >(
			null,
			null,
			null,
			null,
			callback,
		);

		const success = await repository.delete( { parent: 'yes' }, 1 );
		expect( success ).toBe( true );
		expect( callback ).toHaveBeenCalledWith( { parent: 'yes' }, 1 );
	} );

	it( 'should throw error on delete without callback', () => {
		const repository: DeletesModels< DummyModelParams > = new ModelRepository< DummyModelParams >(
			null,
			null,
			null,
			null,
			null,
		);

		expect( () => repository.delete( 1 ) ).toThrowError( /not supported/i );
	} );
} );
