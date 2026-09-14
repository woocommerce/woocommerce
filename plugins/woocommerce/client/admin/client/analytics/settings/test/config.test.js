let mockPreloadedDefaults = {};

jest.mock( '~/utils/admin-settings', () => ( {
	...jest.requireActual( '~/utils/admin-settings' ),
	getAdminSetting: ( name, fallback ) =>
		name === 'wcAdminSettingsDefaults' ? mockPreloadedDefaults : fallback,
} ) );

// config.js reads the preloaded defaults at module load, so each case needs a fresh import.
const loadConfig = ( preloadedDefaults ) => {
	mockPreloadedDefaults = preloadedDefaults;
	jest.resetModules();
	return import( '../config' );
};

describe( 'Analytics settings config - order status defaults', () => {
	it( 'uses the preloaded (filtered) defaults for the order status settings', async () => {
		const config = await loadConfig( {
			woocommerce_excluded_report_order_statuses: [ 'pending', 'failed' ],
			woocommerce_actionable_order_statuses: [
				'processing',
				'on-hold',
				'my-status',
			],
		} );

		expect(
			config.config.woocommerce_excluded_report_order_statuses
				.defaultValue
		).toEqual( [ 'pending', 'failed' ] );
		expect(
			config.config.woocommerce_actionable_order_statuses.defaultValue
		).toEqual( [ 'processing', 'on-hold', 'my-status' ] );
		expect( config.DEFAULT_ACTIONABLE_STATUSES ).toEqual( [
			'processing',
			'on-hold',
			'my-status',
		] );
	} );

	it( 'falls back to the built-in defaults when nothing was preloaded', async () => {
		const config = await loadConfig( {} );

		expect(
			config.config.woocommerce_excluded_report_order_statuses
				.defaultValue
		).toEqual( [ 'pending', 'cancelled', 'failed' ] );
		expect(
			config.config.woocommerce_actionable_order_statuses.defaultValue
		).toEqual( [ 'processing', 'on-hold' ] );
	} );

	it( 'falls back to the built-in defaults when the preload is null', async () => {
		const config = await loadConfig( null );

		expect(
			config.config.woocommerce_actionable_order_statuses.defaultValue
		).toEqual( [ 'processing', 'on-hold' ] );
	} );

	it( 'ignores a malformed preloaded default', async () => {
		const config = await loadConfig( {
			woocommerce_actionable_order_statuses: 'processing',
		} );

		expect(
			config.config.woocommerce_actionable_order_statuses.defaultValue
		).toEqual( [ 'processing', 'on-hold' ] );
	} );
} );
