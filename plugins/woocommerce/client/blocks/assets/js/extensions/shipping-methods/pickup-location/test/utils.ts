type Utils = typeof import('../utils');

// The settings screen hydrates this global before the module loads.
( global as unknown as Record< string, unknown > ).hydratedScreenSettings = {
	pickupLocationSettings: {
		enabled: true,
		title: 'Pickup',
		tax_status: 'taxable',
		cost: '',
	},
	pickupLocations: [],
	readonlySettings: {
		hasLegacyPickup: false,
		storeCountry: 'AU',
		storeState: 'VIC',
	},
};

// The country and state tables are read when the module loads, so the settings
// have to be in place before a fresh module registry requires it.
const loadUtils = (): Utils => {
	window.wcSettings = {
		...window.wcSettings,
		countries: {
			AU: 'Australia',
			US: 'United States (US)',
		},
		countryStates: {
			AU: { VIC: 'Victoria' },
			US: { CA: 'California' },
		},
	};

	let utils: Utils | null = null;
	jest.isolateModules( () => {
		utils = require( '../utils' );
	} );
	if ( ! utils ) {
		throw new Error( 'Pickup location utils did not load.' );
	}
	return utils;
};

describe( 'getUserFriendlyAddress', () => {
	const originalSettings = window.wcSettings;

	afterEach( () => {
		window.wcSettings = originalSettings;
	} );

	it.each( [
		{
			address: {
				country: 'AU',
				state: 'VIC',
				city: 'Melbourne',
				postcode: '3000',
			},
			expected: 'Australia, Victoria, Melbourne, 3000',
		},
		{
			address: {
				country: 'US',
				state: 'CA',
				city: 'Los Angeles',
				postcode: '90001',
			},
			expected: 'United States (US), California, Los Angeles, 90001',
		},
	] )(
		'names the state and the country in $expected',
		( { address, expected } ) => {
			const { getUserFriendlyAddress } = loadUtils();

			expect( getUserFriendlyAddress( address ) ).toBe( expected );
		}
	);

	it( 'keeps a state code the country has no name for', () => {
		const { getUserFriendlyAddress } = loadUtils();

		expect(
			getUserFriendlyAddress( {
				country: 'AU',
				state: 'QLD',
				city: 'Brisbane',
				postcode: '4000',
			} )
		).toBe( 'Australia, QLD, Brisbane, 4000' );
	} );

	it( 'drops empty address parts', () => {
		const { getUserFriendlyAddress } = loadUtils();

		expect(
			getUserFriendlyAddress( {
				country: 'AU',
				state: '',
				city: 'Melbourne',
				postcode: '',
			} )
		).toBe( 'Australia, Melbourne' );
	} );
} );
