import { HTTPClient, HTTPResponse } from '../../http';
import { mock, MockProxy } from 'jest-mock-extended';
import { RESTRepository } from '../rest-repository';
import { Model } from '../../models/model';
import { ModelTransformer } from '../model-transformer';

class TestData extends Model {
	public name: string = '';

	public onCreated( data: any ): void {
		super.onCreated( data );
		this.name = data.name;
	}
}

describe( 'RESTRepository', () => {
	let httpClient: MockProxy< HTTPClient >;
	let repository: RESTRepository< TestData >;

	beforeEach( () => {
		httpClient = mock< HTTPClient >();
		const transformer = new ModelTransformer< TestData >(
			( data ) => {
				return { transformedName: data.name };
			},
			() => new TestData(),
		);
		repository = new RESTRepository< TestData >(
			transformer,
			{ create: '/testing' },
		);
		repository.setHTTPClient( httpClient );
	} );

	it( 'should create', async () => {
		httpClient.post.mockReturnValueOnce(
			Promise.resolve(
				new HTTPResponse( 200, {}, { id: 1, name: 'created' } ),
			),
		);

		const data = new TestData();
		data.name = 'testing';
		const created = await repository.create( data );

		expect( created.name ).toEqual( 'created' );
		expect( httpClient.post ).toBeCalledWith( '/testing', { transformedName: 'testing' } );
	} );
} );
