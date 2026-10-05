import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Internal dependencies
 */
import { bumpStat } from '../stats';
vi.mock( '../utils', () => {
	const mock = {
		isDevelopmentMode: false,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
declare global {
	interface Window {
		Image: typeof Image;
	}
}
describe( 'bumpStat', () => {
	let originalImage: typeof Image;
	let mockImage: {
		src: string;
	};
	beforeEach( () => {
		originalImage = window.Image;
		mockImage = {
			src: '',
		};
		window.Image = vi.fn( function () {
			return mockImage;
		} ) as unknown as typeof Image;
		window.wcTracks = {
			isEnabled: true,
			validateEvent: vi.fn(),
			recordEvent: vi.fn(),
		};
	} );
	afterEach( () => {
		window.Image = originalImage;
		vi.resetAllMocks();
	} );
	it( 'should not bump stats when wcTracks is not enabled', () => {
		window.wcTracks.isEnabled = false;
		const result = bumpStat( 'group', 'name' );
		expect( result ).toBe( false );
		expect( window.Image ).not.toHaveBeenCalled();
	} );
	it( 'should not bump stats in development mode', async () => {
		vi.resetModules();
		vi.doMock( '../utils', () => {
			const mock = {
				isDevelopmentMode: true,
			};
			return Object.defineProperties(
				{
					default: mock,
				},
				Object.getOwnPropertyDescriptors( mock )
			);
		} );
		// eslint-disable-next-line @typescript-eslint/no-var-requires
		const { bumpStat: bumpStatDev } = await import( '../stats' );
		const result = bumpStatDev( 'group', 'name' );
		expect( result ).toBe( false );
		expect( window.Image ).not.toHaveBeenCalled();
	} );
	it( 'should not bump stats when name is empty given group is a string', () => {
		const result = bumpStat( 'group', '' );
		expect( result ).toBe( false );
		expect( window.Image ).not.toHaveBeenCalled();
	} );
	it( 'should bump a single stat', () => {
		const result = bumpStat( 'group', 'name' );
		expect( result ).toBe( true );
		expect( window.Image ).toHaveBeenCalledTimes( 1 );
		expect( mockImage.src ).toMatch(
			/^https?:\/\/pixel\.wp\.com\/g\.gif\?v=wpcom-no-pv&x_woocommerce-group=name&t=/
		);
	} );
	it( 'should bump multiple stats', () => {
		const result = bumpStat( {
			stat1: 'value1',
			stat2: 'value2',
		} );
		expect( result ).toBe( true );
		expect( window.Image ).toHaveBeenCalledTimes( 1 );
		expect( mockImage.src ).toMatch(
			/^https?:\/\/pixel\.wp\.com\/g\.gif\?v=wpcom-no-pv&x_woocommerce-stat1=value1&x_woocommerce-stat2=value2&t=/
		);
	} );
} );
