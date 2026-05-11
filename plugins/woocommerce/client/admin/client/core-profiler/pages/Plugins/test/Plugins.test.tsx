/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { Extension } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { computePluginsSelection, joinWithAnd, Plugins } from '../Plugins';

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
			{
				slug: 'plugin3',
				name: 'Plugin 3',
				label: 'Plugin 3',
				is_activated: false,
				description: '',
				key: 'plugin3',
				image_url: '',
				manage_url: '',
				is_built_by_wc: false,
				is_visible: true,
			},
			{
				slug: 'plugin4',
				name: 'Plugin 4',
				label: 'Plugin 4',
				is_activated: false,
				description: '',
				key: 'plugin4',
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
				/No commitment required – you can remove them at any time/
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 2' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 3' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Plugin 4' ) ).toBeInTheDocument();
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
		fireEvent.click( checkboxLabel ); // because the checkbox is enabled by default, let's uncheck it
		fireEvent.click( checkboxLabel ); // then check it
		const checkboxLabel3 = screen.getByText( 'Plugin 3' );
		fireEvent.click( checkboxLabel3 );
		const checkboxLabel4 = screen.getByText( 'Plugin 4' ); // attempt to uncheck 4, but it shouldn't do anything since it is already unchecked
		fireEvent.click( checkboxLabel4 );
		const installButton = screen.getByText( 'Continue' );
		fireEvent.click( installButton );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				pluginsSelected: [ 'plugin1' ],
				pluginsShown: [ 'plugin1', 'plugin2', 'plugin3', 'plugin4' ],
				pluginsUnselected: [ 'plugin3', 'plugin4' ],
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
			type: 'PLUGINS_PAGE_COMPLETED_WITHOUT_SELECTING_PLUGINS',
		} );
	} );

	it( 'initialises with all plugins selected when there were no errors previously', () => {
		render(
			<Plugins
				context={ mockContext }
				sendEvent={ mockSendEvent }
				navigationProgress={ navigationProgress }
			/>
		);
		const checkboxLabels = screen.getAllByRole( 'checkbox' );
		expect( checkboxLabels ).toHaveLength( 3 );
		checkboxLabels.forEach( ( checkbox ) => {
			expect( checkbox ).toBeChecked();
		} );
	} );

	it( 'initialises with the previous selection correctly when there were errors previously', () => {
		render(
			<Plugins
				context={ {
					...mockContext,
					pluginsAvailable: [ ...mockContext.pluginsAvailable ],
					pluginsInstallationErrors: [
						{
							plugin: 'plugin4',
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
					pluginsSelected: [ 'plugin4' ],
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
		const checkbox1 = screen
			.getByText( 'Plugin 1' )
			.closest( '.woocommerce-profiler-plugins-plugin-card' )
			?.querySelector( 'input[type="checkbox"]' );
		expect( checkbox1 ).not.toBeChecked();
		const checkbox3 = screen
			.getByText( 'Plugin 3' )
			.closest( '.woocommerce-profiler-plugins-plugin-card' )
			?.querySelector( 'input[type="checkbox"]' );
		expect( checkbox3 ).not.toBeChecked();
		const checkbox4 = screen
			// use role because error message also contains the plugin name
			.getByRole( 'heading', { level: 3, name: 'Plugin 4' } )
			.closest( '.woocommerce-profiler-plugins-plugin-card' )
			?.querySelector( 'input[type="checkbox"]' );
		expect( checkbox4 ).toBeChecked();
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
