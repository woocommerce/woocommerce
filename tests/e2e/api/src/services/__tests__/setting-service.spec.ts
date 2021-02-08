import { mock, MockProxy } from 'jest-mock-extended';
import { UpdatesSettings } from '../../models';
import { SettingService } from '../setting-service';

describe( 'SettingService', () => {
	let repository: MockProxy< UpdatesSettings >;
	let service: SettingService;

	beforeEach( () => {
		repository = mock< UpdatesSettings >();
		service = new SettingService( repository );
	} );

	it( 'should update address', async () => {
		const result = await service.updateStoreAddress(
			'line1',
			'line2',
			'New York',
			'US:NY',
			'12345',
		);

		expect( result ).toBeTruthy();
		expect( repository.update ).toHaveBeenCalledTimes( 5 );
		expect( repository.update ).toHaveBeenCalledWith( 'general', 'woocommerce_store_address', { value: 'line1' } );
		expect( repository.update ).toHaveBeenCalledWith( 'general', 'woocommerce_store_address_2', { value: 'line2' } );
		expect( repository.update ).toHaveBeenCalledWith( 'general', 'woocommerce_store_city', { value: 'New York' } );
		expect( repository.update ).toHaveBeenCalledWith( 'general', 'woocommerce_default_country', { value: 'US:NY' } );
		expect( repository.update ).toHaveBeenCalledWith( 'general', 'woocommerce_store_postcode', { value: '12345' } );
	} );
} );
