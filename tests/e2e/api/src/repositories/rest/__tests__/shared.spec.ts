import { mock, MockProxy } from 'jest-mock-extended';
import { HTTPClient, HTTPResponse } from '../../../http';
import { ModelTransformer } from '../../../framework/model-transformer';
import { DummyModel } from '../../../__test_data__/dummy-model';
import {
	restCreate,
	restDelete, restDeleteChild,
	restList,
	restListChild,
	restRead,
	restReadChild,
	restUpdate,
	restUpdateChild,
} from '../shared';
import { ModelRepositoryParams } from '../../../framework/model-repository';
import { Model } from '../../../models/model';

type DummyModelParams = ModelRepositoryParams< DummyModel, never, { search: string }, 'name' >

class DummyChildModel extends Model {
	public childName: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}
type DummyChildParams = ModelRepositoryParams< DummyChildModel, { parent: string }, { childSearch: string }, 'childName' >

describe( 'Shared REST Functions', () => {
	let mockClient: MockProxy< HTTPClient >;
	let mockTransformer: MockProxy< ModelTransformer< any > > & ModelTransformer< any >;

	beforeEach( () => {
		mockClient = mock< HTTPClient >();
		mockTransformer = mock< ModelTransformer< any > >();
	} );

	it( 'restList', async () => {
		mockClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			[
				{
					id: 'Test-1',
					label: 'Test 1',
				},
				{
					id: 'Test-2',
					label: 'Test 2',
				},
			],
		) );
		mockTransformer.toModel.mockReturnValue( new DummyModel( { name: 'Test' } ) );

		const fn = restList< DummyModelParams >( () => 'test-url', DummyModel, mockClient, mockTransformer );

		const result = await fn( { search: 'Test' } );

		expect( result ).toHaveLength( 2 );
		expect( result[ 0 ] ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( result[ 1 ] ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( mockClient.get ).toHaveBeenCalledWith( 'test-url', { search: 'Test' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, { id: 'Test-1', label: 'Test 1' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, { id: 'Test-2', label: 'Test 2' } );
	} );

	it( 'restListChildren', async () => {
		mockClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			[
				{
					id: 'Test-1',
					label: 'Test 1',
				},
				{
					id: 'Test-2',
					label: 'Test 2',
				},
			],
		) );
		mockTransformer.toModel.mockReturnValue( new DummyChildModel( { name: 'Test' } ) );

		const fn = restListChild< DummyChildParams >(
			( parent ) => 'test-url-' + parent.parent,
			DummyChildModel,
			mockClient,
			mockTransformer,
		);

		const result = await fn( { parent: '123' }, { childSearch: 'Test' } );

		expect( result ).toHaveLength( 2 );
		expect( result[ 0 ] ).toMatchObject( new DummyChildModel( { name: 'Test' } ) );
		expect( result[ 1 ] ).toMatchObject( new DummyChildModel( { name: 'Test' } ) );
		expect( mockClient.get ).toHaveBeenCalledWith( 'test-url-123', { childSearch: 'Test' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyChildModel, { id: 'Test-1', label: 'Test 1' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyChildModel, { id: 'Test-2', label: 'Test 2' } );
	} );

	it( 'restCreate', async () => {
		mockClient.post.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'Test-1',
				label: 'Test 1',
			},
		) );
		mockTransformer.fromModel.mockReturnValue( { name: 'From-Test' } );
		mockTransformer.toModel.mockReturnValue( new DummyModel( { name: 'Test' } ) );

		const fn = restCreate< DummyModelParams >(
			( properties ) => 'test-url-' + properties.name,
			DummyModel,
			mockClient,
			mockTransformer,
		);

		const result = await fn( { name: 'Test' } );

		expect( result ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( { name: 'Test' } );
		expect( mockClient.post ).toHaveBeenCalledWith( 'test-url-Test', { name: 'From-Test' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, { id: 'Test-1', label: 'Test 1' } );
	} );

	it( 'restRead', async () => {
		mockClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'Test-1',
				label: 'Test 1',
			},
		) );
		mockTransformer.toModel.mockReturnValue( new DummyModel( { name: 'Test' } ) );

		const fn = restRead< DummyModelParams >( ( id ) => 'test-url-' + id, DummyModel, mockClient, mockTransformer );

		const result = await fn( 123 );

		expect( result ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( mockClient.get ).toHaveBeenCalledWith( 'test-url-123' );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, { id: 'Test-1', label: 'Test 1' } );
	} );

	it( 'restReadChildren', async () => {
		mockClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'Test-1',
				label: 'Test 1',
			},
		) );
		mockTransformer.toModel.mockReturnValue( new DummyChildModel( { name: 'Test' } ) );

		const fn = restReadChild< DummyChildParams >(
			( parent, id ) => 'test-url-' + parent.parent + '-' + id,
			DummyChildModel,
			mockClient,
			mockTransformer,
		);

		const result = await fn( { parent: '123' }, 456 );

		expect( result ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( mockClient.get ).toHaveBeenCalledWith( 'test-url-123-456' );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyChildModel, { id: 'Test-1', label: 'Test 1' } );
	} );

	it( 'restUpdate', async () => {
		mockClient.patch.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'Test-1',
				label: 'Test 1',
			},
		) );
		mockTransformer.fromModel.mockReturnValue( { name: 'From-Test' } );
		mockTransformer.toModel.mockReturnValue( new DummyModel( { name: 'Test' } ) );

		const fn = restUpdate< DummyModelParams >( ( id ) => 'test-url-' + id, DummyModel, mockClient, mockTransformer );

		const result = await fn( 123, { name: 'Test' } );

		expect( result ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( { name: 'Test' } );
		expect( mockClient.patch ).toHaveBeenCalledWith( 'test-url-123', { name: 'From-Test' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyModel, { id: 'Test-1', label: 'Test 1' } );
	} );

	it( 'restUpdateChildren', async () => {
		mockClient.patch.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'Test-1',
				label: 'Test 1',
			},
		) );
		mockTransformer.fromModel.mockReturnValue( { name: 'From-Test' } );
		mockTransformer.toModel.mockReturnValue( new DummyChildModel( { name: 'Test' } ) );

		const fn = restUpdateChild< DummyChildParams >(
			( parent, id ) => 'test-url-' + parent.parent + '-' + id,
			DummyChildModel,
			mockClient,
			mockTransformer,
		);

		const result = await fn( { parent: '123' }, 456, { childName: 'Test' } );

		expect( result ).toMatchObject( new DummyChildModel( { name: 'Test' } ) );
		expect( mockTransformer.fromModel ).toHaveBeenCalledWith( { childName: 'Test' } );
		expect( mockClient.patch ).toHaveBeenCalledWith( 'test-url-123-456', { name: 'From-Test' } );
		expect( mockTransformer.toModel ).toHaveBeenCalledWith( DummyChildModel, { id: 'Test-1', label: 'Test 1' } );
	} );

	it( 'restDelete', async () => {
		mockClient.delete.mockResolvedValue( new HTTPResponse( 200, {}, {} ) );

		const fn = restDelete< DummyModelParams >( ( id ) => 'test-url-' + id, mockClient );

		const result = await fn( 123 );

		expect( result ).toBe( true );
		expect( mockClient.delete ).toHaveBeenCalledWith( 'test-url-123' );
	} );

	it( 'restDeleteChildren', async () => {
		mockClient.delete.mockResolvedValue( new HTTPResponse( 200, {}, {} ) );

		const fn = restDeleteChild< DummyChildParams >(
			( parent, id ) => 'test-url-' + parent.parent + '-' + id,
			mockClient,
		);

		const result = await fn( { parent: '123' }, 456 );

		expect( result ).toBe( true );
		expect( mockClient.delete ).toHaveBeenCalledWith( 'test-url-123-456' );
	} );
} );
