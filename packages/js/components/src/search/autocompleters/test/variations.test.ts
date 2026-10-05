import { afterEach, describe, expect, it, vi } from 'vitest';

/**
 * Internal dependencies
 */
import variations from '../variations';
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getQuery: vi.fn( () => ( {} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'variations autocompleter', () => {
	const hadWcSettings = 'wcSettings' in window;
	const originalWcSettings = window.wcSettings;
	const variation = {
		id: 1,
		name: 'T-shirt',
		attributes: [
			{
				option: 'Red',
			},
			{
				option: 'Large',
			},
		],
		sku: 'TSHIRT-RED-LARGE',
	};
	afterEach( () => {
		if ( hadWcSettings ) {
			window.wcSettings = originalWcSettings;
		} else {
			delete window.wcSettings;
		}
	} );
	it( 'uses the default separator when wcSettings is unavailable', () => {
		delete window.wcSettings;
		expect( variations.getOptionKeywords( variation ) ).toEqual( [
			'T-shirt - Red, Large',
			'TSHIRT-RED-LARGE',
		] );
	} );
	it( 'uses the separator configured in wcSettings', () => {
		window.wcSettings = {
			variationTitleAttributesSeparator: ' | ',
			countries: {},
		};
		expect( variations.getOptionKeywords( variation ) ).toEqual( [
			'T-shirt | Red, Large',
			'TSHIRT-RED-LARGE',
		] );
	} );
} );
