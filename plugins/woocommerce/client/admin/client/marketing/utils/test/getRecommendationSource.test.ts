import { describe, expect, it, vi, type Mock } from 'vitest';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { getRecommendationSource } from '../getRecommendationSource';
vi.mock( '~/utils/admin-settings', () => {
	const mock = {
		getAdminSetting: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'getRecommendationSource', () => {
	it( 'should return "woocommerce.com" when marketplace suggestions feature is turned on', () => {
		( getAdminSetting as Mock ).mockReturnValue( true );
		const source = getRecommendationSource();
		expect( getAdminSetting ).toHaveBeenCalledWith(
			'allowMarketplaceSuggestions',
			false
		);
		expect( source ).toBe( 'woocommerce.com' );
	} );
	it( 'should return "plugin-woocommerce" when marketplace suggestions feature is turned off', () => {
		( getAdminSetting as Mock ).mockReturnValue( false );
		const source = getRecommendationSource();
		expect( getAdminSetting ).toHaveBeenCalledWith(
			'allowMarketplaceSuggestions',
			false
		);
		expect( source ).toBe( 'plugin-woocommerce' );
	} );
} );
