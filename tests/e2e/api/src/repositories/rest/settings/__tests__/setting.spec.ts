import { mock, MockProxy } from 'jest-mock-extended';
import { HTTPClient, HTTPResponse } from '../../../../http';
import { settingRESTRepository } from '../setting';

describe( 'settingGroupRESTRepository', () => {
	let httpClient: MockProxy< HTTPClient >;
	let repository: ReturnType< typeof settingRESTRepository >;

	beforeEach( () => {
		httpClient = mock< HTTPClient >();
		repository = settingRESTRepository( httpClient );
	} );

	it( 'should list', async () => {
		httpClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			[
				{
					id: 'setting_1',
					label: 'Test Setting 1',
				},
				{
					id: 'setting_2',
					label: 'Test Setting 2',
				},
			],
		) );

		const list = await repository.list( 'general' );

		expect( list ).toHaveLength( 2 );
		expect( list[ 0 ] ).toMatchObject( { id: 'setting_1', label: 'Test Setting 1' } );
		expect( list[ 1 ] ).toMatchObject( { id: 'setting_2', label: 'Test Setting 2' } );
		expect( httpClient.get ).toHaveBeenCalledWith( '/wc/v3/settings/general' );
	} );

	it( 'should read', async () => {
		httpClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'setting_1',
				label: 'Test Setting',
			},
		) );

		const read = await repository.read( 'general', 'setting_1' );

		expect( read ).toMatchObject( { id: 'setting_1', label: 'Test Setting' } );
		expect( httpClient.get ).toHaveBeenCalledWith( '/wc/v3/settings/general/setting_1' );
	} );

	it( 'should update', async () => {
		httpClient.patch.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{
				id: 'setting_1',
				label: 'Test Setting',
				value: 'updated-value',
			},
		) );

		const updated = await repository.update( 'general', 'setting_1', { value: 'test-value' } );

		expect( updated ).toMatchObject( { id: 'setting_1', value: 'updated-value' } );
		expect( httpClient.patch ).toHaveBeenCalledWith(
			'/wc/v3/settings/general/setting_1',
			{ value: 'test-value' },
		);
	} );
} );
