/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { Extension } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { computePluginsSelection, Plugins } from '../Plugins';

describe( 'Plugins Component', () => {
	const mockSendEvent = jest.fn();
	const mockContext = {
		pluginsAvailable: [
			{
				slug: 'plugin1',
				name: 'Plugin 1',
				label: 'Plugin 1',
				is_activated: false,
				description: '',
				key: 'plugin1',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
			{
				slug: 'plugin2',
				name: 'Plugin 2',
				label: 'Plugin 2',
				is_activated: true,
				description: '',
				key: 'plugin2',
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
				/Enhance your store by installing these free business features/
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 2' ) ).toBeInTheDocument();
	} );

	it( 'handles plugin selection', () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		const checkboxLabel = screen.getByText( 'Plugin 1' );
		fireEvent.click( checkboxLabel );
		const installButton = screen.getByText( 'Continue' );
		fireEvent.click( installButton );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				pluginsSelected: [ 'plugin1' ],
				pluginsShown: [ 'plugin1', 'plugin2' ],
				pluginsUnselected: [],
			},
		} );
	} );

	it( 'handles case where all plugins are already installed', () => {
		render(
			<Plugins
				context={ {
					...mockContext,
					pluginsAvailable: mockContext.pluginsAvailable.map(
						( plugin ) => ( {
							...plugin,
							is_activated: true,
						} )
					),
				} }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		const plugin1Card = screen
			.getByText( 'Plugin 1' )
			.closest( '.woocommerce-profiler-plugins-plugin-card' );
		expect( plugin1Card ).toHaveClass( 'is-installed' );
		expect( plugin1Card ).toHaveTextContent( 'Installed' );
		expect(
			screen
				.getByText( 'Plugin 2' )
				.closest( '.woocommerce-profiler-plugins-plugin-card' )
		).toHaveTextContent( 'Installed' );
		const continueButton = screen.getByText( 'Continue' );
		fireEvent.click( continueButton );
		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_PAGE_SKIPPED',
		} );
	} );

	it( 'handles skip action', () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		const skipButton = screen.getByText( 'Skip this step' );
		fireEvent.click( skipButton );
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
