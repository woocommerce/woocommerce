/**
 * External dependencies
 */
import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useDispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import TaxRecommendations from '../tax-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '~/components/tracked-link/tracked-link', () => ( {
	TrackedLink: ( { message } ) => <div>{ message }</div>,
} ) );

jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( { children } ) => children,
	DismissableListHeading: ( { children } ) => children,
} ) );

jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: () => null,
} ) );

const clickAndFlush = async ( element: Element ) => {
	await act( async () => {
		await userEvent.click( element );
		await Promise.resolve();
	} );
};

describe( 'TaxRecommendations', () => {
	const installPluginsMock = jest.fn().mockResolvedValue( undefined );
	const activatePluginsMock = jest.fn().mockResolvedValue( undefined );
	const createSuccessNoticeMock = jest.fn();
	let installedPlugins: string[] = [];
	let activePlugins: string[] = [];
	let countryCode = 'US';

	beforeEach( () => {
		installPluginsMock.mockClear();
		activatePluginsMock.mockClear();
		createSuccessNoticeMock.mockClear();
		( recordEvent as jest.Mock ).mockClear();
		installedPlugins = [];
		activePlugins = [];
		countryCode = 'US';

		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getSettings: () => ( {
					general: {
						woocommerce_default_country: countryCode,
					},
				} ),
				getInstalledPlugins: () => installedPlugins,
				getActivePlugins: () => activePlugins,
			} ) )
		);

		( useDispatch as jest.Mock ).mockImplementation( ( store ) => {
			if ( store === 'core/notices' ) {
				return {
					createSuccessNotice: createSuccessNoticeMock,
				};
			}

			return {
				installPlugins: installPluginsMock,
				activatePlugins: activatePluginsMock,
			};
		} );
	} );

	it( 'renders WooCommerce Tax and Anrok with install buttons when no related plugins are present', () => {
		render( <TaxRecommendations /> );
		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Install' ) ).toHaveLength( 2 );
	} );

	it( 'shows Activate when Anrok is installed but inactive', () => {
		installedPlugins = [ 'anrok-tax' ];

		render( <TaxRecommendations /> );

		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Activate' ) ).toBeInTheDocument();
	} );

	it( 'shows a disabled Active button when Anrok is already active', async () => {
		activePlugins = [ 'anrok-tax' ];

		render( <TaxRecommendations /> );

		const activeButton = screen.getByRole( 'button', {
			name: 'Anrok is already active',
		} );

		expect( activeButton ).toHaveTextContent( 'Active' );
		expect( activeButton ).toHaveAttribute( 'aria-disabled', 'true' );

		await clickAndFlush( activeButton );

		expect( installPluginsMock ).not.toHaveBeenCalled();
		expect( activatePluginsMock ).not.toHaveBeenCalled();
	} );

	it( 'shows Active for WooCommerce Tax when the services alias is active', () => {
		activePlugins = [ 'woocommerce-services' ];

		render( <TaxRecommendations /> );

		expect(
			screen.getByRole( 'button', {
				name: 'WooCommerce Tax is already active',
			} )
		).toHaveTextContent( 'Active' );
	} );

	it( 'renders only Anrok for unsupported countries', () => {
		countryCode = 'BR';

		render( <TaxRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Tax' )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Install' ) ).toBeInTheDocument();
	} );

	it( 'fires tax_partner_impression with both recommendations for supported countries', () => {
		render( <TaxRecommendations /> );

		expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_impression', {
			context: 'settings',
			country: 'US',
			plugins: 'woocommerce-services,anrok-tax',
		} );
	} );

	it( 'fires tax_partner_impression with only Anrok for unsupported countries', () => {
		countryCode = 'BR';

		render( <TaxRecommendations /> );

		expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_impression', {
			context: 'settings',
			country: 'BR',
			plugins: 'anrok-tax',
		} );
	} );

	it( 'does not fire tax_partner_impression before the store country is available', () => {
		countryCode = '';

		render( <TaxRecommendations /> );

		expect( recordEvent ).not.toHaveBeenCalledWith(
			'tax_partner_impression',
			expect.anything()
		);
	} );

	it( 'installs WooCommerce Tax using the WooCommerce Services slug', async () => {
		render( <TaxRecommendations /> );

		const wooCommerceTaxItem = screen
			.getByText( 'WooCommerce Tax' )
			.closest( '.woocommerce-list__item' );

		expect( wooCommerceTaxItem ).not.toBeNull();

		await clickAndFlush(
			within( wooCommerceTaxItem as HTMLElement ).getByRole( 'button', {
				name: 'Install',
			} )
		);

		await waitFor( () => {
			expect( installPluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-services',
			] );
		} );

		expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_click', {
			context: 'settings',
			selected_plugin: 'woocommerce-services',
		} );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_tax_recommendation_setup_click',
			{
				plugin: 'woocommerce-services',
				action: 'install',
			}
		);

		await waitFor( () => {
			expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
				'WooCommerce Tax is installed!',
				expect.anything()
			);
		} );

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_install', {
				context: 'settings',
				selected_plugin: 'woocommerce-services',
				success: true,
			} );
		} );
	} );

	it( 'activates the installed WooCommerce Tax alias when one is already present', async () => {
		installedPlugins = [ 'woocommerce-tax' ];

		render( <TaxRecommendations /> );

		const wooCommerceTaxItem = screen
			.getByText( 'WooCommerce Tax' )
			.closest( '.woocommerce-list__item' );

		expect( wooCommerceTaxItem ).not.toBeNull();

		await clickAndFlush(
			within( wooCommerceTaxItem as HTMLElement ).getByRole( 'button', {
				name: 'Activate',
			} )
		);

		await waitFor( () => {
			expect( activatePluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-tax',
			] );
		} );

		expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_click', {
			context: 'settings',
			selected_plugin: 'woocommerce-tax',
		} );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_tax_recommendation_setup_click',
			{
				plugin: 'woocommerce-tax',
				action: 'activate',
			}
		);

		await waitFor( () => {
			expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
				'WooCommerce Tax activated!',
				expect.anything()
			);
		} );

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'tax_partner_activate',
				{
					context: 'settings',
					selected_plugin: 'woocommerce-tax',
					success: true,
				}
			);
		} );
	} );

	it( 'records a failed tax_partner_install event when install fails', async () => {
		installPluginsMock.mockRejectedValueOnce( undefined );

		render( <TaxRecommendations /> );

		const wooCommerceTaxItem = screen
			.getByText( 'WooCommerce Tax' )
			.closest( '.woocommerce-list__item' );

		expect( wooCommerceTaxItem ).not.toBeNull();

		await clickAndFlush(
			within( wooCommerceTaxItem as HTMLElement ).getByRole( 'button', {
				name: 'Install',
			} )
		);

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith( 'tax_partner_install', {
				context: 'settings',
				selected_plugin: 'woocommerce-services',
				success: false,
			} );
		} );
	} );

	it( 'records a failed tax_partner_activate event when activation fails', async () => {
		activatePluginsMock.mockRejectedValueOnce( undefined );
		installedPlugins = [ 'anrok-tax' ];

		render( <TaxRecommendations /> );

		const anrokItem = screen
			.getByText( 'Anrok' )
			.closest( '.woocommerce-list__item' );

		expect( anrokItem ).not.toBeNull();

		await clickAndFlush(
			within( anrokItem as HTMLElement ).getByRole( 'button', {
				name: 'Activate',
			} )
		);

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'tax_partner_activate',
				{
					context: 'settings',
					selected_plugin: 'anrok-tax',
					success: false,
				}
			);
		} );
	} );
} );
