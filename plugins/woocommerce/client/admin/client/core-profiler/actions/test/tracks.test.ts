/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import tracksActions, { resetShippingPartnerImpressionFlag } from '../tracks';
import type { CoreProfilerStateMachineContext } from '../../index';
import type { PluginInstallError } from '../../services/installAndActivatePlugins';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( () => '9.8.0' ),
} ) );

const makeContext = (
	overrides: Partial< CoreProfilerStateMachineContext > = {}
): CoreProfilerStateMachineContext =>
	( {
		optInDataSharing: false,
		userProfile: {},
		pluginsAvailable: [],
		pluginsSelected: [],
		pluginsInstallationErrors: [],
		geolocatedLocation: undefined,
		businessInfo: { location: 'US:CA' },
		countries: [],
		loader: {},
		coreProfilerCompletedSteps: {},
		onboardingProfile: {},
		currentUserEmail: undefined,
		...overrides,
	} as CoreProfilerStateMachineContext );

const shippingPlugins = [
	{
		key: 'woocommerce-shipping',
		slug: 'woocommerce-shipping',
		name: 'WooCommerce Shipping',
		label: 'WooCommerce Shipping',
		is_activated: false,
		description: '',
		image_url: '',
		manage_url: '',
		is_built_by_wc: true,
		is_visible: true,
	},
	{
		key: 'woocommerce-shipstation-integration',
		slug: 'woocommerce-shipstation-integration',
		name: 'ShipStation',
		label: 'ShipStation',
		is_activated: false,
		description: '',
		image_url: '',
		manage_url: '',
		is_built_by_wc: false,
		is_visible: true,
	},
];

const nonShippingPlugin = {
	key: 'woocommerce-payments',
	slug: 'woocommerce-payments',
	name: 'WooPayments',
	label: 'WooPayments',
	is_activated: false,
	description: '',
	image_url: '',
	manage_url: '',
	is_built_by_wc: true,
	is_visible: true,
};

describe( 'Core Profiler shipping partner tracking', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		resetShippingPartnerImpressionFlag();
	} );

	describe( 'recordShippingPartnerImpression', () => {
		it( 'should fire shipping_partner_impression when shipping plugins are available', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			tracksActions.recordShippingPartnerImpression( { context } );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_impression',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
				}
			);
		} );

		it( 'should not fire shipping_partner_impression when no shipping plugins are available', () => {
			const context = makeContext( {
				pluginsAvailable: [ nonShippingPlugin ],
			} );

			tracksActions.recordShippingPartnerImpression( { context } );

			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_impression',
				expect.anything()
			);
		} );

		it( 'should only fire shipping_partner_impression once even if called multiple times', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			tracksActions.recordShippingPartnerImpression( { context } );
			tracksActions.recordShippingPartnerImpression( { context } );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_impression',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
				}
			);
			expect(
				( recordEvent as jest.Mock ).mock.calls.filter(
					( [ eventName ] ) =>
						eventName === 'shipping_partner_impression'
				)
			).toHaveLength( 1 );
		} );
	} );

	describe( 'recordTracksPluginsInstallationRequest (shipping_partner_click)', () => {
		it( 'should fire shipping_partner_click for each selected shipping plugin', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			tracksActions.recordTracksPluginsInstallationRequest( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_REQUESTED',
					payload: {
						pluginsShown: [
							'woocommerce-shipping',
							'woocommerce-shipstation-integration',
							'woocommerce-payments',
						],
						pluginsSelected: [
							'woocommerce-shipping',
							'woocommerce-shipstation-integration',
							'woocommerce-payments',
						],
						pluginsUnselected: [],
					},
				},
			} );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_click',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
					selected_plugin: 'woocommerce-shipping',
				}
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_click',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
					selected_plugin: 'woocommerce-shipstation-integration',
				}
			);
		} );

		it( 'should not fire shipping_partner_click when no shipping plugins are selected', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			tracksActions.recordTracksPluginsInstallationRequest( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_REQUESTED',
					payload: {
						pluginsShown: [
							'woocommerce-shipping',
							'woocommerce-payments',
						],
						pluginsSelected: [ 'woocommerce-payments' ],
						pluginsUnselected: [ 'woocommerce-shipping' ],
					},
				},
			} );

			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_click',
				expect.anything()
			);
		} );
	} );

	describe( 'recordSuccessfulPluginInstallation (shipping_partner_install + shipping_partner_activate)', () => {
		it( 'should fire install and activate success for each shipping plugin', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			tracksActions.recordSuccessfulPluginInstallation( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_COMPLETED',
					payload: {
						installationCompletedResult: {
							installedPlugins: [
								{
									plugin: 'woocommerce-shipping',
									installTime: 1000,
								},
								{
									plugin: 'woocommerce-payments',
									installTime: 2000,
								},
							],
							totalTime: 3000,
						},
					},
				},
			} );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_activate',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
		} );

		it( 'should not fire shipping events for non-shipping plugins', () => {
			const context = makeContext( {
				pluginsAvailable: [ nonShippingPlugin ],
			} );

			tracksActions.recordSuccessfulPluginInstallation( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_COMPLETED',
					payload: {
						installationCompletedResult: {
							installedPlugins: [
								{
									plugin: 'woocommerce-payments',
									installTime: 2000,
								},
							],
							totalTime: 2000,
						},
					},
				},
			} );

			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_install',
				expect.anything()
			);
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_activate',
				expect.anything()
			);
		} );
	} );

	describe( 'recordFailedPluginInstallations (shipping_partner_install failure)', () => {
		it( 'should fire shipping_partner_install with failure for failed shipping plugins', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			const errors: PluginInstallError[] = [
				{
					plugin: 'woocommerce-shipping',
					error: 'Install failed',
					errorDetails: {
						data: {
							code: 'install_error',
							data: { status: 500 },
						},
					},
				},
			];

			tracksActions.recordFailedPluginInstallations( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS',
					payload: { errors },
				},
			} );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'core-profiler',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
					selected_plugin: 'woocommerce-shipping',
					success: false,
				}
			);
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_activate',
				expect.anything()
			);
		} );

		it( 'should not fire shipping_partner_install for failed non-shipping plugins', () => {
			const context = makeContext( {
				pluginsAvailable: [ ...shippingPlugins, nonShippingPlugin ],
			} );

			const errors: PluginInstallError[] = [
				{
					plugin: 'woocommerce-payments',
					error: 'Install failed',
					errorDetails: {
						data: {
							code: 'install_error',
							data: { status: 500 },
						},
					},
				},
			];

			tracksActions.recordFailedPluginInstallations( {
				context,
				event: {
					type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS',
					payload: { errors },
				},
			} );

			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_install',
				expect.anything()
			);
		} );
	} );

	describe( 'country code extraction', () => {
		it( 'should extract country code from location with state', () => {
			const context = makeContext( {
				pluginsAvailable: [ shippingPlugins[ 0 ] ],
				businessInfo: { location: 'DE:BE' },
			} );

			tracksActions.recordShippingPartnerImpression( { context } );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_impression',
				expect.objectContaining( { country: 'DE' } )
			);
		} );

		it( 'should handle missing location gracefully', () => {
			const context = makeContext( {
				pluginsAvailable: [ shippingPlugins[ 0 ] ],
				businessInfo: {},
			} );

			tracksActions.recordShippingPartnerImpression( { context } );

			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_impression',
				expect.objectContaining( { country: '' } )
			);
		} );
	} );
} );
