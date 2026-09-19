/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Extension } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { computePluginsSelection, joinWithAnd, Plugins } from '../Plugins';

const getPluginCheckbox = ( name: string ) => {
	const card = screen
		.getByRole( 'heading', { level: 3, name } )
		.closest( '.woocommerce-profiler-plugins-plugin-card' );

	expect( card ).not.toBeNull();

	return within( card! ).getByRole( 'checkbox' );
};

describe( 'Plugins Component', () => {
	const mockSendEvent = jest.fn();
	const mockContext = {
		pluginsAvailable: [
			{
				slug: 'woocommerce-payments',
				name: 'WooPayments',
				label: 'WooPayments',
				is_activated: false,
				description: '',
				key: 'woocommerce-payments',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
			{
				slug: 'google-listings-and-ads',
				name: 'Google for WooCommerce',
				label: 'Google for WooCommerce',
				is_activated: false,
				description: '',
				key: 'google-listings-and-ads',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
			{
				slug: 'jetpack',
				name: 'Jetpack',
				label: 'Jetpack',
				is_activated: true,
				description: '',
				key: 'jetpack',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
			{
				slug: 'mailpoet',
				name: 'MailPoet',
				label: 'MailPoet',
				is_activated: false,
				description: '',
				key: 'mailpoet:alt',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
		],
		pluginsSelected: [],
		pluginsInstallationErrors: [],
	};
	const navigationProgress = 80;
	beforeEach( () => {
		mockSendEvent.mockClear();
	} );

	it( 'renders correctly', () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		expect(
			screen.getByText(
				/No commitment required – you can remove them at any time/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { level: 3, name: 'WooPayments' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Google for WooCommerce' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Jetpack' ) ).toBeInTheDocument();
		expect( screen.getByText( 'MailPoet' ) ).toBeInTheDocument();
	} );

	it( 'selects each default inactive recommendation', () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);

		expect( getPluginCheckbox( 'WooPayments' ) ).toBeChecked();
		expect( getPluginCheckbox( 'Google for WooCommerce' ) ).toBeChecked();
		expect( getPluginCheckbox( 'MailPoet' ) ).toBeChecked();
		expect(
			screen
				.getByText( 'Jetpack' )
				.closest( '.woocommerce-profiler-plugins-plugin-card' )
		).toHaveClass( 'is-installed' );
	} );

	it( 'completes without selecting when every plugin is installed', async () => {
		render(
			<Plugins
				context={ {
					...mockContext,
					pluginsAvailable: mockContext.pluginsAvailable.map(
						( plugin ) => ( { ...plugin, is_activated: true } )
					),
				} }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);

		expect(
			screen
				.getByRole( 'heading', { level: 3, name: 'WooPayments' } )
				.closest( '.woocommerce-profiler-plugins-plugin-card' )
		).toHaveTextContent( 'Installed' );

		await userEvent.click( screen.getByText( 'Continue' ) );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_PAGE_COMPLETED_WITHOUT_SELECTING_PLUGINS',
		} );
	} );

	it( 'retries the previous selection after an installation error', async () => {
		render(
			<Plugins
				context={ {
					...mockContext,
					pluginsInstallationErrors: [
						{
							plugin: 'woocommerce-payments',
							error: 'Installation failed',
							errorDetails: {
								data: {
									code: 'some_error_code',
									data: {
										status: 400,
									},
								},
							},
						},
					],
					pluginsSelected: [ 'woocommerce-payments', 'mailpoet:alt' ],
				} }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);

		expect(
			screen.getByText(
				/Oops! We encountered a problem while installing/
			)
		).toBeInTheDocument();
		expect( getPluginCheckbox( 'WooPayments' ) ).toBeChecked();
		expect(
			getPluginCheckbox( 'Google for WooCommerce' )
		).not.toBeChecked();
		expect( getPluginCheckbox( 'MailPoet' ) ).toBeChecked();

		await userEvent.click( screen.getByText( 'Please try again' ) );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				pluginsShown: [
					'woocommerce-payments',
					'google-listings-and-ads',
					'jetpack',
					'mailpoet',
				],
				pluginsSelected: [ 'woocommerce-payments', 'mailpoet' ],
				pluginsUnselected: [ 'google-listings-and-ads' ],
			},
		} );
	} );

	it( 'submits normalized shown, selected, and unselected plugin keys', async () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);

		await userEvent.click( getPluginCheckbox( 'MailPoet' ) );
		await userEvent.click( screen.getByText( 'Continue' ) );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				pluginsShown: [
					'woocommerce-payments',
					'google-listings-and-ads',
					'jetpack',
					'mailpoet',
				],
				pluginsSelected: [
					'woocommerce-payments',
					'google-listings-and-ads',
				],
				pluginsUnselected: [ 'mailpoet' ],
			},
		} );
	} );

	it( 'handles skip action', async () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		const skipButton = screen.getByText( 'Skip this step' );
		await userEvent.click( skipButton );
		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_PAGE_SKIPPED',
		} );
	} );
} );

describe( 'computePluginsSelection', () => {
	const mockPluginsAvailable = [
		{ key: 'plugin1', is_activated: false },
		{ key: 'plugin2', is_activated: true },
		{ key: 'plugin3', is_activated: false },
	];

	it( 'correctly computes selection when no plugins are selected', () => {
		const selectedPlugins = new Set< Extension >();
		const result = computePluginsSelection(
			mockPluginsAvailable as Extension[],
			selectedPlugins
		);

		expect( result ).toEqual( {
			pluginsShown: [ 'plugin1', 'plugin2', 'plugin3' ],
			pluginsUnselected: [ 'plugin1', 'plugin3' ],
			selectedPluginSlugs: [],
		} );
	} );

	it( 'correctly computes selection when some plugins are selected', () => {
		const selectedPlugins = new Set( [
			{ key: 'plugin1' },
			{ key: 'plugin3' },
		] as Extension[] );
		const result = computePluginsSelection(
			mockPluginsAvailable as Extension[],
			selectedPlugins
		);

		expect( result ).toEqual( {
			pluginsShown: [ 'plugin1', 'plugin2', 'plugin3' ],
			pluginsUnselected: [],
			selectedPluginSlugs: [ 'plugin1', 'plugin3' ],
		} );
	} );

	it( 'correctly handles already installed plugins', () => {
		const selectedPlugins = new Set< Extension >( [
			{ key: 'plugin1' } as Extension,
		] );
		const result = computePluginsSelection(
			mockPluginsAvailable as Extension[],
			selectedPlugins
		);

		expect( result ).toEqual( {
			pluginsShown: [ 'plugin1', 'plugin2', 'plugin3' ],
			pluginsUnselected: [ 'plugin3' ],
			selectedPluginSlugs: [ 'plugin1' ],
		} );
	} );

	it( 'returns empty arrays when no plugins are available', () => {
		const selectedPlugins = new Set< Extension >();
		const result = computePluginsSelection( [], selectedPlugins );

		expect( result ).toEqual( {
			pluginsShown: [],
			pluginsUnselected: [],
			selectedPluginSlugs: [],
		} );
	} );
} );

describe( 'joinWithAnd', () => {
	it( 'should fallback to en_US locale when current locale is invalid', () => {
		const items = [ 'apple', 'banana', 'orange' ];
		const result = joinWithAnd( items, 'invalid-locale' );

		expect( result ).toEqual( [
			{ type: 'element', value: 'apple' },
			{ type: 'literal', value: ', ' },
			{ type: 'element', value: 'banana' },
			{ type: 'literal', value: ', and ' },
			{ type: 'element', value: 'orange' },
		] );
	} );
} );
