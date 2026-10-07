const NEW_HIDDEN_BLOCKS = [
	'coupons/amount',
	'downloads/download_count',
	'taxes/order_tax',
	'taxes/total_tax',
	'taxes/shipping_tax',
];

const LEGACY_HIDDEN_BLOCKS = [
	...NEW_HIDDEN_BLOCKS,
	'coupons/orders_count',
	'revenue/shipping',
	'orders/avg_order_value',
	'revenue/refunds',
	'revenue/gross_sales',
];

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

/**
 * The default sections are built when the module loads, so each case loads a
 * fresh copy with the admin setting it needs.
 *
 * @param {Object} adminSettings Admin settings the module should see.
 * @return {Array.<string>} Hidden blocks of the Performance section.
 */
const getPerformanceHiddenBlocks = ( adminSettings ) => {
	let sections;

	jest.isolateModules( () => {
		// eslint-disable-next-line @typescript-eslint/no-require-imports -- The isolated registry holds its own copy of the mock.
		require( '~/utils/admin-settings' ).getAdminSetting.mockImplementation(
			( name, fallback ) =>
				name in adminSettings ? adminSettings[ name ] : fallback
		);
		// eslint-disable-next-line @typescript-eslint/no-require-imports -- Load after the admin setting is in place.
		sections = require( '../default-sections' ).default;
	} );

	return sections.find( ( section ) => section.key === 'store-performance' )
		.hiddenBlocks;
};

describe( 'default sections', () => {
	it( 'shows ten performance metrics to stores installed after the defaults changed', () => {
		expect(
			getPerformanceHiddenBlocks( {
				usesLegacyPerformanceDefaults: false,
			} )
		).toEqual( NEW_HIDDEN_BLOCKS );
	} );

	it( 'keeps the previous performance metrics for stores installed before the defaults changed', () => {
		expect(
			getPerformanceHiddenBlocks( {
				usesLegacyPerformanceDefaults: true,
			} )
		).toEqual( LEGACY_HIDDEN_BLOCKS );
	} );

	it( 'keeps the previous performance metrics when the setting is missing', () => {
		expect( getPerformanceHiddenBlocks( {} ) ).toEqual(
			LEGACY_HIDDEN_BLOCKS
		);
	} );
} );
